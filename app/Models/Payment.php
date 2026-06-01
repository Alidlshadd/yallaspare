<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'user_id',
        'provider',
        'method',
        'status',
        'amount',
        'currency',
        'provider_payment_id',
        'provider_transaction_id',
        'provider_reference',
        'redirect_url',
        'return_url',
        'provider_response',
        'metadata',
        'webhook_received_at',
        'verified_at',
        'paid_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_response' => 'array',
        'metadata' => 'array',
        'webhook_received_at' => 'datetime',
        'verified_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
