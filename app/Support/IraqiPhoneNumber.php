<?php

namespace App\Support;

use App\Models\User;

class IraqiPhoneNumber
{
    public static function toE164(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $digits = User::normalizePhone((string) $value);

        if ($digits === null) {
            return null;
        }

        if (str_starts_with($digits, '00964')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '964'.substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '7')) {
            $digits = '964'.$digits;
        }

        if (! preg_match('/^9647\d{9}$/', $digits)) {
            return null;
        }

        return '+'.$digits;
    }

    public static function digits(mixed $value): ?string
    {
        $e164 = self::toE164($value);

        return $e164 !== null ? substr($e164, 1) : null;
    }
}
