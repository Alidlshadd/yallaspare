<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Square app icons rendered from the logo the admin uploaded.
 *
 * A favicon slot cannot take the logo file as-is: the upload is whatever
 * aspect ratio and background the admin had, while every icon slot wants a
 * square, and browsers pick the link that declares the size they want. So the
 * logo is composited onto a navy tile at each size the head asks for, which
 * keeps one source of truth — change the setting, every icon changes.
 */
class BrandIcon
{
    /** Navy #070740. The tile is the brand, the logo sits on it. */
    private const TILE = [7, 7, 64];

    /** Share of the tile the logo may occupy, leaving an even margin. */
    private const INSET = 0.76;

    /**
     * The sizes the head, the manifest and iOS ask for. A slot outside this
     * list is a 404 rather than an invitation to render arbitrary bitmaps.
     *
     * @return list<int>
     */
    public static function sizes(): array
    {
        return [16, 32, 48, 180, 192, 512];
    }

    public static function supports(int $size): bool
    {
        return in_array($size, self::sizes(), true);
    }

    /**
     * The stamp that changes whenever the rendered set would change. It goes
     * on the icon URLs so an upload busts the browser cache, and it names the
     * cache files so a stale render is never served.
     */
    public static function version(): ?string
    {
        $path = self::logoPath();
        if ($path === null) {
            return null;
        }

        $settingVersion = (string) Setting::getValue('site_logo_version', '');
        $mtime = (string) (@filemtime($path) ?: '0');

        return substr(md5($path.'|'.$settingVersion.'|'.$mtime), 0, 12);
    }

    /**
     * PNG bytes for one size, or null when no usable logo is configured — in
     * which case the caller falls back to the packaged files in public/.
     */
    public static function render(int $size): ?string
    {
        if (! self::supports($size) || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $version = self::version();
        $path = self::logoPath();
        if ($version === null || $path === null) {
            return null;
        }

        $cacheKey = 'brand-icons/'.$version.'-'.$size.'.png';
        $cache = Storage::disk('local');
        if ($cache->exists($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $png = self::compose($path, $size);
        if ($png === null) {
            return null;
        }

        $cache->put($cacheKey, $png);

        return $png;
    }

    private static function logoPath(): ?string
    {
        $storagePath = Branding::storagePathFromValue((string) Setting::getValue('site_logo', ''));

        if ($storagePath === null || ! Branding::isSafeLogoPath($storagePath)) {
            return null;
        }

        $absolute = storage_path('app/public/'.$storagePath);

        return is_file($absolute) ? $absolute : null;
    }

    private static function compose(string $path, int $size): ?string
    {
        $source = self::open($path);
        if ($source === null) {
            return null;
        }

        // Draw large and shrink once at the end. Compositing straight into a
        // 16px tile gives the logo four pixels to work with; supersampling
        // lets the edges average into something still recognisable.
        $work = min(512, max($size, $size * 4));

        $canvas = imagecreatetruecolor($work, $work);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);
        imagefilledrectangle(
            $canvas, 0, 0, $work, $work,
            imagecolorallocate($canvas, self::TILE[0], self::TILE[1], self::TILE[2])
        );

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $box = (int) round($work * self::INSET);
        $scale = min($box / $sourceWidth, $box / $sourceHeight);
        $drawWidth = max(1, (int) round($sourceWidth * $scale));
        $drawHeight = max(1, (int) round($sourceHeight * $scale));

        imagecopyresampled(
            $canvas, $source,
            (int) round(($work - $drawWidth) / 2),
            (int) round(($work - $drawHeight) / 2),
            0, 0,
            $drawWidth, $drawHeight,
            $sourceWidth, $sourceHeight
        );
        imagedestroy($source);

        if ($work !== $size) {
            $final = imagecreatetruecolor($size, $size);
            imagealphablending($final, false);
            imagesavealpha($final, true);
            imagecopyresampled($final, $canvas, 0, 0, 0, 0, $size, $size, $work, $work);
            imagedestroy($canvas);
            $canvas = $final;
        }

        ob_start();
        imagepng($canvas, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($canvas);

        return $png !== '' ? $png : null;
    }

    /**
     * @return \GdImage|null
     */
    private static function open(string $path)
    {
        $mimeType = Branding::safeLogoMimeType($path);

        $image = match ($mimeType) {
            'image/png' => @imagecreatefrompng($path),
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if ($image === false) {
            return null;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }
}
