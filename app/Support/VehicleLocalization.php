<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class VehicleLocalization
{
    public static function name(Model $vehicle, ?string $locale = null): string
    {
        $locale = self::locale($locale);
        $field = match ($locale) {
            'ar' => 'name_ar',
            'ku' => 'name_ku',
            default => 'name_en',
        };

        return LocalizedText::first(
            $vehicle->getAttribute($field),
            $vehicle->getAttribute('name_en'),
            $vehicle->getAttribute('name'),
            $vehicle->getAttribute('name_ar'),
            $vehicle->getAttribute('name_ku'),
            __('Vehicle'),
        );
    }

    /** @return array{name_en: string, name_ar: ?string, name_ku: ?string} */
    public static function names(Model $vehicle): array
    {
        return [
            'name_en' => LocalizedText::first($vehicle->getAttribute('name_en'), $vehicle->getAttribute('name')),
            'name_ar' => LocalizedText::nullable($vehicle->getAttribute('name_ar')),
            'name_ku' => LocalizedText::nullable($vehicle->getAttribute('name_ku')),
        ];
    }

    public static function engine(?string $engine, ?string $locale = null): string
    {
        $label = trim((string) $engine);
        if ($label === '') {
            return '';
        }

        // Free-text engines are still translated word by word. The fuel words
        // come from VehicleFuelType so a label is never defined in two places.
        $fuelLabels = VehicleFuelType::labels($locale);
        $terms = [
            'Turbo' => self::aspiration('turbo', $locale),
            'Petrol' => $fuelLabels[VehicleFuelType::PETROL],
            'Diesel' => $fuelLabels[VehicleFuelType::DIESEL],
            'Hybrid' => $fuelLabels[VehicleFuelType::HYBRID],
            'Electric' => $fuelLabels[VehicleFuelType::ELECTRIC],
        ];

        return (string) preg_replace_callback(
            '/\b(Turbo|Petrol|Diesel|Hybrid|Electric)\b/iu',
            fn (array $match): string => $terms[ucfirst(mb_strtolower($match[1]))] ?? $match[0],
            $label,
        );
    }

    /**
     * How a forced-induction engine is written in each language. Kept beside the
     * other vehicle wording so a translation lives in one place.
     */
    public static function aspiration(?string $aspiration, ?string $locale = null): string
    {
        $value = strtolower(trim((string) $aspiration));

        if ($value === '' || $value === 'na' || $value === 'naturally_aspirated') {
            return '';
        }

        return match (self::normalizedLocale($locale)) {
            'ar' => 'تيربو',
            'ku' => 'تۆربۆ',
            default => 'Turbo',
        };
    }

    /**
     * Public so sibling support classes resolve the locale exactly the same way
     * rather than each rolling its own fallback chain.
     */
    public static function normalizedLocale(?string $locale = null): string
    {
        return self::locale($locale);
    }

    private static function locale(?string $locale): string
    {
        $locale = strtolower($locale ?: app()->getLocale());
        $primary = explode('-', str_replace('_', '-', $locale))[0];

        return $primary === 'ckb' ? 'ku' : (in_array($primary, ['en', 'ar', 'ku'], true) ? $primary : 'en');
    }
}
