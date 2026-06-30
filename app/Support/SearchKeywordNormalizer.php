<?php

namespace App\Support;

class SearchKeywordNormalizer
{
    public const MAX_LENGTH = 80;
    public const MIN_LENGTH = 2;

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $raw) ?? '';
        $clean = mb_strtolower($clean, 'UTF-8');
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? '';
        $clean = trim($clean);

        if ($clean === '' || mb_strlen($clean) < self::MIN_LENGTH) {
            return null;
        }

        if (preg_match('/^[\W\d_]+$/u', $clean) === 1) {
            return null;
        }

        return mb_substr($clean, 0, self::MAX_LENGTH);
    }
}
