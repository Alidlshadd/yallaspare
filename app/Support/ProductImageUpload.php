<?php

namespace App\Support;

/**
 * What a product picture may be, stated once.
 *
 * The form's hint, the browser-side check and the validation rules all read
 * from here, so they cannot drift into telling the admin three different
 * things.
 */
class ProductImageUpload
{
    /** The formats SecureImageStorage will actually keep. */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Per image, main or gallery. */
    public const MAX_KILOBYTES = 5120;

    /**
     * The limit that really applies to this request.
     *
     * PHP drops a file larger than upload_max_filesize before the application
     * sees it, so promising more than the server accepts would only move the
     * failure somewhere less clear. Read at request time, this is the web
     * server's own setting rather than the command line's.
     */
    public static function maxKilobytes(): int
    {
        $server = intdiv(ini_parse_quantity((string) ini_get('upload_max_filesize')), 1024);

        return $server > 0 ? min(self::MAX_KILOBYTES, $server) : self::MAX_KILOBYTES;
    }

    /** The limit as people read it: "5", or "1.5" on a tighter server. */
    public static function maxMegabytes(): string
    {
        return rtrim(rtrim(number_format(self::maxKilobytes() / 1024, 1, '.', ''), '0'), '.');
    }

    public static function acceptAttribute(): string
    {
        return implode(',', [
            ...array_map(fn (string $extension): string => '.'.$extension, self::EXTENSIONS),
            ...self::MIME_TYPES,
        ]);
    }
}
