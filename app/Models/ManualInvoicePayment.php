<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money received against a manual invoice. A negative amount is a refund.
 */
class ManualInvoicePayment extends Model
{
    public const METHODS = ['cash', 'bank_transfer', 'card', 'other'];

    protected $fillable = [
        'manual_invoice_id',
        'amount',
        'paid_on',
        'method',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_on' => 'date',
    ];

    /** @return BelongsTo<ManualInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ManualInvoice::class, 'manual_invoice_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRefund(): bool
    {
        return $this->amount < 0;
    }

    /**
     * @return array<string, string>
     */
    public static function methodLabels(): array
    {
        return [
            'cash' => __('Cash'),
            'bank_transfer' => __('Bank transfer'),
            'card' => __('Card'),
            'other' => __('Other'),
        ];
    }
}
