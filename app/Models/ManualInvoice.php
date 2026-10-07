<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An invoice written by staff for a sale that did not come through the site.
 *
 * A draft is a working copy and touches nothing else. Finalizing it is the
 * sale: stock leaves the shelf, the document is frozen, and from then on only
 * payments and its share link can change. A finalized invoice that should
 * not have happened is voided, not deleted: the stock comes back and the
 * document stays on file, marked as void.
 */
class ManualInvoice extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    public const STATUS_VOID = 'void';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_STATUSES = [self::PAYMENT_UNPAID, self::PAYMENT_PARTIAL, self::PAYMENT_PAID];

    protected $fillable = [
        'number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_whatsapp',
        'customer_country',
        'customer_city',
        'customer_address',
        'status',
        'payment_status',
        'invoice_date',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'total',
        'paid_amount',
        'notes',
        'share_token_hash',
        'share_token',
        'shared_at',
        'finalized_at',
        'finalized_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'created_by',
    ];

    protected $hidden = ['share_token', 'share_token_hash'];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'delivery_fee' => 'float',
        'total' => 'float',
        'paid_amount' => 'float',
        'voided_at' => 'datetime',
        'share_token' => 'encrypted',
        'shared_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    /**
     * What was invoiced by hand between two days, both included.
     *
     * This is the figure the shop's revenue counts for a manual sale: the
     * total of every finalized invoice dated in the range, whether or not it
     * has been paid yet. Drafts are not sales and void invoices no longer
     * are. Either end may be left open.
     *
     * Dates, not timestamps: an invoice has a day, and comparing days reads
     * the same on MySQL and SQLite where a timestamp range does not.
     */
    public static function invoicedBetween(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): float
    {
        return (float) self::query()
            ->where('status', self::STATUS_FINALIZED)
            ->when($from, fn ($query) => $query->whereDate('invoice_date', '>=', $from->format('Y-m-d')))
            ->when($to, fn ($query) => $query->whereDate('invoice_date', '<=', $to->format('Y-m-d')))
            ->sum('total');
    }

    /**
     * The same, split by day.
     *
     * @return array<string, float> keyed by Y-m-d
     */
    public static function invoicedByDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return self::query()
            ->where('status', self::STATUS_FINALIZED)
            ->whereDate('invoice_date', '>=', $from->format('Y-m-d'))
            ->whereDate('invoice_date', '<=', $to->format('Y-m-d'))
            ->get(['invoice_date', 'total'])
            ->groupBy(fn (self $invoice): string => (string) $invoice->invoice_date?->format('Y-m-d'))
            ->map(fn ($invoices): float => (float) $invoices->sum('total'))
            ->all();
    }

    /**
     * How many sales were invoiced by hand between two days, both included.
     * Each finalized invoice is one sale, the way each order is.
     */
    public static function countBetween(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): int
    {
        return (int) self::finalizedBetween($from, $to)->count();
    }

    /**
     * @return array<string, int> keyed by Y-m-d
     */
    public static function countByDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return self::finalizedBetween($from, $to)
            ->get(['invoice_date'])
            ->countBy(fn (self $invoice): string => (string) $invoice->invoice_date?->format('Y-m-d'))
            ->all();
    }

    /**
     * @return array<int, int> keyed by month number, for one calendar year
     */
    public static function countByMonth(int $year): array
    {
        return self::query()
            ->where('status', self::STATUS_FINALIZED)
            ->whereYear('invoice_date', $year)
            ->get(['invoice_date'])
            ->countBy(fn (self $invoice): int => (int) $invoice->invoice_date?->format('n'))
            ->all();
    }

    /**
     * Catalogue products sold on manual invoices: units and money per
     * product. A line typed by hand has no product and is not here.
     *
     * @return array<int, array{units: float, revenue: float}> keyed by product id
     */
    public static function productSalesBetween(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        return ManualInvoiceItem::query()
            ->whereNotNull('product_id')
            ->whereIn('manual_invoice_id', self::finalizedBetween($from, $to)->select('id'))
            ->selectRaw('product_id, SUM(quantity) as units, SUM(line_total) as revenue')
            ->groupBy('product_id')
            ->get()
            ->mapWithKeys(fn ($row): array => [(int) $row->product_id => [
                'units' => (float) $row->getAttribute('units'),
                'revenue' => (float) $row->getAttribute('revenue'),
            ]])
            ->all();
    }

    /**
     * @return Builder<self>
     */
    private static function finalizedBetween(?\DateTimeInterface $from, ?\DateTimeInterface $to): Builder
    {
        return self::query()
            ->where('status', self::STATUS_FINALIZED)
            ->when($from, fn ($query) => $query->whereDate('invoice_date', '>=', $from->format('Y-m-d')))
            ->when($to, fn ($query) => $query->whereDate('invoice_date', '<=', $to->format('Y-m-d')));
    }

    /** @return HasMany<ManualInvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ManualInvoiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<ManualInvoicePayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(ManualInvoicePayment::class)->orderBy('paid_on')->orderBy('id');
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    /** What the customer still owes. Never negative, and nothing once void. */
    public function balance(): float
    {
        return $this->isVoid() ? 0.0 : max(round($this->total - $this->paid_amount, 2), 0.0);
    }

    public function whatsappNumber(): string
    {
        return (string) ($this->customer_whatsapp ?: $this->customer_phone);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_FINALIZED => __('Finalized'),
            self::STATUS_VOID => __('Void'),
            default => __('Draft'),
        };
    }

    public function paymentStatusLabel(): string
    {
        return self::paymentStatusLabels()[$this->payment_status] ?? (string) $this->payment_status;
    }

    /**
     * @return array<string, string>
     */
    public static function paymentStatusLabels(): array
    {
        return [
            self::PAYMENT_UNPAID => __('Unpaid'),
            self::PAYMENT_PARTIAL => __('Partially paid'),
            self::PAYMENT_PAID => __('Paid'),
        ];
    }

    /** The public page for this invoice, or null while no link is active. */
    public function shareUrl(): ?string
    {
        return $this->share_token ? route('invoices.shared.show', ['token' => $this->share_token]) : null;
    }
}
