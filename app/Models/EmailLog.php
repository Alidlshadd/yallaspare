<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'recipient_hash',
        'recipient_domain',
        'subject',
        'mailer',
        'mailable_class',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
