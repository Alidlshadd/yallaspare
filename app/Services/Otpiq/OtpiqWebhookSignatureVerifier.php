<?php

namespace App\Services\Otpiq;

class OtpiqWebhookSignatureVerifier
{
    public function isConfigured(): bool
    {
        return trim((string) config('services.otpiq.webhook_secret')) !== '';
    }

    public function verify(string $rawBody, string $timestamp, string $providedSignature): bool
    {
        $secret = (string) config('services.otpiq.webhook_secret');

        if (trim($secret) === '') {
            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac(
            'sha256',
            $timestamp.'.'.$rawBody,
            $secret
        );

        return hash_equals($expectedSignature, $providedSignature);
    }
}
