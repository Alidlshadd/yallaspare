<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Branding;
use App\Observers\AdminAuditObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        Lang::handleMissingKeysUsing(function (string $key, array $replace, string $locale, bool $fallback): string {
            Log::warning('Missing translation key.', [
                'key' => $key,
                'locale' => $locale,
                'fallback' => $fallback,
            ]);

            $fallbackLocale = (string) config('app.fallback_locale', 'en');
            $fallbackText = Lang::get($key, $replace, $fallbackLocale);

            if (is_string($fallbackText) && $fallbackText !== $key) {
                return $fallbackText;
            }

            $label = str_contains($key, '.') ? Str::afterLast($key, '.') : $key;

            return Str::of($label)
                ->replace(['_', '-'], ' ')
                ->replaceMatches('/\s+/', ' ')
                ->trim()
                ->ucfirst()
                ->toString();
        });

        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers());

        try {
            if (Schema::hasTable('settings')) {
                $settings = Setting::allWithDefaults();
            } else {
                $settings = Setting::defaults();
            }
        } catch (\Throwable $e) {
            $settings = Setting::defaults();
        }

        $settings['low_stock_threshold'] = (int) ($settings['low_stock_threshold'] ?? 5);
        $settings['shipping_fee'] = max(0, (float) ($settings['shipping_fee'] ?? 5000));
        $settings['currency_symbol'] = (string) ($settings['currency_symbol'] ?? 'IQD');
        $settings['currency_code'] = (string) ($settings['currency_code'] ?? 'IQD');
        if ($settings['currency_code'] === '') {
            $settings['currency_code'] = 'IQD';
        }
        if ($settings['currency_symbol'] === '') {
            $settings['currency_symbol'] = $settings['currency_code'];
        }
        $settings['currency_label'] = $settings['currency_code'] !== '' ? $settings['currency_code'] : $settings['currency_symbol'];
        $settings['currency_decimals'] = strtoupper($settings['currency_code']) === 'IQD' ? 0 : 2;
        $settings['site_name'] = (string) ($settings['site_name'] ?? config('app.name', 'Laravel'));
        $settings['site_logo_url'] = Branding::logoUrlFromValue((string) ($settings['site_logo'] ?? ''));
        $settings['site_logo_version'] = (string) ($settings['site_logo_version'] ?? '');
        if ($settings['site_logo_url'] !== null && $settings['site_logo_version'] !== '') {
            $separator = str_contains($settings['site_logo_url'], '?') ? '&' : '?';
            $settings['site_logo_url'] .= $separator . 'sv=' . urlencode($settings['site_logo_version']);
        }

        View::share('systemSettings', $settings);

        Category::observe(AdminAuditObserver::class);
        Product::observe(AdminAuditObserver::class);
        User::observe(AdminAuditObserver::class);
        Order::observe(AdminAuditObserver::class);
    }
}
