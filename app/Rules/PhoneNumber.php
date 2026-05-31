<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    public const MIN_DIGITS = 8;

    public const MAX_DIGITS = 15;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_scalar($value)) {
            $fail($this->message());

            return;
        }

        $phone = trim((string) $value);

        if ($phone === '' || ! preg_match('/\A[0-9\p{Nd}+\-\s().]+\z/u', $phone)) {
            $fail($this->message());

            return;
        }

        if (substr_count($phone, '+') > 1 || (str_contains($phone, '+') && ! str_starts_with($phone, '+'))) {
            $fail($this->message());

            return;
        }

        $normalized = User::normalizePhone($phone);
        $length = $normalized !== null ? strlen($normalized) : 0;

        if ($length < self::MIN_DIGITS || $length > self::MAX_DIGITS) {
            $fail($this->message());
        }
    }

    private function message(): string
    {
        return __('validation.phone');
    }
}
