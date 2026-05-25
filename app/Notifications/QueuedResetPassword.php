<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

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

    public function toMail($notifiable): MailMessage
    {
        return $this->buildMailMessage($this->resetUrl($notifiable), (string) $notifiable->getEmailForPasswordReset());
    }

    protected function buildMailMessage($url, string $email = ''): MailMessage
    {
        $expiresIn = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject(__('Reset your YallaSpare password'))
            ->line(__('We received a request to reset the password for your YallaSpare account. Use the secure button below to continue.'))
            ->action(__('Reset Password'), $url)
            ->line(__('This password reset link will expire in :count minutes.', ['count' => $expiresIn]))
            ->line(__('If you did not request a password reset, no further action is required.'))
            ->view([
                'html' => 'emails.auth.reset-password',
                'text' => 'emails.text.generic',
            ], [
                'title' => __('Reset your password'),
                'preheader' => __('A secure password reset link was requested for your YallaSpare account.'),
                'email' => $email,
                'expiresIn' => $expiresIn,
                'actionUrl' => $url,
                'actionText' => __('Reset Password'),
                'intro' => __('We received a request to reset the password for your YallaSpare account. Use the secure button below to continue.'),
            ]);
    }
}
