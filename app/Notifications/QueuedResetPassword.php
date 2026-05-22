<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Password reset emails use the mail queue in production so a temporary
     * SMTP delay does not hold the user-facing request open.
     */
    public function __construct(string $token)
    {
        parent::__construct($token);

        $this->onQueue('mail');
    }
}
