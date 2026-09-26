<?php

namespace App\Notifications;

use App\Services\Email\EmailTemplateOverrides;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminTwoFactorCode extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly int $ttlMinutes
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $viewData = [
            'title' => __('Admin verification required'),
            'preheader' => __('Use your one-time admin verification code to continue signing in.'),
            'code' => $this->code,
            'ttlMinutes' => $this->ttlMinutes,
            'email' => (string) ($notifiable->email ?? ''),
            'intro' => __('Use this code to complete your admin sign-in.'),
        ];

        $templateVars = [
            'brand' => 'YallaSpare',
            'name' => (string) ($notifiable->name ?? ''),
            'email' => (string) ($notifiable->email ?? ''),
            'code' => $this->code,
            'expires' => $this->ttlMinutes,
        ];
        $overrides = app(EmailTemplateOverrides::class);
        $viewData = $overrides->viewData('two-factor-code', $templateVars) + $viewData;

        return (new MailMessage)
            ->subject($overrides->subject('two-factor-code', __('YallaSpare admin verification code'), $templateVars))
            ->line(__('Use this code to complete your admin sign-in.'))
            ->line($this->code)
            ->line(__('This code expires in :count minutes.', ['count' => $this->ttlMinutes]))
            ->view('emails.admin.two-factor-code', $viewData)
            ->text('emails.text.generic', $viewData);
    }
}
