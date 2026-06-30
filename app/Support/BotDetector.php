<?php

namespace App\Support;

class BotDetector
{
    private const PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp',
        'bingpreview', 'facebookexternalhit',
        'headlesschrome', 'lighthouse', 'mediapartners-google',
    ];

    public static function isBot(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }

        $needle = strtolower($userAgent);
        foreach (self::PATTERNS as $pattern) {
            if (str_contains($needle, $pattern)) {
                return true;
            }
        }
        return false;
    }
}
