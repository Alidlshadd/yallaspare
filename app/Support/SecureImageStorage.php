<?php

namespace App\Support;

use App\Exceptions\ImageUploadException;
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
            throw ImageUploadException::unsupported();
        }

        // GD unavailable: the file is already verified as a real image above, so it
        // is safe to store the original bytes without re-encoding.
        if (! function_exists('imagecreatetruecolor')) {
            return self::storeOriginal($file, $directory, $disk);
        }

        // Decoding costs memory by pixel count, not file size, and running out
        // is a fatal error nothing can catch. Refuse it while we still can.
        if (! self::fitsInMemory((int) $imageInfo[0], (int) $imageInfo[1])) {
            throw ImageUploadException::tooManyPixels();
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => function_exists('imagewebp') ? 'webp' : 'jpg',
            default => 'jpg',
        };

        $filename = trim($directory, '/').'/'.(string) Str::uuid().'.'.$extension;
        $encoded = self::encode($file, $mime, $extension);

        if ($encoded === null) {
            return self::storeOriginal($file, $directory, $disk);
        }

        // The disks are configured not to throw, so a full or unwritable disk
        // only shows up as a false here. Left unchecked it becomes a saved
        // record pointing at a picture that was never written.
        if (! Storage::disk($disk)->put($filename, $encoded)) {
            throw ImageUploadException::notSaved();
        }

        return $filename;
    }

    private static function storeOriginal(UploadedFile $file, string $directory, string $disk): string
    {
        $path = $file->store($directory, $disk);

        if (! is_string($path) || $path === '') {
            throw ImageUploadException::notSaved();
        }

        return str_replace('\\', '/', $path);
    }

    private static function fitsInMemory(int $width, int $height): bool
    {
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));

        if ($limit <= 0) {
            return true;
        }

        // A truecolor GD image takes about five bytes a pixel once its row
        // pointers are counted.
        return $width * $height * 5 < $limit - memory_get_usage(true);
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
