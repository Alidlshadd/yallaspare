<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'order_number',
        'total_amount',
        'status',
        'payment_method',
        'delivery_address',
        'delivery_city',
        'delivery_phone',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function workflow(): array
    {
        return [
            self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED => [self::STATUS_DELIVERED],
            self::STATUS_DELIVERED => [],
            self::STATUS_CANCELLED => [],
        ];
    }

    public static function normalizedStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'complete', 'completed' => self::STATUS_DELIVERED,
            default => in_array($status, self::allowedStatuses(), true)
                ? $status
                : self::STATUS_PENDING,
        };
    }

    public static function canTransition(string $from, string $to): bool
    {
        $current = self::normalizedStatus($from);
        $next = self::normalizedStatus($to);

        if ($current === $next) {
            return true;
        }

        return in_array($next, self::workflow()[$current] ?? [], true);
    }

    public static function nextStatuses(string $from): array
    {
        $current = self::normalizedStatus($from);

        return self::workflow()[$current] ?? [];
    }

    public static function statusMeta(string $status): array
    {
        $normalized = self::normalizedStatus($status);

        return match ($normalized) {
            self::STATUS_PENDING => [
                'label' => 'Pending',
                'class' => 'bg-amber-100 text-amber-800 border border-amber-200',
            ],
            self::STATUS_PROCESSING => [
                'label' => 'Processing',
                'class' => 'bg-blue-100 text-blue-800 border border-blue-200',
            ],
            self::STATUS_SHIPPED => [
                'label' => 'Shipped',
                'class' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
            ],
            self::STATUS_DELIVERED => [
                'label' => 'Delivered',
                'class' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            ],
            self::STATUS_CANCELLED => [
                'label' => 'Cancelled',
                'class' => 'bg-rose-100 text-rose-800 border border-rose-200',
            ],
            default => [
                'label' => ucfirst(str_replace('_', ' ', $normalized)),
                'class' => 'bg-slate-100 text-slate-700 border border-slate-200',
            ],
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }
}
