<?php

namespace App\Support;

use Illuminate\Http\Request;

class VerificationRateLimit
{
    private const SESSION_KEY = 'verification.rate_limited_until';

    public static function remember(Request $request, int $retryAfterSeconds): void
    {
        $request->session()->put(
            self::SESSION_KEY,
            now()->addSeconds(max(1, $retryAfterSeconds))->timestamp,
        );
    }

    public static function remainingSeconds(Request $request): int
    {
        $until = (int) $request->session()->get(self::SESSION_KEY, 0);
        $remaining = max(0, $until - now()->timestamp);

        if ($remaining === 0 && $until > 0) {
            $request->session()->forget(self::SESSION_KEY);
        }

        return $remaining;
    }

    public static function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
