<?php

namespace App\Support;

/**
 * The canonical fuel types a vehicle engine can have.
 *
 * The stored value never changes with locale — only the label does. Keeping the
 * list here means validation, the admin form, the storefront and the API all
 * agree on what is allowed without repeating the strings.
 */
class VehicleFuelType
{
    public const PETROL = 'petrol';

    public const DIESEL = 'diesel';

    public const HYBRID = 'hybrid';

    public const ELECTRIC = 'electric';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::PETROL, self::DIESEL, self::HYBRID, self::ELECTRIC];
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && in_array($value, self::all(), true);
    }

    /**
     * An electric drivetrain has no displacement or aspiration, so the form must
     * not demand them and the display must not invent them.
     */
    public static function hasDisplacement(?string $fuelType): bool
    {
        return $fuelType !== self::ELECTRIC;
    }

    public static function label(?string $fuelType, ?string $locale = null): string
    {
        if (! self::isValid($fuelType)) {
            return '';
        }

        return self::labels($locale)[$fuelType];
    }

    /**
     * Labels are held here rather than in the JSON translation files because
     * they belong to a fixed domain list, not to page copy — a missing key would
     * silently print an empty fuel type on an invoice or a product page.
     *
     * @return array<string, string>
     */
    public static function labels(?string $locale = null): array
    {
        return match (VehicleLocalization::normalizedLocale($locale)) {
            'ar' => [
                self::PETROL => 'بنزين',
                self::DIESEL => 'ديزل',
                self::HYBRID => 'هجين',
                self::ELECTRIC => 'كهربائي',
            ],
            'ku' => [
                self::PETROL => 'بەنزین',
                self::DIESEL => 'دیزڵ',
                self::HYBRID => 'هایبرێد',
                self::ELECTRIC => 'کارەبایی',
            ],
            default => [
                self::PETROL => 'Petrol',
                self::DIESEL => 'Diesel',
                self::HYBRID => 'Hybrid',
                self::ELECTRIC => 'Electric',
            ],
        };
    }

    /**
     * Read the parts back out of free text like "2.0 Turbo Petrol".
     *
     * Engines were entered as plain strings for a long time, and the admin form
     * still offers a paste-a-list field. Parsing keeps those entries searchable
     * instead of leaving them with an empty fuel type.
     *
     * @return array{fuel_type: string|null, engine_size: float|null, aspiration: string|null}
     */
    public static function parse(?string $text): array
    {
        $value = trim((string) $text);

        $fuelType = null;
        foreach ([
            self::ELECTRIC => '\b(electric|ev|kwh)\b',
            self::HYBRID => '\b(hybrid|hev|phev)\b',
            self::DIESEL => '\b(diesel|tdi|crdi|xdi|hdi|dci)\b',
            self::PETROL => '\b(petrol|gasoline|benzin|tsi|gdi|vvt)\b',
        ] as $candidate => $pattern) {
            if (preg_match('/'.$pattern.'/iu', $value) === 1) {
                $fuelType = $candidate;
                break;
            }
        }

        $engineSize = null;
        if ($fuelType !== self::ELECTRIC && preg_match('/(\d+(?:[.,]\d+)?)\s*(?:l|litre|liter)?\b/iu', $value, $match) === 1) {
            $size = (float) str_replace(',', '.', $match[1]);
            // Displacement in litres; anything larger is a year or a trim number.
            $engineSize = ($size > 0 && $size < 10) ? $size : null;
        }

        $aspiration = null;
        if ($fuelType !== self::ELECTRIC && preg_match('/\b(turbo|tsi|tdi|crdi|hdi|dci)\b/iu', $value) === 1) {
            $aspiration = 'turbo';
        }

        return ['fuel_type' => $fuelType, 'engine_size' => $engineSize, 'aspiration' => $aspiration];
    }

    /**
     * Displacement as engines are actually written: one decimal, always.
     *
     * This used to trim the trailing zero, so a 2.0 stored in the column came
     * out as a bare "2" and a customer reading "2 Turbo Petrol" could not tell
     * which engine was meant. The stored number is unchanged — only how it is
     * written down.
     *
     * An electric drivetrain has no displacement, so it gets none here.
     */
    public static function displacement(int|float|string|null $engineSize, ?string $fuelType = null): string
    {
        if ($engineSize === null || $engineSize === '' || ! self::hasDisplacement($fuelType)) {
            return '';
        }

        return number_format((float) $engineSize, 1, '.', '');
    }

    /**
     * Build the display string for an engine from its structured parts, so the
     * same engine reads correctly in every locale without storing three copies.
     *
     * 2.0 + turbo + petrol  ->  "2.0 Turbo Petrol" / "2.0 تيربو بنزين"
     * electric              ->  "Electric"
     */
    public static function displayName(
        ?string $fuelType,
        int|float|string|null $engineSize = null,
        ?string $aspiration = null,
        ?string $locale = null,
    ): string {
        $parts = [];

        $displacement = self::displacement($engineSize, $fuelType);
        if ($displacement !== '') {
            $parts[] = $displacement;
        }

        if ($aspiration !== null && trim($aspiration) !== '' && self::hasDisplacement($fuelType)) {
            $parts[] = VehicleLocalization::aspiration($aspiration, $locale);
        }

        $label = self::label($fuelType, $locale);
        if ($label !== '') {
            $parts[] = $label;
        }

        return trim(implode(' ', array_filter($parts, fn ($part) => $part !== '')));
    }
}
