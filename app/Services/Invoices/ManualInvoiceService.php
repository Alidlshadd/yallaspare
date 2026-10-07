<?php

namespace App\Services\Invoices;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\ManualInvoice;
use App\Models\ManualInvoicePayment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Inventory\InventoryAdjustmentService;
use App\Support\AdminLogger;
use App\Support\Pricing\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualInvoiceService
{
    /** The largest value a decimal(12, 2) money column can hold. */
    private const MAX_AMOUNT = 9999999999.99;

    public function __construct(private readonly InventoryAdjustmentService $inventory) {}

    /**
     * Price an invoice from what the form sent.
     *
     * Only quantities, unit prices and the two adjustments are taken from the
     * request. Every total is worked out here, so a figure edited in the
     * browser never reaches the database.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{items: array<int, array<string, mixed>>, subtotal: float, discount_amount: float, delivery_fee: float, total: float}
     */
    public function calculate(array $rows, float $discount, float $deliveryFee): array
    {
        $products = Product::query()
            ->whereIn('id', collect($rows)->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->unique())
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0.0;

        foreach (array_values($rows) as $index => $row) {
            $product = isset($row['product_id']) ? $products->get((int) $row['product_id']) : null;
            $description = trim((string) ($row['description'] ?? ''));
            $sku = trim((string) ($row['sku'] ?? ''));

            if ($description === '' && $product) {
                $description = $product->localizedName();
            }

            // The product's name in every language, kept with the line so the
            // invoice can be printed in any of them. Only while the text is
            // still the product's own name: a description the admin reworded
            // is theirs, and is printed as written whatever the language.
            $translations = null;

            if ($product) {
                $names = array_filter([
                    'en' => trim((string) $product->name_en),
                    'ar' => trim((string) $product->name_ar),
                    'ku' => trim((string) $product->name_ku),
                ], fn (string $name): bool => $name !== '');

                $kept = is_array($row['description_translations'] ?? null) ? $row['description_translations'] : [];

                if (in_array($description, $names, true)) {
                    $translations = $names;
                } elseif ($kept !== [] && in_array($description, $kept, true)) {
                    // A finalized-era copy the catalogue has since renamed.
                    $translations = $kept;
                }
            }

            if ($description === '') {
                throw ValidationException::withMessages([
                    "items.{$index}.description" => __('Each invoice line needs a description.'),
                ]);
            }

            $quantity = (int) $row['quantity'];
            $unitPrice = round((float) $row['unit_price'], 2);

            // A line that came from a dollar-priced product carries the
            // dollar amount and the rate it was converted at. While it does,
            // its dinar price is that conversion and nothing else — worked
            // out here, whatever the form sent. Typing a different unit
            // price on the form drops the dollar amount, and the line is
            // then an ordinary dinar line the rate cannot touch.
            $usdUnitPrice = ExchangeRate::decimal(trim((string) ($row['usd_unit_price'] ?? '')), 2);
            $usdRate = null;

            if ($product && $product->isUsdPriced() && $usdUnitPrice !== null && bccomp($usdUnitPrice, '0', 2) > 0) {
                $usdRate = ExchangeRate::normalizeRate($row['usd_rate_per_100'] ?? null) ?? ExchangeRate::perHundred();
            }

            if ($usdRate !== null) {
                $unitPrice = (float) ExchangeRate::toIqd($usdUnitPrice, $usdRate);
            } else {
                $usdUnitPrice = null;
            }

            $lineTotal = round($quantity * $unitPrice, 2);
            $subtotal += $lineTotal;

            $items[] = [
                'product_id' => $product?->id,
                'description' => mb_substr($description, 0, 255),
                'description_translations' => $translations,
                'sku' => mb_substr($sku !== '' ? $sku : (string) ($product?->sku ?: $product?->part_number), 0, 120) ?: null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'usd_unit_price' => $usdUnitPrice,
                'usd_rate_per_100' => $usdRate,
                // What the part costs the shop today. Rewritten on every save
                // of the draft and one last time when it is finalized.
                'unit_cost' => $product?->cost_price,
                'sort_order' => $index,
            ];
        }

        $subtotal = round($subtotal, 2);
        $discount = round(max($discount, 0), 2);
        $deliveryFee = round(max($deliveryFee, 0), 2);

        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'discount_amount' => __('The discount cannot be larger than the subtotal.'),
            ]);
        }

        // Each field is within range on its own, but quantity times price, or
        // the lines added up, can still exceed what the money columns hold.
        // Refused here it is a message on the form; left to the database it
        // is a failed save or, on a lenient server, a silently clipped total.
        if ($subtotal + $deliveryFee > self::MAX_AMOUNT) {
            throw ValidationException::withMessages([
                'items' => __('The invoice total is too large to be saved. Check the quantities and prices.'),
            ]);
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'delivery_fee' => $deliveryFee,
            'total' => round($subtotal - $discount + $deliveryFee, 2),
        ];
    }

    /**
     * Create a draft, or rewrite one that is still a draft.
     *
     * @param  array<string, mixed>  $data  Validated form input.
     */
    public function saveDraft(?ManualInvoice $invoice, array $data, User $actor): ManualInvoice
    {
        $priced = $this->calculate(
            $data['items'],
            (float) ($data['discount_amount'] ?? 0),
            (float) ($data['delivery_fee'] ?? 0),
        );

        return DB::transaction(function () use ($invoice, $data, $priced, $actor): ManualInvoice {
            if ($invoice) {
                $invoice = ManualInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                $this->assertDraft($invoice);
            }

            $customer = Customer::query()->findOrFail((int) $data['customer_id']);

            $attributes = [
                'customer_id' => $customer->id,
                ...$this->customerSnapshot($customer),
                'invoice_date' => Carbon::parse($data['invoice_date'])->toDateString(),
                'subtotal' => $priced['subtotal'],
                'discount_amount' => $priced['discount_amount'],
                'delivery_fee' => $priced['delivery_fee'],
                'total' => $priced['total'],
                'notes' => $data['notes'] ?? null,
            ];

            if ($invoice) {
                $invoice->update($attributes);
                $invoice->items()->delete();
            } else {
                $invoice = ManualInvoice::query()->create($attributes + [
                    'status' => ManualInvoice::STATUS_DRAFT,
                    'payment_status' => ManualInvoice::PAYMENT_UNPAID,
                    'created_by' => $actor->id,
                ]);
                $invoice->update(['number' => $this->numberFor($invoice)]);
            }

            $invoice->items()->createMany($priced['items']);

            return $invoice->load('items');
        });
    }

    /**
     * Change an invoice that has already been finalized.
     *
     * A customer adds a part, a price is renegotiated, a discount is given
     * after the fact: the invoice is corrected in place rather than voided
     * and rewritten, and everything that depended on the old version is
     * brought in line in the same transaction.
     *
     * Stock moves by the difference only. Each product's quantity before and
     * after is compared: more of it leaves the shelf under the invoice
     * number, less of it comes back, and a product whose quantity did not
     * change is not touched. If the shelf cannot cover an increase nothing is
     * saved at all.
     *
     * Money already received stays as recorded. The new total may not fall
     * below it — a refund has to be recorded first — and paid, partial or
     * unpaid is worked out again against the new total.
     *
     * A line that was on the invoice keeps the cost it was sold at; only a
     * newly added product takes today's. A void invoice cannot be amended.
     *
     * @param  array<string, mixed>  $data  Validated form input.
     */
    public function amendFinalized(ManualInvoice $invoice, array $data, User $actor): ManualInvoice
    {
        $priced = $this->calculate(
            $data['items'],
            (float) ($data['discount_amount'] ?? 0),
            (float) ($data['delivery_fee'] ?? 0),
        );

        return DB::transaction(function () use ($invoice, $data, $priced, $actor): ManualInvoice {
            $invoice = ManualInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (! $invoice->isFinalized()) {
                throw ValidationException::withMessages([
                    'invoice' => __('Only a finalized invoice can be amended.'),
                ]);
            }

            if ($priced['total'] < round((float) $invoice->paid_amount, 2)) {
                throw ValidationException::withMessages([
                    'items' => __('The new total (:total) is less than what has already been paid (:paid). Record a refund first.', [
                        'total' => $this->money($priced['total']),
                        'paid' => $this->money((float) $invoice->paid_amount),
                    ]),
                ]);
            }

            $oldItems = $invoice->items()->get();
            $quantities = fn ($lines) => collect($lines)
                ->filter(fn ($line) => ! empty($line['product_id']))
                ->groupBy('product_id')
                ->map(fn ($group) => (int) $group->sum('quantity'));

            $before = $quantities($oldItems->toArray());
            $after = $quantities($priced['items']);
            $productIds = $before->keys()->merge($after->keys())->unique()->sort()->values();

            // A product deleted since the sale has no shelf to adjust.
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $deltas = [];

            foreach ($productIds as $productId) {
                $delta = (int) $after->get($productId, 0) - (int) $before->get($productId, 0);
                $product = $products->get($productId);

                if ($delta === 0 || ! $product) {
                    continue;
                }

                if ($delta > 0 && (int) $product->stock_quantity < $delta) {
                    throw ValidationException::withMessages([
                        'items' => __('Not enough stock for :product: :available available, :needed needed.', [
                            'product' => $product->localizedName(),
                            'available' => (int) $product->stock_quantity,
                            'needed' => $delta,
                        ]),
                    ]);
                }

                $deltas[$productId] = $delta;
            }

            foreach ($deltas as $productId => $delta) {
                $this->inventory->move(
                    $products->get($productId),
                    $delta > 0 ? InventoryMovement::TYPE_OUT : InventoryMovement::TYPE_IN,
                    abs($delta),
                    $actor,
                    (string) $invoice->number,
                    __('Amendment of manual invoice :number', ['number' => $invoice->number]),
                );
            }

            // The cost a product was sold at stays with it.
            $soldAt = $oldItems->whereNotNull('product_id')->pluck('unit_cost', 'product_id');
            $items = array_map(function (array $line) use ($soldAt): array {
                if ($line['product_id'] !== null && $soldAt->has($line['product_id'])) {
                    $line['unit_cost'] = $soldAt->get($line['product_id']);
                }

                return $line;
            }, $priced['items']);

            $customer = Customer::query()->findOrFail((int) $data['customer_id']);
            $previousTotal = (float) $invoice->total;

            $invoice->update([
                'customer_id' => $customer->id,
                ...$this->customerSnapshot($customer),
                'invoice_date' => Carbon::parse($data['invoice_date'])->toDateString(),
                'subtotal' => $priced['subtotal'],
                'discount_amount' => $priced['discount_amount'],
                'delivery_fee' => $priced['delivery_fee'],
                'total' => $priced['total'],
                'notes' => $data['notes'] ?? null,
                'payment_status' => $this->paymentStatusFor((float) $invoice->paid_amount, $priced['total']),
            ]);

            $invoice->items()->delete();
            $invoice->items()->createMany($items);

            AdminLogger::log('manual_invoice.amended', $invoice, [
                'number' => $invoice->number,
                'previous_total' => $previousTotal,
                'total' => $priced['total'],
                'stock_changes' => $deltas,
            ]);

            return $invoice->load('items');
        });
    }

    /**
     * What a draft would come to at today's rate, when that differs.
     *
     * A draft keeps the rate each dollar line was priced at, so reopening it
     * a week later shows the customer the figure they were quoted. This says
     * how far that has drifted. Null when nothing has: not a draft, no dollar
     * lines, or every line already at the current rate.
     *
     * @return array{lines: array<int, array{old: float, new: float}>, old_total: float, new_total: float, difference: float, old_rate: string, new_rate: string}|null
     */
    public function exchangeRateDrift(ManualInvoice $invoice): ?array
    {
        $rate = ExchangeRate::perHundred();

        if (! $invoice->isDraft() || $rate === null) {
            return null;
        }

        $lines = [];
        $subtotal = 0.0;
        $oldRate = null;

        foreach ($invoice->items as $item) {
            $lineTotal = (float) $item->line_total;

            if ($item->usd_unit_price !== null && $item->usd_rate_per_100 !== null && ! ExchangeRate::sameRate($item->usd_rate_per_100, $rate)) {
                $newUnitPrice = (float) ExchangeRate::toIqd($item->usd_unit_price, $rate);
                $lines[(int) $item->id] = ['old' => (float) $item->unit_price, 'new' => $newUnitPrice];
                $lineTotal = round((int) $item->quantity * $newUnitPrice, 2);
                $oldRate ??= ExchangeRate::normalizeRate($item->usd_rate_per_100);
            }

            $subtotal += $lineTotal;
        }

        if ($lines === [] || $oldRate === null) {
            return null;
        }

        $newTotal = round($subtotal - (float) $invoice->discount_amount + (float) $invoice->delivery_fee, 2);

        return [
            'lines' => $lines,
            'old_total' => (float) $invoice->total,
            'new_total' => $newTotal,
            'difference' => round($newTotal - (float) $invoice->total, 2),
            'old_rate' => $oldRate,
            'new_rate' => $rate,
        ];
    }

    /**
     * Reprice a draft's dollar lines at today's rate.
     *
     * Each line is converted again from its own dollar amount; dinar lines,
     * manual lines, the discount and the delivery fee are left as they are.
     * Only a draft: the status is read under a lock, so a finalized invoice
     * can not be repriced by a stale page or a second tab.
     */
    public function repriceDraft(ManualInvoice $invoice, User $actor): ManualInvoice
    {
        return DB::transaction(function () use ($invoice, $actor): ManualInvoice {
            $invoice = ManualInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($invoice);

            $rate = ExchangeRate::perHundred();
            $previousTotal = (float) $invoice->total;

            $rows = $invoice->items()->orderBy('sort_order')->orderBy('id')->get()->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'usd_unit_price' => $item->usd_unit_price,
                'usd_rate_per_100' => $item->usd_unit_price !== null ? $rate : null,
                'description_translations' => $item->description_translations,
            ])->all();

            $priced = $this->calculate($rows, (float) $invoice->discount_amount, (float) $invoice->delivery_fee);

            $invoice->update([
                'subtotal' => $priced['subtotal'],
                'discount_amount' => $priced['discount_amount'],
                'delivery_fee' => $priced['delivery_fee'],
                'total' => $priced['total'],
            ]);
            $invoice->items()->delete();
            $invoice->items()->createMany($priced['items']);

            AdminLogger::log('manual_invoice.repriced', $invoice, [
                'number' => $invoice->number,
                'per_100_usd' => $rate,
                'previous_total' => $previousTotal,
                'total' => $priced['total'],
                'by' => $actor->id,
            ]);

            return $invoice->load('items');
        });
    }

    /**
     * Delete a draft, and only a draft.
     *
     * The status is read again under a lock rather than trusted from the page
     * that was open: otherwise a delete racing a finalize in another tab could
     * remove an invoice whose stock had just been deducted, leaving a movement
     * with nothing to explain it.
     */
    public function deleteDraft(ManualInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $locked = ManualInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($locked);
            $locked->delete();
        });
    }

    /**
     * Turn a draft into the record of a sale.
     *
     * Stock for every catalogue line leaves through the same inventory service
     * the rest of the panel uses, so it shows up in the movement history under
     * the invoice number. The invoice row is locked first and its status read
     * under that lock: a second click, or two tabs, finds it already finalized
     * and does nothing — no second movement, no second deduction.
     *
     * Either every line is fulfilled or none is. If one product is short the
     * whole thing rolls back and the invoice stays a draft.
     */
    public function finalize(ManualInvoice $invoice, User $actor): ManualInvoice
    {
        return DB::transaction(function () use ($invoice, $actor): ManualInvoice {
            $invoice = ManualInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            // Finalized already, or void: either way there is nothing to do.
            if (! $invoice->isDraft()) {
                return $invoice->load('items');
            }

            $invoice->load('items');

            if ($invoice->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => __('Add at least one line before finalizing the invoice.'),
                ]);
            }

            // One movement per product, even if it appears on several lines.
            $needed = $invoice->items
                ->whereNotNull('product_id')
                ->groupBy('product_id')
                ->map(fn ($lines) => (int) $lines->sum('quantity'));

            $products = Product::query()
                ->whereIn('id', $needed->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($needed as $productId => $quantity) {
                $product = $products->get($productId);

                if ($product && (int) $product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => __('Not enough stock for :product: :available available, :needed needed.', [
                            'product' => $product->localizedName(),
                            'available' => (int) $product->stock_quantity,
                            'needed' => $quantity,
                        ]),
                    ]);
                }
            }

            foreach ($needed as $productId => $quantity) {
                if ($product = $products->get($productId)) {
                    $this->inventory->move(
                        $product,
                        InventoryMovement::TYPE_OUT,
                        $quantity,
                        $actor,
                        (string) $invoice->number,
                        __('Manual invoice :number', ['number' => $invoice->number]),
                    );
                }
            }

            // Cost is frozen with the sale, like everything else on it: the
            // profit on this invoice must not move when a cost is edited.
            foreach ($invoice->items as $item) {
                if ($item->product_id && ($product = $products->get($item->product_id))) {
                    $item->update(['unit_cost' => $product->cost_price]);
                }
            }

            // The customer as they are today is what the document records.
            $snapshot = $invoice->customer ? $this->customerSnapshot($invoice->customer) : [];

            $invoice->update($snapshot + [
                'status' => ManualInvoice::STATUS_FINALIZED,
                'finalized_at' => now(),
                'finalized_by' => $actor->id,
            ]);

            AdminLogger::log('manual_invoice.finalized', $invoice, [
                'number' => $invoice->number,
                'total' => $invoice->total,
                'stock_lines' => $needed->count(),
            ]);

            return $invoice;
        });
    }

    /**
     * Record money received against a finalized invoice, or given back.
     *
     * A positive amount is a payment, a negative one a refund. What has been
     * paid can never go below zero or above the invoice total, and the status
     * shown everywhere — unpaid, partially paid, paid — is worked out from the
     * running sum rather than chosen by hand.
     */
    public function recordPayment(ManualInvoice $invoice, float $amount, string $paidOn, ?string $method, ?string $note, User $actor): ManualInvoicePayment
    {
        return DB::transaction(function () use ($invoice, $amount, $paidOn, $method, $note, $actor): ManualInvoicePayment {
            $invoice = ManualInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $amount = round($amount, 2);

            if (! $invoice->isFinalized()) {
                throw ValidationException::withMessages([
                    'amount' => __('Payments can only be recorded on a finalized invoice.'),
                ]);
            }

            if ($amount == 0.0) {
                throw ValidationException::withMessages(['amount' => __('Enter an amount.')]);
            }

            $paid = round($invoice->paid_amount + $amount, 2);

            if ($paid > $invoice->total) {
                throw ValidationException::withMessages([
                    'amount' => __('This is more than the balance due (:balance).', ['balance' => $this->money($invoice->balance())]),
                ]);
            }

            if ($paid < 0) {
                throw ValidationException::withMessages([
                    'amount' => __('A refund cannot be larger than what has been paid (:paid).', ['paid' => $this->money($invoice->paid_amount)]),
                ]);
            }

            $payment = $invoice->payments()->create([
                'amount' => $amount,
                'paid_on' => Carbon::parse($paidOn)->toDateString(),
                'method' => $method,
                'note' => $note,
                'created_by' => $actor->id,
            ]);

            $invoice->update([
                'paid_amount' => $paid,
                'payment_status' => $this->paymentStatusFor($paid, $invoice->total),
            ]);

            AdminLogger::log($amount < 0 ? 'manual_invoice.refund_recorded' : 'manual_invoice.payment_recorded', $invoice, [
                'number' => $invoice->number,
                'amount' => $amount,
                'paid_amount' => $paid,
            ]);

            return $payment;
        });
    }

    /**
     * Cancel a finalized invoice.
     *
     * The opposite of finalizing, and built the same way: the row is locked,
     * the stock for every catalogue line goes back in through the inventory
     * service under the invoice number, and a second click finds it already
     * void and does nothing. The invoice is kept, marked void, with who did it
     * and why. Its share link is withdrawn, since the document a customer
     * would open no longer stands.
     *
     * Money has to be settled first: an invoice with payments on it cannot be
     * voided until they are refunded, so no payment is ever left attached to
     * a sale that did not happen.
     */
    public function void(ManualInvoice $invoice, string $reason, User $actor): ManualInvoice
    {
        return DB::transaction(function () use ($invoice, $reason, $actor): ManualInvoice {
            $invoice = ManualInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->isVoid()) {
                return $invoice;
            }

            if (! $invoice->isFinalized()) {
                throw ValidationException::withMessages([
                    'void_reason' => __('Only a finalized invoice can be voided. A draft can simply be deleted.'),
                ]);
            }

            if ($invoice->paid_amount > 0) {
                throw ValidationException::withMessages([
                    'void_reason' => __('Refund the :paid already paid before voiding this invoice.', ['paid' => $this->money($invoice->paid_amount)]),
                ]);
            }

            $returned = $invoice->items()
                ->whereNotNull('product_id')
                ->get()
                ->groupBy('product_id')
                ->map(fn ($lines) => (int) $lines->sum('quantity'));

            // A product deleted since the sale has no shelf to go back to.
            $products = Product::query()->whereIn('id', $returned->keys())->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($returned as $productId => $quantity) {
                if ($product = $products->get($productId)) {
                    $this->inventory->move(
                        $product,
                        InventoryMovement::TYPE_IN,
                        $quantity,
                        $actor,
                        (string) $invoice->number,
                        __('Void of manual invoice :number', ['number' => $invoice->number]),
                    );
                }
            }

            $invoice->update([
                'status' => ManualInvoice::STATUS_VOID,
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => $reason,
                'share_token' => null,
                'share_token_hash' => null,
                'shared_at' => null,
            ]);

            AdminLogger::log('manual_invoice.voided', $invoice, [
                'number' => $invoice->number,
                'reason' => $reason,
                'stock_lines' => $returned->count(),
            ]);

            return $invoice;
        });
    }

    private function paymentStatusFor(float $paid, float $total): string
    {
        return match (true) {
            $paid <= 0 => ManualInvoice::PAYMENT_UNPAID,
            $paid >= $total => ManualInvoice::PAYMENT_PAID,
            default => ManualInvoice::PAYMENT_PARTIAL,
        };
    }

    /**
     * Give the invoice a link the customer can open without signing in.
     *
     * Calling it again returns the same link, so a message already sent keeps
     * working. Only a finalized invoice can be shared — a draft may still
     * change under the person reading it.
     */
    public function enableSharing(ManualInvoice $invoice): ManualInvoice
    {
        if (! $invoice->isFinalized()) {
            throw ValidationException::withMessages([
                'share' => __('Finalize the invoice before sharing it.'),
            ]);
        }

        if ($invoice->share_token) {
            return $invoice;
        }

        $token = bin2hex(random_bytes(24));

        $invoice->update([
            'share_token' => $token,
            'share_token_hash' => hash('sha256', $token),
            'shared_at' => now(),
        ]);

        AdminLogger::log('manual_invoice.share_enabled', $invoice, ['number' => $invoice->number]);

        return $invoice;
    }

    public function revokeSharing(ManualInvoice $invoice): void
    {
        if (! $invoice->share_token_hash) {
            return;
        }

        $invoice->update(['share_token' => null, 'share_token_hash' => null, 'shared_at' => null]);

        AdminLogger::log('manual_invoice.share_revoked', $invoice, ['number' => $invoice->number]);
    }

    /**
     * The one invoice a share token opens, or null.
     */
    public function findShared(string $token): ?ManualInvoice
    {
        if (preg_match('/^[a-f0-9]{48}$/', $token) !== 1) {
            return null;
        }

        return ManualInvoice::query()
            ->where('share_token_hash', hash('sha256', $token))
            ->where('status', ManualInvoice::STATUS_FINALIZED)
            ->first();
    }

    /**
     * A WhatsApp chat with the customer, the message already written.
     *
     * It only opens the chat. Nothing is sent until the person at the keyboard
     * presses send, and WhatsApp cannot take an attachment this way — the
     * message carries the link instead.
     */
    public function whatsappUrl(ManualInvoice $invoice): ?string
    {
        $shareUrl = $invoice->shareUrl();

        if ($shareUrl === null) {
            return null;
        }

        $message = implode("\n", [
            __('Hello :name,', ['name' => $invoice->customer_name]),
            __('Your invoice :number from :business is ready.', [
                'number' => $invoice->number,
                'business' => $this->businessName(),
            ]),
            __('Total: :total', ['total' => $this->money((float) $invoice->total)]),
            __('View or download it here:'),
            $shareUrl,
        ]);

        return self::whatsappChatUrl($invoice->whatsappNumber()).'?text='.rawurlencode($message);
    }

    public static function whatsappChatUrl(string $phone): string
    {
        return 'https://wa.me/'.preg_replace('/\D+/', '', $phone);
    }

    public function businessName(): string
    {
        return (string) Setting::getValue('site_name', config('app.name', 'YallaSpare'));
    }

    public function currencyLabel(): string
    {
        $code = (string) Setting::getValue('currency_code', 'IQD');

        return $code !== '' ? $code : (string) Setting::getValue('currency_symbol', 'IQD');
    }

    public function currencyDecimals(): int
    {
        return strtoupper((string) Setting::getValue('currency_code', 'IQD')) === 'IQD' ? 0 : 2;
    }

    public function money(float $amount): string
    {
        return number_format($amount, $this->currencyDecimals()).' '.$this->currencyLabel();
    }

    public function assertDraft(ManualInvoice $invoice): void
    {
        if (! $invoice->isDraft()) {
            throw ValidationException::withMessages([
                'invoice' => __('A finalized invoice can no longer be edited.'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function customerSnapshot(Customer $customer): array
    {
        return [
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_whatsapp' => $customer->whatsapp,
            'customer_country' => $customer->country,
            'customer_city' => $customer->city,
            'customer_address' => $customer->address,
        ];
    }

    private function numberFor(ManualInvoice $invoice): string
    {
        return 'MINV-'.now()->format('Y').'-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);
    }
}
