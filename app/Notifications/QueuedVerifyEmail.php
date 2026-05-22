<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Email verification links are queued through the same mail worker used
     * for order and support mail.
     */
    public function __construct()
    {
        $this->onQueue('mail');
    }
}
