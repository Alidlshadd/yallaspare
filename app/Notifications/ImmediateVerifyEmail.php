<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class ImmediateVerifyEmail extends VerifyEmail
{
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
