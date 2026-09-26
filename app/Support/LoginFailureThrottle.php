<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Failed sign-ins counted per address, across every account tried.
 *
 * The per-account limit (5 per email and IP) stops guessing one password,
 * but not one password tried against hundreds of accounts from one address.
 * Only failures count, and a success does not reset the tally, so a shared
 * carrier-grade NAT address full of people signing in normally never gets
 * near the ceiling while a spraying script does.
 */
class LoginFailureThrottle
{
    public const MAX_FAILURES = 30;

    public const DECAY_SECONDS = 900;

    public static function tooMany(?string $ip): bool
    {
        return RateLimiter::tooManyAttempts(self::key($ip), self::MAX_FAILURES);
    }

    public static function availableIn(?string $ip): int
    {
        return RateLimiter::availableIn(self::key($ip));
    }

    public static function recordFailure(?string $ip): void
    {
        RateLimiter::hit(self::key($ip), self::DECAY_SECONDS);
    }

    private static function key(?string $ip): string
    {
        return 'login-failures:'.sha1((string) $ip);
    }
}
