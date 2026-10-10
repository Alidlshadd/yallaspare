<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * The uploaded logo at the size the pages actually draw it.
 *
 * Admins upload whatever they have — the live one was 1254px and 453 KB for a
 * mark shown 80px wide, and it rode along on every page. /brand/logo serves
 * this copy instead: the same picture in the same format, capped on its long
 * edge. A logo already inside the cap is served untouched.
 */
class BrandLogo
{
    /** Long edge in pixels. The storefront draws the mark at 80px or less, so this is sharp on 3x screens. */
    private const MAX_EDGE = 400;

    /**
     * Absolute path of the file to serve for the logo at $absolute: a cached
     * smaller copy when one is worth having, otherwise the original.
     */
    public static function displayPath(string $absolute): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return $absolute;
        }

        $dimensions = @getimagesize($absolute);
        if ($dimensions === false || max($dimensions[0], $dimensions[1]) <= self::MAX_EDGE) {
            return $absolute;
        }

        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $cacheKey = 'brand-logo/'.md5($absolute.'|'.(@filemtime($absolute) ?: '0').'|'.self::MAX_EDGE).'.'.$extension;
        $cache = Storage::disk('local');

        if (! $cache->exists($cacheKey)) {
            $bytes = self::shrink($absolute, $dimensions[0], $dimensions[1]);
            $original = (string) @file_get_contents($absolute);

            if ($original === '') {
                return $absolute;
            }

            // A re-encode can come out heavier than a well-compressed source.
            // Keep whichever is lighter so the answer is cached either way.
            $cache->put($cacheKey, $bytes !== null && strlen($bytes) < strlen($original) ? $bytes : $original);
        }

        return $cache->path($cacheKey);
    }

    private static function shrink(string $path, int $sourceWidth, int $sourceHeight): ?string
    {
        $mimeType = Branding::safeLogoMimeType($path);

        $source = match ($mimeType) {
            'image/png' => @imagecreatefrompng($path),
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if ($source === false) {
            return null;
        }

        $scale = self::MAX_EDGE / max($sourceWidth, $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));

        // Start from a fully transparent canvas and copy without blending, so
        // a logo with a see-through background keeps it.
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $width, $height, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        imagedestroy($source);

        ob_start();
        $written = match ($mimeType) {
            'image/png' => imagepng($canvas, null, 9),
            'image/jpeg' => imagejpeg($canvas, null, 88),
            'image/webp' => function_exists('imagewebp') && imagewebp($canvas, null, 88),
            default => false,
        };
        $bytes = (string) ob_get_clean();
        imagedestroy($canvas);

        return $written && $bytes !== '' ? $bytes : null;
    }
}
