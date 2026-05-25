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

        return (new MailMessage)
            ->subject('Verify your YallaSpare email address')
            ->greeting('Welcome to YallaSpare')
            ->line('Please confirm your email address so we can protect your account and unlock checkout, orders, saved addresses, and account settings.')
            ->action('Verify email address', $verificationUrl)
            ->line('This verification link expires in ' . $expiresIn . ' minutes.')
            ->line('If you did not create a YallaSpare account, you can ignore this email.');
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
