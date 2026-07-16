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
        return Cache::remember(self::KEY, self::TTL_SECONDS, $callback);
    }

    public static function flush(): void
    {
        Cache::forget(self::KEY);
    }
}
