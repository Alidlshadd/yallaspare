<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * A small JPEG of a stored image, for embedding in a spreadsheet.
 *
 * The spreadsheet writer keeps every embedded picture in memory until the
 * file is finished. Product photos are stored as uploaded — megabytes each —
 * and a catalogue's worth of them overran PHP's memory limit, which no
 * try/catch can turn into a friendly message: the export simply answered 500.
 * A cell-sized thumbnail is a few kilobytes, so the whole catalogue fits.
 *
 * Thumbnails are kept on disk and reused; one is rebuilt only when its source
 * file changes. Building them is the slow part of a first export, so it works
 * to a time budget: once that is spent the remaining rows go without a
 * picture this time and get one on the next export, rather than the request
 * running into the execution limit.
 */
class ExportThumbnail
{
    private const SIZE = 96;

    private const DIRECTORY = 'export-thumbnails';

    /** Decoding anything larger than this risks the memory limit on its own. */
    private const MAX_SOURCE_PIXELS = 30_000_000;

    private const BUILD_BUDGET_SECONDS = 20.0;

    private static float $spent = 0.0;

    /**
     * Absolute path of the thumbnail for a public-disk image, or null when
     * there is none to show: no file, not an image, or out of time.
     */
    public static function pathFor(?string $publicPath): ?string
    {
        $publicPath = ltrim(trim((string) $publicPath), '/');

        if ($publicPath === '' || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($publicPath)) {
                return null;
            }

            $original = $disk->path($publicPath);
            $target = storage_path('app/'.self::DIRECTORY.'/'.sha1($publicPath.'|'.filemtime($original).'|'.filesize($original)).'.jpg');

            if (is_file($target)) {
                return $target;
            }

            if (self::$spent >= self::BUILD_BUDGET_SECONDS) {
                return null;
            }

            $started = microtime(true);
            $built = self::build(self::smallestSource($publicPath, $original), $target);
            self::$spent += microtime(true) - $started;

            return $built ? $target : null;
        } catch (\Throwable) {
            // A picture is decoration here; the rows must still export.
            return null;
        }
    }

    public static function resetBudget(): void
    {
        self::$spent = 0.0;
    }

    /**
     * The pre-generated 400px copy when there is one — far cheaper to decode
     * than a full-size upload — and the original otherwise.
     */
    private static function smallestSource(string $publicPath, string $original): string
    {
        $variant = ImageVariants::variantPath($publicPath, ImageVariants::WIDTHS[0]);
        $disk = Storage::disk('public');

        return $disk->exists($variant) ? $disk->path($variant) : $original;
    }

    private static function build(string $source, string $target): bool
    {
        $info = @getimagesize($source);

        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > self::MAX_SOURCE_PIXELS) {
            return false;
        }

        $contents = @file_get_contents($source);
        $image = $contents === false ? false : @imagecreatefromstring($contents);
        unset($contents);

        if ($image === false) {
            return false;
        }

        $scale = min(self::SIZE / $info[0], self::SIZE / $info[1], 1);
        $width = max(1, (int) round($info[0] * $scale));
        $height = max(1, (int) round($info[1] * $scale));

        // White behind it: a transparent PNG would otherwise turn black as a JPEG.
        $thumbnail = imagecreatetruecolor($width, $height);
        imagefill($thumbnail, 0, 0, (int) imagecolorallocate($thumbnail, 255, 255, 255));
        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
        imagedestroy($image);

        if (! is_dir(dirname($target))) {
            @mkdir(dirname($target), 0775, true);
        }

        $written = imagejpeg($thumbnail, $target, 82);
        imagedestroy($thumbnail);

        return $written && is_file($target);
    }
}
