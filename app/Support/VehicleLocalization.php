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

        $terms = match (self::locale($locale)) {
            'ar' => ['Turbo' => 'تيربو', 'Petrol' => 'بنزين'],
            'ku' => ['Turbo' => 'تۆربۆ', 'Petrol' => 'بەنزین'],
            default => ['Turbo' => 'Turbo', 'Petrol' => 'Petrol'],
        };

        return (string) preg_replace_callback(
            '/\b(Turbo|Petrol)\b/iu',
            fn (array $match): string => $terms[ucfirst(mb_strtolower($match[1]))] ?? $match[0],
            $label,
        );
    }

    private static function locale(?string $locale): string
    {
        $locale = strtolower($locale ?: app()->getLocale());
        $primary = explode('-', str_replace('_', '-', $locale))[0];

        return $primary === 'ckb' ? 'ku' : (in_array($primary, ['en', 'ar', 'ku'], true) ? $primary : 'en');
    }
}
