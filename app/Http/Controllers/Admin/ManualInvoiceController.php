<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ManualInvoice;
use App\Models\ManualInvoicePayment;
use App\Models\Product;
use App\Services\InvoiceRenderer;
use App\Services\Invoices\ManualInvoiceService;
use App\Support\AdminLogger;
use App\Support\Pricing\ExchangeRate;
use App\Support\SqlSafe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ManualInvoiceController extends Controller
{
    public function __construct(private readonly ManualInvoiceService $invoices) {}

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'payment_status' => (string) $request->query('payment_status', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];

        $query = ManualInvoice::query()
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $term = SqlSafe::searchTerm($filters['q']);
                $digits = ltrim(preg_replace('/\D+/', '', $filters['q']) ?? '', '0');

                $query->where(function (Builder $nested) use ($term, $digits): void {
                    SqlSafe::whereLike($nested, 'number', $term);
                    SqlSafe::orWhereLike($nested, 'customer_name', $term);

                    if (strlen($digits) >= 3) {
                        SqlSafe::orWhereLike($nested, 'customer_phone', $digits);
                    }
                });
            })
            ->when(in_array($filters['status'], [ManualInvoice::STATUS_DRAFT, ManualInvoice::STATUS_FINALIZED, ManualInvoice::STATUS_VOID], true),
                fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(in_array($filters['payment_status'], ManualInvoice::PAYMENT_STATUSES, true),
                fn (Builder $query) => $query->where('payment_status', $filters['payment_status']))
            ->when($this->isDate($filters['date_from']), fn (Builder $query) => $query->whereDate('invoice_date', '>=', $filters['date_from']))
            ->when($this->isDate($filters['date_to']), fn (Builder $query) => $query->whereDate('invoice_date', '<=', $filters['date_to']));

        // Drafts are not sales yet and void invoices no longer are, so both
        // stay out of the money figures.
        $finalized = (clone $query)->where('status', ManualInvoice::STATUS_FINALIZED);
        $finalizedTotal = (float) (clone $finalized)->sum('total');
        $paidTotal = (float) (clone $finalized)->sum('paid_amount');
        $summary = [
            'count' => (clone $query)->count(),
            'finalized_total' => $finalizedTotal,
            'paid_total' => $paidTotal,
            'outstanding_total' => max($finalizedTotal - $paidTotal, 0),
        ];

        $invoices = $query->latest('invoice_date')->latest('id')->paginate(20)->withQueryString();

        return view('admin.manual-invoices.index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'summary' => $summary,
            'service' => $this->invoices,
        ]);
    }

    public function create(Request $request): View
    {
        $customer = $request->filled('customer_id') ? Customer::query()->find((int) $request->query('customer_id')) : null;

        return $this->form($request, null, $customer);
    }

    public function store(Request $request): RedirectResponse
    {
        $invoice = $this->invoices->saveDraft(null, $this->validated($request), $request->user());

        AdminLogger::log('manual_invoice.created', $invoice, ['number' => $invoice->number]);

        return $this->afterSave($request, $invoice);
    }

    public function show(ManualInvoice $manualInvoice): View
    {
        $manualInvoice->load(['items.product:id,stock_quantity', 'creator:id,name', 'customer', 'payments.recorder:id,name']);

        return view('admin.manual-invoices.show', [
            'invoice' => $manualInvoice,
            'service' => $this->invoices,
            'whatsappUrl' => $this->invoices->whatsappUrl($manualInvoice),
            'rateDrift' => $this->invoices->exchangeRateDrift($manualInvoice),
        ]);
    }

    /**
     * Bring a draft's dollar-priced lines to the current exchange rate.
     */
    public function reprice(Request $request, ManualInvoice $manualInvoice): RedirectResponse
    {
        $this->invoices->repriceDraft($manualInvoice, $request->user());

        return redirect()
            ->route('admin.manual-invoices.show', $manualInvoice)
            ->with('success', __('The draft was recalculated at the current exchange rate.'));
    }

    public function edit(Request $request, ManualInvoice $manualInvoice): View|RedirectResponse
    {
        // A draft is rewritten freely and a finalized invoice is amended;
        // a void one is a closed record.
        if ($manualInvoice->isVoid()) {
            return redirect()
                ->route('admin.manual-invoices.show', $manualInvoice)
                ->with('warning', __('A void invoice cannot be edited.'));
        }

        $manualInvoice->load(['items', 'customer']);

        return $this->form($request, $manualInvoice, $manualInvoice->customer);
    }

    public function update(Request $request, ManualInvoice $manualInvoice): RedirectResponse
    {
        if ($manualInvoice->isFinalized()) {
            $invoice = $this->invoices->amendFinalized($manualInvoice, $this->validated($request), $request->user());

            return redirect()
                ->route('admin.manual-invoices.show', $invoice)
                ->with('success', __('Invoice updated. Stock and the balance due were adjusted to match.'));
        }

        $invoice = $this->invoices->saveDraft($manualInvoice, $this->validated($request), $request->user());

        return $this->afterSave($request, $invoice);
    }

    public function finalize(Request $request, ManualInvoice $manualInvoice): RedirectResponse
    {
        $wasDraft = $manualInvoice->isDraft();
        $invoice = $this->invoices->finalize($manualInvoice, $request->user());

        return redirect()
            ->route('admin.manual-invoices.show', $invoice)
            ->with($wasDraft ? 'success' : 'warning', $wasDraft
                ? __('Invoice finalized. Stock was deducted for its catalogue items.')
                : __('This invoice was already finalized. Nothing was changed.'));
    }

    public function storePayment(Request $request, ManualInvoice $manualInvoice): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['payment', 'refund'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['nullable', Rule::in(ManualInvoicePayment::METHODS)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = (float) $data['amount'] * ($data['kind'] === 'refund' ? -1 : 1);

        $this->invoices->recordPayment($manualInvoice, $amount, $data['paid_on'], $data['method'] ?? null, $data['note'] ?? null, $request->user());

        return back()->with('success', $data['kind'] === 'refund' ? __('Refund recorded.') : __('Payment recorded.'));
    }

    public function void(Request $request, ManualInvoice $manualInvoice): RedirectResponse
    {
        $data = $request->validate([
            'void_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $wasVoid = $manualInvoice->isVoid();
        $this->invoices->void($manualInvoice, $data['void_reason'], $request->user());

        return redirect()
            ->route('admin.manual-invoices.show', $manualInvoice)
            ->with($wasVoid ? 'warning' : 'success', $wasVoid
                ? __('This invoice was already void. Nothing was changed.')
                : __('Invoice voided. Stock for its catalogue items was returned.'));
    }

    public function destroy(ManualInvoice $manualInvoice): RedirectResponse
    {
        // A finalized invoice has moved stock and may be in a customer's
        // hands; only a draft, which has done neither, can simply go.
        $number = $manualInvoice->number;
        $this->invoices->deleteDraft($manualInvoice);

        AdminLogger::log('manual_invoice.draft_deleted', null, ['number' => $number]);

        return redirect()
            ->route('admin.manual-invoices.index')
            ->with('success', __('Draft invoice deleted.'));
    }

    public function pdf(Request $request, ManualInvoice $manualInvoice, InvoiceRenderer $renderer): Response
    {
        // Not `lang`: that parameter switches the language of the whole panel,
        // and printing an Arabic invoice should not turn the admin's screen
        // Arabic.
        return $renderer->manualResponse(
            $manualInvoice,
            $renderer->resolveManualLocale($request->query('doc_lang')),
            $request->boolean('inline'),
        );
    }

    /**
     * The invoice as a page a picture can be taken of, for customers who want
     * an image in their chat rather than a PDF. The picture itself is made in
     * the browser; with `auto` it is made and downloaded as the page opens.
     */
    public function image(Request $request, ManualInvoice $manualInvoice, InvoiceRenderer $renderer): Response
    {
        // `doc_lang`, like the PDF: `lang` would switch the whole panel.
        $locale = $renderer->resolveManualLocale($request->query('doc_lang'));
        app()->setLocale($locale);

        $response = response()->view('invoices.shared', [
            'invoice' => $manualInvoice->load('items'),
            'token' => null,
            'staffPreview' => true,
            'autoImage' => $request->boolean('auto'),
            'locale' => $locale,
            'isRtl' => in_array($locale, ['ar', 'ku'], true),
            'service' => $this->invoices,
        ]);

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function share(ManualInvoice $manualInvoice): RedirectResponse
    {
        $this->invoices->enableSharing($manualInvoice);

        return back()->with('success', __('Share link created. Only someone with the link can open this invoice.'));
    }

    public function revokeShare(ManualInvoice $manualInvoice): RedirectResponse
    {
        $this->invoices->revokeSharing($manualInvoice);

        return back()->with('success', __('Share link revoked. The old link no longer works.'));
    }

    /**
     * The catalogue picker on the invoice form.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->select(['id', 'name_en', 'name_ar', 'name_ku', 'sku', 'part_number', 'oem_number', 'brand', 'price', 'price_currency', 'price_usd', 'stock_quantity'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = SqlSafe::searchTerm($search);

                $query->where(function (Builder $nested) use ($term): void {
                    SqlSafe::whereLike($nested, 'name_en', $term);
                    SqlSafe::orWhereLike($nested, 'name_ar', $term);
                    SqlSafe::orWhereLike($nested, 'name_ku', $term);
                    SqlSafe::orWhereLike($nested, 'sku', $term);
                    SqlSafe::orWhereLike($nested, 'part_number', $term);
                    SqlSafe::orWhereLike($nested, 'oem_number', $term);
                    SqlSafe::orWhereLike($nested, 'brand', $term);
                });
            })
            ->orderBy('name_en')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->localizedName(),
                'sku' => (string) ($product->sku ?: $product->part_number),
                // Always dinars at the current rate. For a dollar product the
                // dollar amount and rate come along too, so the line can say
                // where its price came from and be repriced later.
                'price' => (float) $product->price,
                'usd_price' => $product->isUsdPriced() && $product->price_usd !== null ? (string) $product->price_usd : null,
                'usd_rate' => $product->isUsdPriced() ? ExchangeRate::perHundred() : null,
                'stock' => (int) $product->stock_quantity,
            ])->values(),
        ]);
    }

    /**
     * The form, filled from whichever is most recent: what was just submitted
     * and bounced, or what is saved.
     */
    private function form(Request $request, ?ManualInvoice $invoice, ?Customer $customer): View
    {
        if ($request->old('customer_id')) {
            $customer = Customer::query()->find((int) $request->old('customer_id')) ?? $customer;
        }

        $rows = $request->old('items');

        if (! is_array($rows)) {
            $rows = $invoice
                ? $invoice->items->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'usd_unit_price' => $item->usd_unit_price,
                    'usd_rate_per_100' => $item->usd_rate_per_100,
                ])->all()
                : [];
        }

        return view('admin.manual-invoices.form', [
            'invoice' => $invoice,
            'customer' => $customer,
            'rows' => array_values($rows),
            'service' => $this->invoices,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:120'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'items.*.usd_unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'items.*.usd_rate_per_100' => ['nullable', 'numeric', 'gt:0', 'max:'.ExchangeRate::MAX_PER_HUNDRED],
        ], [
            'customer_id.required' => __('Select a customer for this invoice.'),
            'items.required' => __('Add at least one line to the invoice.'),
        ]);
    }

    /**
     * "Save and finalize" is two steps on purpose: the draft is stored first,
     * so if a product turns out to be short the work is kept and only the
     * finalizing is refused.
     */
    private function afterSave(Request $request, ManualInvoice $invoice): RedirectResponse
    {
        if ($request->input('action') === 'finalize') {
            try {
                return $this->finalize($request, $invoice);
            } catch (ValidationException $e) {
                // Back to the saved draft, not to the form: resubmitting the
                // form would write the same invoice a second time.
                return redirect()
                    ->route('admin.manual-invoices.show', $invoice)
                    ->with('warning', __('Saved as a draft, but it could not be finalized.'))
                    ->withErrors($e->errors());
            }
        }

        return redirect()
            ->route('admin.manual-invoices.show', $invoice)
            ->with('success', __('Draft saved. Stock and revenue are unchanged until the invoice is finalized.'));
    }

    private function isDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
