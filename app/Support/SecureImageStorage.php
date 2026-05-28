<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SecureImageStorage
{
    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $imageInfo = @getimagesize($file->getRealPath());
        $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';

        // Reject anything that is not a verified raster image we support. This blocks
        // SVG (inline-JS stored XSS), GIF/BMP, and files disguised with an image
        // extension whose real bytes do not match a supported format.
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            abort(422, 'Unsupported or unverifiable image format.');
        }

        // GD unavailable: the file is already verified as a real image above, so it
        // is safe to store the original bytes without re-encoding.
        if (! function_exists('imagecreatetruecolor')) {
            return str_replace('\\', '/', (string) $file->store($directory, $disk));
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => function_exists('imagewebp') ? 'webp' : 'jpg',
            default => 'jpg',
        };

        $filename = trim($directory, '/') . '/' . (string) Str::uuid() . '.' . $extension;
        $encoded = self::encode($file, $mime, $extension);

        if ($encoded === null) {
            return str_replace('\\', '/', (string) $file->store($directory, $disk));
        }

        Storage::disk($disk)->put($filename, $encoded);

        return $filename;
    }

    /**
     * Store an email-attachment-class upload. Accepts jpeg/png/webp via the
     * existing image flow OR application/pdf with a magic-byte check. SVG and
     * anything else is rejected with HTTP 422.
     *
     * @return string Relative storage path (e.g. "email-attachments/abc.pdf")
     */
    public static function storeAttachment(UploadedFile $file, string $directory, string $disk = 'local'): string
    {
        $realPath = $file->getRealPath();

        if ($realPath === false || ! is_file($realPath)) {
            abort(422, 'Unable to read uploaded file.');
        }

        $imageInfo = @getimagesize($realPath);
        $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';

        // Re-use the hardened image path (which itself rejects SVG/unverifiable MIME).
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return self::store($file, $directory, $disk);
        }

        // PDF path — verify magic bytes.
        $head = (string) @file_get_contents($realPath, false, null, 0, 5);
        if ($head !== '%PDF-') {
            abort(422, 'Unsupported or unverifiable attachment format.');
        }

        $filename = trim($directory, '/') . '/' . (string) Str::uuid() . '.pdf';
        Storage::disk($disk)->putFileAs(
            trim($directory, '/'),
            $file,
            basename($filename)
        );

        return $filename;
    }

    private static function encode(UploadedFile $file, string $mime, string $extension): ?string
    {
        $path = $file->getRealPath();
        $image = match ($mime) {
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => @imagecreatefromjpeg($path),
        };

        if (! $image) {
            return null;
        }

        ob_start();

        try {
            if ($extension === 'png') {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagepng($image, null, 6);
            } elseif ($extension === 'webp' && function_exists('imagewebp')) {
                imagewebp($image, null, 82);
            } else {
                imagejpeg($image, null, 86);
            }

            return ob_get_clean() ?: null;
        } finally {
            imagedestroy($image);
        }
    }
}
