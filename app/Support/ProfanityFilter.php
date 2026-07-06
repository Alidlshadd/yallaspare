<?php

namespace App\Support;

class ProfanityFilter
{
    /**
     * Mask profane words with asterisks.
     *
     * @return array{0: string|null, 1: bool} masked text and whether anything matched
     */
    public static function clean(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [$text, false];
        }

        $matched = false;
        $masked = $text;

        foreach (self::patterns() as $pattern) {
            $masked = (string) preg_replace_callback($pattern, function (array $hit) use (&$matched): string {
                $matched = true;

                return str_repeat('*', mb_strlen($hit[0]));
            }, $masked);
        }

        return [$masked, $matched];
    }

    public static function contains(?string $text): bool
    {
        [, $matched] = self::clean($text);

        return $matched;
    }

    /**
     * @return list<string>
     */
    private static function patterns(): array
    {
        static $patterns = null;

        if ($patterns !== null) {
            return $patterns;
        }

        $patterns = collect(config('profanity.words', []))
            ->flatten()
            ->map(fn ($word) => trim((string) $word))
            ->filter()
            ->unique()
            // Whole-word match bounded by non-letters, so list entries never
            // fire inside longer legitimate words. Unicode-aware for AR/KU.
            ->map(fn (string $word) => '/(?<!\p{L})' . preg_quote($word, '/') . '(?!\p{L})/iu')
            ->values()
            ->all();

        return $patterns;
    }
}
