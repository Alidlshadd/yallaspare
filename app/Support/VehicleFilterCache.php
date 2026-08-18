<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache for the storefront vehicle filter options (brands, models, engines).
 *
 * The TTL is only a safety net: models feeding the options (VehicleBrand,
 * VehicleModel, ProductVehicleFitment, and Product brand/compatible_models
 * changes) flush this cache from their model events, so admin edits show up
 * on the very next request.
 */
final class VehicleFilterCache
{
    public const KEY = 'shop_vehicle_filter_options';

    public const TTL_SECONDS = 600;

    public static function remember(Closure $callback): array
    {
        return Cache::remember(self::keyForLocale(), self::TTL_SECONDS, $callback);
    }

    public static function flush(): void
    {
        foreach (['en', 'ar', 'ku'] as $locale) {
            Cache::forget(self::keyForLocale($locale));
        }
    }

    public static function keyForLocale(?string $locale = null): string
    {
        $locale = strtolower($locale ?: app()->getLocale());
        $locale = str_starts_with($locale, 'ar') ? 'ar' : (str_starts_with($locale, 'ku') || str_starts_with($locale, 'ckb') ? 'ku' : 'en');

        return $locale === 'en' ? self::KEY : self::KEY.'.'.$locale;
    }
}
