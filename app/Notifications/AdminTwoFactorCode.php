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
            ->subject(__('YallaSpare admin verification code'))
            ->line(__('Use this code to complete your admin sign-in.'))
            ->line($this->code)
            ->line(__('This code expires in :count minutes.', ['count' => $this->ttlMinutes]))
            ->view([
                'html' => 'emails.admin.two-factor-code',
                'text' => 'emails.text.generic',
            ], [
                'title' => __('Admin verification required'),
                'preheader' => __('Use your one-time admin verification code to continue signing in.'),
                'code' => $this->code,
                'ttlMinutes' => $this->ttlMinutes,
                'email' => (string) ($notifiable->email ?? ''),
                'intro' => __('Use this code to complete your admin sign-in.'),
            ]);
    }
}
