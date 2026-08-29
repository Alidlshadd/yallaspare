<?php

namespace App\Models\Concerns;

use App\Support\CatalogLandingCache;
use App\Support\VehicleFilterCache;

/**
 * Flushes the storefront vehicle caches whenever the model changes, so admin
 * edits to vehicle data are visible on the next request. Both the shop's filter
 * options and the catalogue landing indexes are built from this data.
 */
trait FlushesVehicleFilterCache
{
    public static function bootFlushesVehicleFilterCache(): void
    {
        $flush = static function (): void {
            VehicleFilterCache::flush();
            CatalogLandingCache::flush();
        };

        static::saved($flush);
        static::deleted($flush);
    }
}
