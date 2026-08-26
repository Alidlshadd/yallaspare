<?php

namespace App\Services\Payments;

use Illuminate\Support\Str;

class PaymentPayloadSanitizer
{
    /** @var array<int, string> */
    private const BLOCKED_KEY_PARTS = [
        'authorization',
        'authentication',
        'access_token',
        'refresh_token',
        'api_token',
        'api_key',
        'token',
        'secret',
        'password',
        'credential',
        'card',
        'pan',
        'cvv',
        'cvc',
        'pin',
        'qrcode',
        'qr_code',
        'customer',
        'email',
        'phone',
        'address',
    ];

    /** @return array<string|int, mixed> */
    public function sanitize(array $payload): array
    {
        return $this->sanitizeValue($payload);
    }

    public function text(mixed $value, int $limit = 1000): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', trim((string) $value)) ?? '';

        foreach ($this->configuredSecrets() as $secret) {
            $text = str_replace($secret, '[redacted]', $text);
        }

        return $text !== '' ? Str::limit($text, $limit, '…') : null;
    }

    private function isBlockedKey(string|int|null $key): bool
    {
        $key = strtolower((string) $key);

        foreach (self::BLOCKED_KEY_PARTS as $blocked) {
            if ($key !== '' && str_contains($key, $blocked)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeValue(mixed $value, string|int|null $key = null): mixed
    {
        if ($this->isBlockedKey($key)) {
            return '[redacted]';
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $childKey => $childValue) {
                $clean[$childKey] = $this->sanitizeValue($childValue, $childKey);
            }

            return $clean;
        }

        if (is_string($value)) {
            return $this->text($value, 4000);
        }

        return is_scalar($value) || $value === null ? $value : '[unsupported]';
    }

    /** @return array<int, string> */
    private function configuredSecrets(): array
    {
        return collect([
            config('services.wayl.token'),
            config('services.wayl.webhook_secret'),
            config('services.fib.client_secret'),
            config('services.zaincash.secret'),
        ])
            ->filter(fn (mixed $value): bool => is_string($value) && strlen(trim($value)) >= 6)
            ->map(fn (string $value): string => trim($value))
            ->values()
            ->all();
    }
}
