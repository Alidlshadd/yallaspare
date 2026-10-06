<?php

namespace App\Models;

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
