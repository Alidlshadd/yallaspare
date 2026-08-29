<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache for the catalogue landing indexes — the list of part brands and the
 * list of makes, each with how much of the catalogue sits behind it.
 *
 * Both are counting queries over the whole catalogue rendered on pages meant to
 * receive search traffic, so they are the wrong thing to recompute per visitor.
 * The TTL is a safety net only: the models behind them flush this cache when
 * they change, the way [[VehicleFilterCache]] does for the shop filters.
 */
final class CatalogLandingCache
{
    public const KEY = 'catalog_landing';

    public const TTL_SECONDS = 900;

    private const SECTIONS = ['brands', 'vehicle-makes'];

    private const LOCALES = ['en', 'ar', 'ku'];

    /**
     * @param  Closure(): array<int, mixed>  $callback
     * @return array<int, mixed>
     */
    public static function remember(string $section, Closure $callback): array
    {
        return Cache::remember(self::keyFor($section), self::TTL_SECONDS, $callback);
    }

    public static function flush(): void
    {
        foreach (self::SECTIONS as $section) {
            foreach (self::LOCALES as $locale) {
                Cache::forget(self::keyFor($section, $locale));
            }
        }
    }

    public static function keyFor(string $section, ?string $locale = null): string
    {
        return self::KEY.'.'.$section.'.'.self::normalizedLocale($locale);
    }

    private static function normalizedLocale(?string $locale = null): string
    {
        $locale = strtolower($locale ?: app()->getLocale());

        return match (true) {
            str_starts_with($locale, 'ar') => 'ar',
            str_starts_with($locale, 'ku'), str_starts_with($locale, 'ckb') => 'ku',
            default => 'en',
        };
    }
}
