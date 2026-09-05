<?php

namespace App\Console\Commands;

use App\Support\ImageVariants;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Writes resized WebP copies of uploaded images so the storefront can serve a
 * card-sized file instead of the original upload.
 *
 * This runs over the stored files rather than hooking the upload path, so the
 * admin upload flow is untouched: a new photo simply gets its variants on the
 * next run, and until then the views fall back to the original.
 */
class GenerateImageVariants extends Command
{
    protected $signature = 'images:variants
        {--force : Rebuild variants that already exist}
        {--dir=* : Limit the scan to these public-disk directories}';

    protected $description = 'Generate resized WebP variants for uploaded storefront images';

    /**
     * Directories holding images the storefront renders. Deliberately not the
     * whole disk: invoices, exports and backups have no business being resized.
     */
    private const DIRECTORIES = [
        'products',
        'categories',
        'popups',
        'brands',
        'vehicles',
        'settings',
        'home',
    ];

    private const SOURCE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->warn('The gd extension is not loaded, so no variants can be generated.');
            $this->line('The storefront keeps serving the original uploads until it is installed.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('public');
        $directories = $this->option('dir') ?: self::DIRECTORIES;
        $force = (bool) $this->option('force');

        $written = 0;
        $skipped = 0;
        $failed = 0;
        $savedBytes = 0;

        foreach ($directories as $directory) {
            $directory = trim((string) $directory, '/');

            if ($directory === '' || ! $disk->exists($directory)) {
                continue;
            }

            foreach ($disk->allFiles($directory) as $path) {
                if (Str::startsWith($path, 'variants/')) {
                    continue;
                }

                if (! in_array(strtolower((string) Str::of($path)->afterLast('.')), self::SOURCE_EXTENSIONS, true)) {
                    continue;
                }

                $sourcePath = $disk->path($path);
                $sourceSize = @filesize($sourcePath) ?: 0;

                foreach (ImageVariants::WIDTHS as $width) {
                    $variant = ImageVariants::variantPath($path, $width);

                    if (! $force && $disk->exists($variant)) {
                        $skipped++;

                        continue;
                    }

                    $result = $this->writeVariant($sourcePath, $disk->path($variant), $width);

                    if ($result === null) {
                        $failed++;

                        continue;
                    }

                    if ($result === 0) {
                        // The original is already narrower than this width, so a
                        // variant would only be the same picture in a new file.
                        $skipped++;

                        continue;
                    }

                    $written++;
                    $savedBytes += max(0, $sourceSize - $result);
                }
            }
        }

        $this->info(sprintf(
            'Wrote %d variant(s), skipped %d, failed %d. Roughly %s saved against serving the originals.',
            $written,
            $skipped,
            $failed,
            $this->formatBytes($savedBytes)
        ));

        return self::SUCCESS;
    }

    /**
     * @return int|null Bytes written, 0 when the source is already small
     *                  enough to leave alone, null when it could not be read.
     */
    private function writeVariant(string $sourcePath, string $targetPath, int $width): ?int
    {
        $image = $this->readImage($sourcePath);

        if ($image === null) {
            return null;
        }

        try {
            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);

            if ($sourceWidth <= 0 || $sourceHeight <= 0) {
                return null;
            }

            if ($sourceWidth <= $width) {
                return 0;
            }

            $targetHeight = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));
            $resized = imagecreatetruecolor($width, $targetHeight);

            // Product cut-outs are transparent PNGs. Without this they would
            // come back with a black box behind them.
            imagealphablending($resized, false);
            imagesavealpha($resized, true);

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $targetHeight, $sourceWidth, $sourceHeight);

            $directory = dirname($targetPath);

            if (! is_dir($directory) && ! @mkdir($directory, 0o755, true) && ! is_dir($directory)) {
                imagedestroy($resized);

                return null;
            }

            $ok = imagewebp($resized, $targetPath, 82);
            imagedestroy($resized);

            if (! $ok) {
                return null;
            }

            return @filesize($targetPath) ?: 0;
        } finally {
            imagedestroy($image);
        }
    }

    private function readImage(string $path): ?\GdImage
    {
        if (! is_readable($path)) {
            return null;
        }

        try {
            $image = @imagecreatefromstring((string) @file_get_contents($path));
        } catch (\Throwable $exception) {
            return null;
        }

        return $image instanceof \GdImage ? $image : null;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }
}
