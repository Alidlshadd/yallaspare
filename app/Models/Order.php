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
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal_amount',
        'shipping_fee',
        'discount_amount',
        'coupon_id',
        'coupon_code',
        'payment_method',
        'payment_reference',
        'delivery_address',
        'delivery_city',
        'delivery_phone',
        'notes',
        'cancellation_requested_at',
        'cancellation_reason',
        'archived_at',
    ];

    /**
     * Money/status fields excluded from $fillable to block mass-assignment fraud
     * (e.g. forcing grand_total=0 or payment_status=paid). Set these only via
     * explicit forceFill()->save() with server-computed/allowlisted values.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'grand_total',
        'total_amount',
        'status',
        'payment_status',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cancellation_requested_at' => 'datetime',
        'archived_at' => 'datetime',
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

    public static function allowedPaymentStatuses(): array
    {
        return [
            self::PAYMENT_PENDING,
            self::PAYMENT_PAID,
            self::PAYMENT_FAILED,
            self::PAYMENT_REFUNDED,
        ];
    }

    public static function normalizedPaymentStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return in_array($normalized, self::allowedPaymentStatuses(), true)
            ? $normalized
            : self::PAYMENT_PENDING;
    }

    public static function paymentStatusMeta(?string $status): array
    {
        $normalized = self::normalizedPaymentStatus($status);

        return match ($normalized) {
            self::PAYMENT_PAID => [
                'label' => __('Paid'),
                'class' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            ],
            self::PAYMENT_FAILED => [
                'label' => __('Failed'),
                'class' => 'bg-rose-100 text-rose-800 border border-rose-200',
            ],
            self::PAYMENT_REFUNDED => [
                'label' => __('Refunded'),
                'class' => 'bg-slate-100 text-slate-700 border border-slate-200',
            ],
            default => [
                'label' => __('Pending'),
                'class' => 'bg-amber-100 text-amber-800 border border-amber-200',
            ],
        };
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
                'label' => __('Pending'),
                'class' => 'bg-amber-100 text-amber-800 border border-amber-200',
            ],
            self::STATUS_PROCESSING => [
                'label' => __('Processing'),
                'class' => 'bg-blue-100 text-blue-800 border border-blue-200',
            ],
            self::STATUS_SHIPPED => [
                'label' => __('Shipped'),
                'class' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
            ],
            self::STATUS_DELIVERED => [
                'label' => __('Delivered'),
                'class' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            ],
            self::STATUS_CANCELLED => [
                'label' => __('Cancelled'),
                'class' => 'bg-rose-100 text-rose-800 border border-rose-200',
            ],
            default => [
                'label' => __(ucfirst(str_replace('_', ' ', $normalized))),
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

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function adminNotes()
    {
        return $this->hasMany(OrderAdminNote::class)->latest('id');
    }

    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class)->latest('id');
    }
}
