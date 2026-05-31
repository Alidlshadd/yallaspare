<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailBroadcast extends Model
{
    use HasFactory;

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_ROLE = 'role';
    public const AUDIENCE_USER = 'user';

    public const PURPOSE_PROMOTIONAL = 'promotional';
    public const PURPOSE_OPERATIONAL = 'operational';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'admin_id',
        'target_user_id',
        'audience_type',
        'audience_role',
        'purpose',
        'subject',
        'message',
        'action_url',
        'action_text',
        'status',
        'recipient_count',
        'sent_count',
        'failed_count',
        'last_error',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
