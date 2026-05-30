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
            $fail(__('validation.phone'));

            return;
        }

        $normalized = User::normalizePhone((string) $value);
        $length = $normalized !== null ? strlen($normalized) : 0;

        if ($length < self::MIN_DIGITS || $length > self::MAX_DIGITS) {
            $fail(__('validation.phone'));
        }
    }
}
