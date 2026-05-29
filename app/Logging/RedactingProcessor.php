<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class RedactingProcessor implements ProcessorInterface
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        '_token',
        'api_token',
        'remember_token',
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'session',
        'csrf',
        'code',
        'verification_code',
        'otp',
        'recovery_code',
        'two_factor_secret',
        'secret',
        'api_key',
        'apikey',
        'private_key',
        'credit_card',
        'card_number',
        'cvv',
        'cvc',
        'pan',
    ];

    private const PATTERNS = [
        '/[\w\.\-\+]+@[\w\.\-]+\.[A-Za-z]{2,}/' => '[email-redacted]',
        '/Bearer\s+[A-Za-z0-9\-_\.=]+/i' => 'Bearer [redacted]',
        '/(?<![A-Za-z0-9])\+?\d[\d\s\-]{8,18}\d(?![A-Za-z0-9])/' => '[phone-redacted]',
        '/\b[a-f0-9]{40,}\b/i' => '[hex-redacted]',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->redactString($record->message),
            context: $this->redactArray($record->context),
            extra: $this->redactArray($record->extra),
        );
    }

    private function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->redactString($value);
            }
        }

        return $data;
    }

    private function redactString(string $value): string
    {
        $result = preg_replace(array_keys(self::PATTERNS), array_values(self::PATTERNS), $value);

        return $result ?? $value;
    }
}
