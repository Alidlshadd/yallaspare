<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'status',
        'reason',
        'admin_note',
        'refund_amount',
        'requested_at',
        'resolved_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_REQUESTED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_RECEIVED,
            self::STATUS_REFUNDED,
            self::STATUS_CLOSED,
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
