<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class EmailVerificationCode
{
    public static function generateFor(User $user): string
    {
        $code = (string) random_int(100000, 999999);

        Cache::put(self::keyFor($user), [
            'email' => (string) $user->getEmailForVerification(),
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(self::expiresIn()));

        return $code;
    }

    public static function verifyFor(User $user, string $code): bool
    {
        $payload = Cache::get(self::keyFor($user));

        if (! is_array($payload) || empty($payload['hash'])) {
            return false;
        }

        if ((string) ($payload['email'] ?? '') !== (string) $user->getEmailForVerification()) {
            return false;
        }

        if (! Hash::check(self::normalize($code), (string) $payload['hash'])) {
            $attempts = ((int) ($payload['attempts'] ?? 0)) + 1;

            if ($attempts >= self::maxAttempts()) {
                self::forgetFor($user);

                return false;
            }

            $payload['attempts'] = $attempts;
            Cache::put(self::keyFor($user), $payload, now()->addMinutes(self::expiresIn()));

            return false;
        }

        self::forgetFor($user);

        return true;
    }

    public static function forgetFor(User $user): void
    {
        Cache::forget(self::keyFor($user));
    }

    public static function normalize(string $code): string
    {
        return preg_replace('/\D+/', '', $code) ?? '';
    }

    public static function expiresIn(): int
    {
        return max(1, (int) config('auth.verification.expire', 60));
    }

    public static function maxAttempts(): int
    {
        return max(1, (int) config('security.email_verification.max_attempts', 5));
    }

    private static function keyFor(User $user): string
    {
        return 'email-verification-code:' . $user->getKey() . ':' . sha1((string) $user->getEmailForVerification());
    }
}
