<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Allowlist validation for admin-entered link URLs rendered as href targets:
 * only http/https absolute URLs or single-slash relative paths pass. Control
 * characters are stripped first so a scheme like "java\tscript:" cannot be
 * smuggled past the check. Empty values are allowed — combine with
 * "required" when the link is mandatory.
 */
class SafeLinkUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = self::sanitize($value);

        if ($url === '') {
            return;
        }

        // Relative path (but not scheme-relative //host).
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            $fail(__('This link type is not allowed.'));
        }
    }

    /**
     * Normalization applied both before validation and before persisting, so
     * the stored value can never contain a smuggled scheme.
     */
    public static function sanitize(mixed $value): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]/', '', (string) $value));
    }
}
