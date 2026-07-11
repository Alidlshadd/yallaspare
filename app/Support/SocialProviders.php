<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class SocialProviders
{
    /**
     * Remembers whether the Apple private-key warning was already logged in
     * this request, so a page rendering the button twice logs once.
     */
    private static bool $warnedUnreadableAppleKey = false;

    public static function anyEnabled(): bool
    {
        return self::googleEnabled() || self::appleEnabled();
    }

    public static function googleEnabled(): bool
    {
        if (! self::socialLoginVisible() || ! config('services.google.enabled')) {
            return false;
        }

        $config = (array) config('services.google', []);

        return filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['redirect'] ?? null);
    }

    public static function appleEnabled(): bool
    {
        if (! self::socialLoginVisible() || ! config('services.apple.enabled')) {
            return false;
        }

        $config = (array) config('services.apple', []);

        if (! filled($config['client_id'] ?? null) || ! filled($config['redirect'] ?? null)) {
            return false;
        }

        // Apple accepts either a pre-generated client secret JWT or the
        // key material (team id + key id + private key) to build one.
        if (filled($config['client_secret'] ?? null)) {
            return true;
        }

        if (! filled($config['team_id'] ?? null) || ! filled($config['key_id'] ?? null)) {
            return false;
        }

        return self::applePrivateKeyUsable((string) ($config['private_key'] ?? ''));
    }

    private static function socialLoginVisible(): bool
    {
        return (bool) config('services.social_login.visible', false);
    }

    private static function applePrivateKeyUsable(string $privateKey): bool
    {
        if (trim($privateKey) === '') {
            return false;
        }

        // The provider accepts either the literal PEM contents or a path.
        if (str_contains($privateKey, '-----BEGIN')) {
            return true;
        }

        if (is_file($privateKey) && is_readable($privateKey)) {
            return true;
        }

        if (! self::$warnedUnreadableAppleKey) {
            self::$warnedUnreadableAppleKey = true;
            Log::warning('Apple sign-in disabled: the configured private key file is missing or not readable.');
        }

        return false;
    }
}
