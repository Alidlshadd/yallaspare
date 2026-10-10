<?php

namespace App\Support;

/**
 * The warranty periods a product can carry.
 *
 * The field used to be free text, so the storefront showed whatever was typed
 * — "2 months" in English on an Arabic page. A product now stores one of these
 * codes and every screen prints it in the reader's language.
 *
 * Each period has its own translated sentence rather than a count and a unit:
 * Arabic has a dual and Kurdish does not pluralise after a number, and a short
 * fixed list is simpler to get right than plural rules.
 */
class ProductWarranty
{
    /** Code => the English sentence that is also its translation key. */
    private const OPTIONS = [
        'none' => 'No warranty',
        '1_month' => '1 month',
        '2_months' => '2 months',
        '3_months' => '3 months',
        '6_months' => '6 months',
        '1_year' => '1 year',
        '2_years' => '2 years',
    ];

    /** Months => code, for reading what was typed before there was a list. */
    private const BY_MONTHS = [
        1 => '1_month',
        2 => '2_months',
        3 => '3_months',
        6 => '6_months',
        12 => '1_year',
        24 => '2_years',
    ];

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::OPTIONS);
    }

    /**
     * Code => label in the current language, for a select.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(fn (string $label): string => __($label), self::OPTIONS);
    }

    public static function isCode(?string $value): bool
    {
        return $value !== null && array_key_exists($value, self::OPTIONS);
    }

    /**
     * What to show for a stored value: the translated period for a code, the
     * text itself for anything typed before the list existed, null for none.
     */
    public static function label(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return self::isCode($value) ? __(self::OPTIONS[$value]) : $value;
    }

    /**
     * The code a piece of typed text plainly means — "6 Months", "1 year",
     * "12 month", "no warranty" — or null when it is not one of the periods.
     */
    public static function normalize(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        if (self::isCode($value)) {
            return $value;
        }

        if (in_array($value, ['none', 'no', 'no warranty', 'without warranty'], true)) {
            return 'none';
        }

        if (preg_match('/^(\d{1,2})\s*[-_ ]?\s*(months?|mo|m|years?|yrs?|y)\.?$/', $value, $match) !== 1) {
            return null;
        }

        $months = (int) $match[1] * (str_starts_with($match[2], 'y') ? 12 : 1);

        return self::BY_MONTHS[$months] ?? null;
    }
}
