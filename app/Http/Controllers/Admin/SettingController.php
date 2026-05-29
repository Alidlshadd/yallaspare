<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Branding;
use App\Support\SecureImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    private const HERO_VIDEO_MAX_KILOBYTES = 51200;
    private const HERO_VIDEO_MAX_BYTES = self::HERO_VIDEO_MAX_KILOBYTES * 1024;
    private const HERO_VIDEO_DIRECTORY = 'home/hero';

    public function edit(): View
    {
        $settings = Setting::allWithDefaults();
        $productionChecks = [
            ['label' => 'APP_ENV', 'value' => config('app.env'), 'ok' => config('app.env') === 'production', 'expected' => 'production'],
            ['label' => 'APP_DEBUG', 'value' => config('app.debug') ? 'true' : 'false', 'ok' => config('app.debug') === false, 'expected' => 'false'],
            ['label' => 'QUEUE_CONNECTION', 'value' => config('queue.default'), 'ok' => config('queue.default') !== 'sync', 'expected' => 'database / redis'],
            ['label' => 'MAIL_MAILER', 'value' => config('mail.default'), 'ok' => config('mail.default') !== 'log', 'expected' => 'smtp / provider'],
        ];

        return view('admin.settings.edit', compact('settings', 'productionChecks'));
    }

    public function update(Request $request): RedirectResponse
    {
        $currentHeroTitle = (string) Setting::getValue('storefront_hero_title', 'Find the right spare parts faster');
        $currentHeroSubtitle = (string) Setting::getValue('storefront_hero_subtitle', 'Browse saved categories, filter by vehicle, and shop available parts from one clean catalog.');
        $currentHeroButtonLabel = (string) Setting::getValue('storefront_hero_button_label', 'Shop now');
        $currentHeroButtonUrl = (string) Setting::getValue('storefront_hero_button_url', '');

        $request->merge([
            'storefront_hero_title' => $request->input('storefront_hero_title', $currentHeroTitle),
            'storefront_hero_subtitle' => $request->input('storefront_hero_subtitle', $currentHeroSubtitle),
            'storefront_hero_button_label' => $request->input('storefront_hero_button_label', $currentHeroButtonLabel),
            'storefront_hero_button_url' => $request->input('storefront_hero_button_url', $currentHeroButtonUrl),
        ]);

        $validator = Validator::make($request->all(), [
            'site_name' => ['required', 'string', 'max:120'],
            'currency_code' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'shipping_fee' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'site_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_logo' => ['nullable', 'boolean'],
            'storefront_hero_title' => ['required', 'string', 'max:120'],
            'storefront_hero_subtitle' => ['required', 'string', 'max:300'],
            'storefront_hero_button_label' => ['required', 'string', 'max:40'],
            'storefront_hero_button_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $url = trim((string) $value);
                    if ($url === '') {
                        return;
                    }

                    if (preg_match('/^(javascript|data):/i', $url) === 1) {
                        $fail(__('The storefront hero button URL is not allowed.'));
                    }
                },
            ],
            'storefront_hero_video' => [
                'nullable',
                'file',
                'mimes:mp4',
                'max:' . self::HERO_VIDEO_MAX_KILOBYTES,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    if (! $this->isSafeMp4Upload($value)) {
                        $fail(__('Hero video upload failed. Please select a valid MP4 file.'));
                    }
                },
            ],
            'remove_storefront_hero_video' => ['nullable', 'boolean'],
            'sms_provider_webhook_url' => ['nullable', 'url', 'max:2048'],
            'whatsapp_provider_webhook_url' => ['nullable', 'url', 'max:2048'],
            'notification_order_placed_en_subject' => ['nullable', 'string', 'max:160'],
            'notification_order_placed_en_body' => ['nullable', 'string', 'max:3000'],
            'notification_order_status_updated_en_subject' => ['nullable', 'string', 'max:160'],
            'notification_order_status_updated_en_body' => ['nullable', 'string', 'max:3000'],
            'notification_order_placed_ar_subject' => ['nullable', 'string', 'max:160'],
            'notification_order_placed_ar_body' => ['nullable', 'string', 'max:3000'],
            'notification_order_status_updated_ar_subject' => ['nullable', 'string', 'max:160'],
            'notification_order_status_updated_ar_body' => ['nullable', 'string', 'max:3000'],
            'notification_order_placed_ku_subject' => ['nullable', 'string', 'max:160'],
            'notification_order_placed_ku_body' => ['nullable', 'string', 'max:3000'],
            'notification_order_status_updated_ku_subject' => ['nullable', 'string', 'max:160'],
            'notification_order_status_updated_ku_body' => ['nullable', 'string', 'max:3000'],
        ]);

        if ($validator->fails()) {
            if ($request->files->has('storefront_hero_video')) {
                $this->logHeroVideoUploadFailure(
                    $request,
                    $request->file('storefront_hero_video'),
                    'validation_failed',
                    ['errors' => $validator->errors()->get('storefront_hero_video')]
                );
            }

            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $data = $validator->validated();

        $currentLogo = (string) Setting::getValue('site_logo', '');
        $currentLogoStoragePath = Branding::storagePathFromValue($currentLogo);
        $newLogo = $currentLogo;
        $logoChanged = false;

        if ($request->boolean('remove_logo')) {
            if ($currentLogoStoragePath !== null) {
                Storage::disk('public')->delete($currentLogoStoragePath);
            }
            $newLogo = '';
            $logoChanged = true;
        }

        if ($request->hasFile('site_logo')) {
            $uploadedLogo = $request->file('site_logo');

            if (! $uploadedLogo->isValid()) {
                return back()
                    ->withInput()
                    ->withErrors(['site_logo' => __('Logo upload failed. Please select a valid transparent PNG, WEBP, or JPG file (max 8MB).')]);
            }

            try {
                // Store the new logo first, then remove the old one.
                // This prevents broken settings when storage write fails.
                $storedPath = $this->storeLogoWithTransparentBackground($uploadedLogo);
                $storedPath = str_replace('\\', '/', (string) $storedPath);
                if ($storedPath === '' || !Storage::disk('public')->exists($storedPath)) {
                    throw new \RuntimeException(__('Stored logo file was not found after upload.'));
                }

                if ($currentLogoStoragePath !== null && $currentLogoStoragePath !== $storedPath) {
                    Storage::disk('public')->delete($currentLogoStoragePath);
                }

                $newLogo = $storedPath;
                $logoChanged = true;
            } catch (Throwable $e) {
                return back()
                    ->withInput()
                    ->withErrors(['site_logo' => __('Could not save the uploaded logo. Please try again.')]);
            }
        }

        $heroImage = (string) Setting::getValue('storefront_hero_image', '');
        $heroVideo = (string) Setting::getValue('storefront_hero_video', '');

        if ($request->boolean('remove_storefront_hero_video')) {
            $this->deleteManagedHeroMedia($heroVideo);
            $heroVideo = '';
        }

        if ($request->hasFile('storefront_hero_video')) {
            $uploadedHeroVideo = $request->file('storefront_hero_video');
            $this->logHeroVideoUploadAttempt($request, $uploadedHeroVideo);

            if (! $uploadedHeroVideo->isValid()) {
                $this->logHeroVideoUploadFailure($request, $uploadedHeroVideo, 'php_upload_invalid');

                return back()
                    ->withInput()
                    ->withErrors(['storefront_hero_video' => __('Hero video upload failed. Please select a valid MP4 file.')]);
            }

            try {
                $storedHeroVideo = $this->storeHeroVideo($uploadedHeroVideo);
                if ($storedHeroVideo === '' || !Storage::disk('public')->exists($storedHeroVideo)) {
                    throw new \RuntimeException(__('Stored hero video was not found after upload.'));
                }

                $this->deleteManagedHeroMedia($heroVideo);
                $heroVideo = $storedHeroVideo;
                $this->writeUploadLog('info', 'Storefront hero video uploaded', [
                    'path' => $storedHeroVideo,
                    'size' => Storage::disk('public')->size($storedHeroVideo),
                    'mime' => Storage::disk('public')->mimeType($storedHeroVideo),
                    'admin_id' => optional($request->user())->getAuthIdentifier(),
                ]);
            } catch (Throwable $e) {
                $this->logHeroVideoUploadFailure($request, $uploadedHeroVideo, 'storage_failed', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);

                return back()
                    ->withInput()
                    ->withErrors(['storefront_hero_video' => __('Could not save the uploaded hero video. Please try again.')]);
            }
        }

        Setting::setMany([
            'site_name' => $data['site_name'],
            'currency_code' => strtoupper($data['currency_code']),
            'currency_symbol' => $data['currency_symbol'],
            'low_stock_threshold' => (string) $data['low_stock_threshold'],
            'shipping_fee' => (string) round((float) $data['shipping_fee'], 2),
            'site_logo' => $newLogo,
            'site_logo_version' => $logoChanged ? (string) Str::uuid() : (string) Setting::getValue('site_logo_version', ''),
            'storefront_hero_title' => $data['storefront_hero_title'],
            'storefront_hero_subtitle' => $data['storefront_hero_subtitle'],
            'storefront_hero_button_label' => $data['storefront_hero_button_label'],
            'storefront_hero_button_url' => trim((string) ($data['storefront_hero_button_url'] ?? '')),
            'storefront_hero_image' => $heroImage,
            'storefront_hero_video' => $heroVideo,
            'sms_provider_webhook_url' => (string) ($data['sms_provider_webhook_url'] ?? ''),
            'whatsapp_provider_webhook_url' => (string) ($data['whatsapp_provider_webhook_url'] ?? ''),
            'notification_order_placed_en_subject' => (string) ($data['notification_order_placed_en_subject'] ?? ''),
            'notification_order_placed_en_body' => (string) ($data['notification_order_placed_en_body'] ?? ''),
            'notification_order_status_updated_en_subject' => (string) ($data['notification_order_status_updated_en_subject'] ?? ''),
            'notification_order_status_updated_en_body' => (string) ($data['notification_order_status_updated_en_body'] ?? ''),
            'notification_order_placed_ar_subject' => (string) ($data['notification_order_placed_ar_subject'] ?? ''),
            'notification_order_placed_ar_body' => (string) ($data['notification_order_placed_ar_body'] ?? ''),
            'notification_order_status_updated_ar_subject' => (string) ($data['notification_order_status_updated_ar_subject'] ?? ''),
            'notification_order_status_updated_ar_body' => (string) ($data['notification_order_status_updated_ar_body'] ?? ''),
            'notification_order_placed_ku_subject' => (string) ($data['notification_order_placed_ku_subject'] ?? ''),
            'notification_order_placed_ku_body' => (string) ($data['notification_order_placed_ku_body'] ?? ''),
            'notification_order_status_updated_ku_subject' => (string) ($data['notification_order_status_updated_ku_subject'] ?? ''),
            'notification_order_status_updated_ku_body' => (string) ($data['notification_order_status_updated_ku_body'] ?? ''),
        ]);

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', __('System settings updated successfully.'));
    }

    private function storeLogoWithTransparentBackground(UploadedFile $uploadedLogo): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            return $this->storeOriginalLogo($uploadedLogo);
        }

        $realPath = $uploadedLogo->getRealPath();
        $contents = $realPath ? @file_get_contents($realPath) : false;

        if ($contents === false || $contents === '') {
            return $this->storeOriginalLogo($uploadedLogo);
        }

        $image = @imagecreatefromstring($contents);

        if (! $image) {
            return $this->storeOriginalLogo($uploadedLogo);
        }

        if (! imageistruecolor($image) && function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        $changed = false;
        $visited = [];
        $queue = new \SplQueue();

        for ($x = 0; $x < $width; $x++) {
            $this->queueWhiteLogoPixel($image, $queue, $visited, $width, $x, 0);
            $this->queueWhiteLogoPixel($image, $queue, $visited, $width, $x, $height - 1);
        }

        for ($y = 1; $y < $height - 1; $y++) {
            $this->queueWhiteLogoPixel($image, $queue, $visited, $width, 0, $y);
            $this->queueWhiteLogoPixel($image, $queue, $visited, $width, $width - 1, $y);
        }

        while (! $queue->isEmpty()) {
            $key = $queue->dequeue();
            $x = $key % $width;
            $y = intdiv($key, $width);

            imagesetpixel($image, $x, $y, $transparent);
            $changed = true;

            if ($x > 0) {
                $this->queueWhiteLogoPixel($image, $queue, $visited, $width, $x - 1, $y);
            }
            if ($x < $width - 1) {
                $this->queueWhiteLogoPixel($image, $queue, $visited, $width, $x + 1, $y);
            }
            if ($y > 0) {
                $this->queueWhiteLogoPixel($image, $queue, $visited, $width, $x, $y - 1);
            }
            if ($y < $height - 1) {
                $this->queueWhiteLogoPixel($image, $queue, $visited, $width, $x, $y + 1);
            }
        }

        if (! $changed) {
            imagedestroy($image);

            return $this->storeOriginalLogo($uploadedLogo);
        }

        ob_start();
        $written = imagepng($image, null, 9);
        $pngData = ob_get_clean();
        imagedestroy($image);

        if (! $written || ! is_string($pngData) || $pngData === '') {
            return $this->storeOriginalLogo($uploadedLogo);
        }

        $storedPath = 'settings/' . (string) Str::uuid() . '.png';
        Storage::disk('public')->put($storedPath, $pngData);

        return $storedPath;
    }

    private function queueWhiteLogoPixel($image, \SplQueue $queue, array &$visited, int $width, int $x, int $y): void
    {
        $key = ($y * $width) + $x;

        if (isset($visited[$key])) {
            return;
        }

        $visited[$key] = true;

        if (! $this->isNearWhiteLogoPixel(imagecolorat($image, $x, $y))) {
            return;
        }

        $queue->enqueue($key);
    }

    private function isNearWhiteLogoPixel(int $rgba): bool
    {
        $alpha = ($rgba & 0x7F000000) >> 24;
        $red = ($rgba >> 16) & 0xFF;
        $green = ($rgba >> 8) & 0xFF;
        $blue = $rgba & 0xFF;

        return $alpha < 120
            && min($red, $green, $blue) >= 242
            && (max($red, $green, $blue) - min($red, $green, $blue)) <= 18;
    }

    private function storeOriginalLogo(UploadedFile $uploadedLogo): string
    {
        // SecureImageStorage verifies the file is a real raster image (getimagesize on
        // real bytes), re-encodes through GD when available, and rejects SVG/disguised
        // payloads. Defends the fallback path when GD-based logo processing fails.
        return SecureImageStorage::store($uploadedLogo, 'settings', 'public');
    }

    private function storeHeroVideo(UploadedFile $uploadedHeroVideo): string
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory(self::HERO_VIDEO_DIRECTORY);

        $filename = (string) Str::uuid() . '.mp4';
        $storedPath = $disk->putFileAs(self::HERO_VIDEO_DIRECTORY, $uploadedHeroVideo, $filename);
        $storedPath = str_replace('\\', '/', (string) $storedPath);

        if ($storedPath === '' || ! $disk->exists($storedPath)) {
            throw new \RuntimeException('Hero video was not written to the public disk.');
        }

        if ((int) $disk->size($storedPath) <= 0) {
            $disk->delete($storedPath);
            throw new \RuntimeException('Hero video was written as an empty file.');
        }

        return $storedPath;
    }

    private function isSafeMp4Upload(UploadedFile $uploadedVideo): bool
    {
        $realPath = $uploadedVideo->getRealPath();

        if (! $uploadedVideo->isValid()
            || $realPath === false
            || ! is_readable($realPath)
            || strtolower($uploadedVideo->getClientOriginalExtension()) !== 'mp4'
            || $uploadedVideo->getSize() <= 0
            || $uploadedVideo->getSize() > self::HERO_VIDEO_MAX_BYTES
        ) {
            return false;
        }

        return $this->hasMp4FtypSignature($realPath)
            && ! $this->containsDangerousUploadSignature($realPath);
    }

    private function hasMp4FtypSignature(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $header = (string) fread($handle, 64);
        fclose($handle);

        if (strlen($header) < 12 || substr($header, 4, 4) !== 'ftyp') {
            return false;
        }

        $brandBytes = substr($header, 8, 56);
        foreach (['isom', 'iso2', 'mp41', 'mp42', 'avc1', 'dash', 'M4V '] as $brand) {
            if (str_contains($brandBytes, $brand)) {
                return true;
            }
        }

        return false;
    }

    private function containsDangerousUploadSignature(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return true;
        }

        $tail = '';
        while (! feof($handle)) {
            $chunk = $tail . (string) fread($handle, 1024 * 1024);
            $lowerChunk = strtolower($chunk);

            foreach (['<?php', '<?=', '<? ', '<script', '<html', '#!/usr/bin/env php'] as $signature) {
                if (str_contains($lowerChunk, $signature)) {
                    fclose($handle);

                    return true;
                }
            }

            $tail = substr($chunk, -32);
        }

        fclose($handle);

        return false;
    }

    private function logHeroVideoUploadAttempt(Request $request, ?UploadedFile $uploadedVideo): void
    {
        $this->writeUploadLog('info', 'Storefront hero video upload attempt', $this->heroVideoUploadContext($request, $uploadedVideo));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function logHeroVideoUploadFailure(Request $request, mixed $uploadedVideo, string $reason, array $extra = []): void
    {
        $this->writeUploadLog('warning', 'Storefront hero video upload failed', array_merge(
            $this->heroVideoUploadContext($request, $uploadedVideo instanceof UploadedFile ? $uploadedVideo : null),
            ['reason' => $reason],
            $extra
        ));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeUploadLog(string $level, string $message, array $context): void
    {
        try {
            Log::log($level, $message, $context);
        } catch (Throwable) {
            // Uploads must not fail only because production logging permissions are broken.
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function heroVideoUploadContext(Request $request, ?UploadedFile $uploadedVideo): array
    {
        $realPath = $uploadedVideo?->getRealPath();
        $readablePath = is_string($realPath) && $realPath !== '' && is_readable($realPath);

        return [
            'admin_id' => optional($request->user())->getAuthIdentifier(),
            'content_length' => (int) $request->server('CONTENT_LENGTH', 0),
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
            'php_max_execution_time' => ini_get('max_execution_time'),
            'php_max_input_time' => ini_get('max_input_time'),
            'php_memory_limit' => ini_get('memory_limit'),
            'upload_tmp_path_readable' => $readablePath,
            'original_name' => $this->uploadedFileValue($uploadedVideo, 'getClientOriginalName'),
            'client_mime' => $this->uploadedFileValue($uploadedVideo, 'getClientMimeType'),
            'detected_mime' => $readablePath ? $this->uploadedFileValue($uploadedVideo, 'getMimeType') : null,
            'extension' => $this->uploadedFileValue($uploadedVideo, 'getClientOriginalExtension'),
            'size' => $this->uploadedFileValue($uploadedVideo, 'getSize'),
            'upload_error' => $this->uploadedFileValue($uploadedVideo, 'getError'),
            'upload_error_message' => $this->uploadedFileValue($uploadedVideo, 'getErrorMessage'),
        ];
    }

    private function uploadedFileValue(?UploadedFile $uploadedVideo, string $method): mixed
    {
        if ($uploadedVideo === null || ! method_exists($uploadedVideo, $method)) {
            return null;
        }

        try {
            return $uploadedVideo->{$method}();
        } catch (Throwable $e) {
            return null;
        }
    }

    private function deleteManagedHeroMedia(string $path): void
    {
        $path = str_replace('\\', '/', trim($path));

        if ($path !== '' && Str::startsWith($path, 'home/hero/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
