<?php

namespace App\Models\Concerns;

use App\Support\VehicleFilterCache;

/**
 * Flushes the storefront vehicle filter cache whenever the model changes,
 * so admin edits to vehicle data are visible on the next request.
 */
trait FlushesVehicleFilterCache
{
    public static function bootFlushesVehicleFilterCache(): void
    {
        $flush = static function (): void {
            VehicleFilterCache::flush();
        };

        static::saved($flush);
        static::deleted($flush);
    }
}
