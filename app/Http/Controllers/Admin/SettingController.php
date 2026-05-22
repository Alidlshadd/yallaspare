<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
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
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'currency_code' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'shipping_fee' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'site_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_logo' => ['nullable', 'boolean'],
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
                    ->withErrors(['site_logo' => __('Logo upload failed. Please select a valid JPG, PNG, or WEBP file (max 8MB).')]);
            }

            try {
                // Store the new logo first, then remove the old one.
                // This prevents broken settings when storage write fails.
                $storedPath = $uploadedLogo->store('settings', 'public');
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

        Setting::setMany([
            'site_name' => $data['site_name'],
            'currency_code' => strtoupper($data['currency_code']),
            'currency_symbol' => $data['currency_symbol'],
            'low_stock_threshold' => (string) $data['low_stock_threshold'],
            'shipping_fee' => (string) round((float) $data['shipping_fee'], 2),
            'site_logo' => $newLogo,
            'site_logo_version' => $logoChanged ? (string) Str::uuid() : (string) Setting::getValue('site_logo_version', ''),
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
}
