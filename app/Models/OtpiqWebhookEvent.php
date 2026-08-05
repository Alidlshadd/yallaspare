<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpiqWebhookEvent extends Model
{
    use HasFactory;

    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'event_id',
        'event_type',
        'attempt_number',
        'webhook_timestamp',
        'signature_verified',
        'processing_status',
        'sender_phone',
        'sender_name',
        'external_message_id',
        'message_type',
        'message_text',
        'raw_payload',
        'error_message',
        'received_at',
        'processed_at',
        'read_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'signature_verified' => 'boolean',
            'attempt_number' => 'integer',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeProcessed(Builder $query): Builder
    {
        return $query->where('processing_status', self::STATUS_PROCESSED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('processing_status', self::STATUS_FAILED);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
