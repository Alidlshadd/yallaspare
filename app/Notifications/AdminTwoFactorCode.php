<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminTwoFactorCode extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly int $ttlMinutes
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('YallaSpare admin verification code')
            ->line('Use this code to complete your admin sign-in.')
            ->line($this->code)
            ->line('This code expires in ' . $this->ttlMinutes . ' minutes.');
    }
}
