<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class ImmediateVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $expiresIn = (int) config('auth.verification.expire', 60);
        $email = (string) $notifiable->getEmailForVerification();

        return (new MailMessage)
            ->subject(__('Verify your YallaSpare email address'))
            ->greeting(__('Welcome to YallaSpare'))
            ->line(__('Please confirm your email address so we can protect your account and unlock checkout, orders, saved addresses, and account settings.'))
            ->action(__('Verify email address'), $verificationUrl)
            ->line(__('This verification link expires in :count minutes.', ['count' => $expiresIn]))
            ->line(__('If you did not create a YallaSpare account, you can ignore this email.'))
            ->view([
                'html' => 'emails.auth.verify-email',
                'text' => 'emails.text.generic',
            ], [
                'title' => __('Verify your email address'),
                'preheader' => __('Confirm your YallaSpare email address to protect your account.'),
                'email' => $email,
                'expiresIn' => $expiresIn,
                'actionUrl' => $verificationUrl,
                'actionText' => __('Verify email address'),
            ]);
    }

    protected function verificationUrl($notifiable): string
    {
        $relativeUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
            false
        );

        return rtrim($this->verificationBaseUrl(), '/') . $relativeUrl;
    }

    private function verificationBaseUrl(): string
    {
        $request = request();
        $httpHost = $request?->getHttpHost();

        if (is_string($httpHost) && $httpHost !== '') {
            $host = strtolower((string) $request->getHost());
            $scheme = $this->isLocalHost($host) ? $request->getScheme() : 'https';

            return $scheme . '://' . $httpHost;
        }

        return (string) config('app.url', 'https://yallaspare.com');
    }

    private function isLocalHost(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
