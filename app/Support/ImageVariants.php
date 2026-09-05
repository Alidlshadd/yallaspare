<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves a stored image to a smaller, pre-generated derivative.
 *
 * Uploads are stored at whatever resolution they arrived in — a 2MB product
 * photo goes into a 300px card. Rather than change the upload path, the
 * `images:variants` command writes resized WebP copies next to the originals
 * and this resolver hands them to the views when they exist. When one does not
 * exist yet, or the file is remote, the original URL is returned unchanged, so
 * a missing variant is never a broken image.
 */
class ImageVariants
{
    /**
     * Widths the command generates and the views may ask for. Anything else
     * falls back to the original: a variant nobody generated is not a variant.
     */
    public const WIDTHS = [400, 800, 1400];

    /** @var array<string, string|null> */
    private static array $memo = [];

    /**
     * @param  string|null  $pathOrUrl  A public-disk path ("products/x.png") or a
     *                                  URL already built with asset('storage/...').
     */
    public static function url(?string $pathOrUrl, int $width): ?string
    {
        $pathOrUrl = trim((string) $pathOrUrl);

        if ($pathOrUrl === '') {
            return null;
        }

        $key = $width.'|'.$pathOrUrl;

        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        return self::$memo[$key] = self::resolve($pathOrUrl, $width);
    }

    /**
     * The public-disk path a variant is written to. Kept out of the source
     * directories so a rescan never treats a variant as an original.
     */
    public static function variantPath(string $path, int $width): string
    {
        $path = ltrim($path, '/');
        $withoutExtension = (string) Str::of($path)->beforeLast('.');

        return 'variants/'.$width.'/'.($withoutExtension === '' ? $path : $withoutExtension).'.webp';
    }

    /**
     * Strips the local storage URL prefix so a view may pass either shape.
     * Returns null for anything hosted elsewhere.
     */
    public static function toStoragePath(string $pathOrUrl): ?string
    {
        if (! Str::startsWith($pathOrUrl, ['http://', 'https://', '//'])) {
            return ltrim($pathOrUrl, '/');
        }

        $prefix = asset('storage/');

        if (! Str::startsWith($pathOrUrl, $prefix)) {
            return null;
        }

        return ltrim(Str::after($pathOrUrl, $prefix), '/');
    }

    public static function flushMemo(): void
    {
        self::$memo = [];
    }

    private static function resolve(string $pathOrUrl, int $width): string
    {
        $original = Str::startsWith($pathOrUrl, ['http://', 'https://', '//'])
            ? $pathOrUrl
            : asset('storage/'.ltrim($pathOrUrl, '/'));

        if (! in_array($width, self::WIDTHS, true)) {
            return $original;
        }

        $path = self::toStoragePath($pathOrUrl);

        if ($path === null || $path === '' || Str::startsWith($path, 'variants/')) {
            return $original;
        }

        $variant = self::variantPath($path, $width);

        try {
            if (! Storage::disk('public')->exists($variant)) {
                return $original;
            }
        } catch (\Throwable $exception) {
            return $original;
        }

        return asset('storage/'.$variant);
    }
}
