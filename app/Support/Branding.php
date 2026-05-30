<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Branding
{
    private const SAFE_LOGO_MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    public static function logoUrlFromValue(?string $rawValue): ?string
    {
        $value = trim((string) $rawValue);
        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        $storagePath = self::storagePathFromValue($value);
        if (self::isSafeLogoPath($storagePath) && Storage::disk('public')->exists($storagePath)) {
            $fullPath = storage_path('app/public/' . $storagePath);
            $version = is_file($fullPath)
                ? (string) filemtime($fullPath)
                : md5($storagePath);

            return '/storage/' . ltrim($storagePath, '/') . '?v=' . $version;
        }

        $publicPath = self::publicPathFromValue($value);
        if (self::isSafeLogoPath($publicPath) && is_file(public_path(ltrim($publicPath, '/')))) {
            $version = (string) filemtime(public_path(ltrim($publicPath, '/')));

            return $publicPath . '?v=' . $version;
        }

        return null;
    }

    public static function isSafeLogoPath(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return array_key_exists($extension, self::SAFE_LOGO_MIME_TYPES);
    }

    public static function safeLogoMimeType(?string $path): ?string
    {
        if (! self::isSafeLogoPath($path)) {
            return null;
        }

        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return self::SAFE_LOGO_MIME_TYPES[$extension] ?? null;
    }

    public static function storagePathFromValue(?string $rawValue): ?string
    {
        $value = str_replace('\\', '/', trim((string) $rawValue));
        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return null;
        }

        if (Str::startsWith($value, '/storage/')) {
            $value = Str::after($value, '/storage/');
        } elseif (Str::startsWith($value, 'storage/')) {
            $value = Str::after($value, 'storage/');
        } elseif (Str::startsWith($value, '/')) {
            return null;
        }

        $value = ltrim($value, '/');

        return $value !== '' ? $value : null;
    }

    public static function initials(?string $brand, int $maxChars = 2): string
    {
        $name = trim((string) $brand);
        if ($name === '') {
            return 'YS';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = collect($parts)
            ->filter()
            ->map(fn (string $part) => Str::substr($part, 0, 1))
            ->take($maxChars)
            ->implode('');

        $initials = strtoupper($initials);

        return $initials !== '' ? $initials : 'YS';
    }

    private static function publicPathFromValue(string $value): ?string
    {
        $normalized = str_replace('\\', '/', trim($value));
        if ($normalized === '') {
            return null;
        }

        if (Str::startsWith($normalized, '/')) {
            return $normalized;
        }

        if (Str::startsWith($normalized, ['storage/', 'images/'])) {
            return '/' . ltrim($normalized, '/');
        }

        return null;
    }

    /**
     * Resolve an absolute filesystem path to the site logo for embedding in PDFs.
     * DomPDF needs a path, not a URL. Returns null if no safe logo can be found.
     */
    public static function invoiceLogoPath(): ?string
    {
        $logoValue = (string) Setting::getValue('site_logo', '');
        if ($logoValue === '') {
            return null;
        }

        $storagePath = self::storagePathFromValue($logoValue);
        if ($storagePath && self::isSafeLogoPath($storagePath)) {
            $publicStoragePath = public_path('storage/' . ltrim($storagePath, '/'));
            if (is_file($publicStoragePath)) {
                return str_replace('\\', '/', $publicStoragePath);
            }
        }

        $normalized = str_replace('\\', '/', trim($logoValue));
        if (
            self::isSafeLogoPath($normalized)
            && Str::startsWith($normalized, ['assets/', 'images/', 'storage/', '/assets/', '/images/', '/storage/'])
        ) {
            $publicPath = public_path(ltrim($normalized, '/'));
            if (is_file($publicPath)) {
                return str_replace('\\', '/', $publicPath);
            }
        }

        return null;
    }
}
