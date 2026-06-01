<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserTwoFactorCode extends Notification
{
    public function __construct(
        public readonly string $code,
        public readonly int $ttlMinutes
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $viewData = [
            'title' => __('Two-factor verification required'),
            'preheader' => __('Use your one-time verification code to continue signing in.'),
            'code' => $this->code,
            'ttlMinutes' => $this->ttlMinutes,
            'email' => (string) ($notifiable->email ?? ''),
            'intro' => __('Use this code to complete your sign-in.'),
        ];

        return (new MailMessage)
            ->subject(__('YallaSpare verification code'))
            ->line(__('Use this code to complete your sign-in.'))
            ->line($this->code)
            ->line(__('This code expires in :count minutes.', ['count' => $this->ttlMinutes]))
            ->view('emails.admin.two-factor-code', $viewData)
            ->text('emails.text.generic', $viewData);
    }
}
