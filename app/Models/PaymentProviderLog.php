<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProviderLog extends Model
{
    use HasFactory;

    public const EVENT_CREATE_LINK = 'CREATE_LINK';

    public const EVENT_STATUS_CHECK = 'STATUS_CHECK';

    public const EVENT_HEALTH_CHECK = 'HEALTH_CHECK';

    public const EVENT_WEBHOOK_RECEIVED = 'WEBHOOK_RECEIVED';

    public const EVENT_WEBHOOK_REJECTED = 'WEBHOOK_REJECTED';

    protected $fillable = [
        'provider',
        'payment_id',
        'order_id',
        'reference_id',
        'event_type',
        'http_method',
        'endpoint',
        'http_status',
        'result',
        'duration_ms',
        'safe_message',
        'request_metadata',
        'response_metadata',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'duration_ms' => 'integer',
            'request_metadata' => 'array',
            'response_metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
