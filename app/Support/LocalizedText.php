<?php

namespace App\Support;

class LocalizedText
{
    public static function first(mixed ...$values): string
    {
        foreach ($values as $value) {
            $text = trim((string) $value);

            if (self::isReadable($text)) {
                return $text;
            }
        }

        return '';
    }

    public static function nullable(mixed ...$values): ?string
    {
        $text = self::first(...$values);

        return $text !== '' ? $text : null;
    }

    public static function isReadable(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        if (preg_match('/^[\s\?\x{FFFD}]+$/u', $text) === 1) {
            return false;
        }

        return true;
    }
}
