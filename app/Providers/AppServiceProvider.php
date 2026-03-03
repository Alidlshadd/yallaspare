<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Observers\AdminAuditObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

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
        $settings['site_logo_url'] = !empty($settings['site_logo'])
            ? asset('storage/' . ltrim((string) $settings['site_logo'], '/'))
            : null;

        View::share('systemSettings', $settings);

        Category::observe(AdminAuditObserver::class);
        Product::observe(AdminAuditObserver::class);
        User::observe(AdminAuditObserver::class);
        Order::observe(AdminAuditObserver::class);
    }
}
