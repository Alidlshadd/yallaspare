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
 * its payment status and its share link can change.
 */
class ManualInvoice extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

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
        'customer_city',
        'customer_address',
        'status',
        'payment_status',
        'invoice_date',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'total',
        'notes',
        'share_token_hash',
        'share_token',
        'shared_at',
        'finalized_at',
        'finalized_by',
        'created_by',
    ];

    protected $hidden = ['share_token', 'share_token_hash'];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'delivery_fee' => 'float',
        'total' => 'float',
        'share_token' => 'encrypted',
        'shared_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    /** @return HasMany<ManualInvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ManualInvoiceItem::class)->orderBy('sort_order')->orderBy('id');
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

    public function whatsappNumber(): string
    {
        return (string) ($this->customer_whatsapp ?: $this->customer_phone);
    }

    public function statusLabel(): string
    {
        return $this->isFinalized() ? __('Finalized') : __('Draft');
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
