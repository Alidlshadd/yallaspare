<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmailBroadcast extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['subject', 'recipient_count', 'admin_user_id'])
            ->useLogName('email-broadcast');
    }

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'admin_user_id',
        'subject',
        'body_html',
        'attachments',
        'filters_snapshot',
        'recipient_count',
        'batch_id',
    ];

    /**
     * Mutable state — set only via forceFill()->save() in the controller/job.
     * Mirrors the P1 mass-assignment guard pattern used on Order/User.
     */
    protected $guarded = [
        'status',
        'sent_count',
        'failed_count',
        'sent_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'filters_snapshot' => 'array',
        'sent_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailBroadcastRecipient::class, 'broadcast_id');
    }
}
