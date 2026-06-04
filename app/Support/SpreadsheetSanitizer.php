<?php

namespace App\Support;

class SpreadsheetSanitizer
{
    public static function cell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = preg_replace('/[\r\n]+/', ' ', $value) ?? $value;
        if ($value === '') {
            return $value;
        }

        $first = $value[0];
        if ($first === "\t" || in_array($first, ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    public static function row(array $row): array
    {
        return array_map(static fn (mixed $value): mixed => self::cell($value), $row);
    }
}
