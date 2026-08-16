<?php

namespace App\Providers;

use App\Http\View\Composers\HeaderComposer;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wishlist;
use App\Observers\AdminAuditObserver;
use App\Observers\CartItemCacheObserver;
use App\Observers\CategoryCacheObserver;
use App\Observers\OrderAnalyticsObserver;
use App\Observers\ProductStockObserver;
use App\Observers\WishlistCacheObserver;
use App\Security\HibpCircuitBreaker;
use App\Security\ObservableUncompromisedVerifier;
use App\Support\Branding;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HibpCircuitBreaker::class, function ($app): HibpCircuitBreaker {
            return new HibpCircuitBreaker($app->make('cache.store'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->overrideUncompromisedVerifier();

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

        Password::defaults(function (): Password {
            $rule = Password::min(8)->letters()->numbers();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });

        View::composer('*', function ($view): void {
            $view->with('systemSettings', $this->systemSettings());
        });

        Category::observe(AdminAuditObserver::class);
        Category::observe(CategoryCacheObserver::class);
        CartItem::observe(CartItemCacheObserver::class);
        Product::observe(AdminAuditObserver::class);
        Product::observe(ProductStockObserver::class);
        User::observe(AdminAuditObserver::class);
        Wishlist::observe(WishlistCacheObserver::class);
        Order::observe(AdminAuditObserver::class);
        Order::observe(OrderAnalyticsObserver::class);

        View::composer('layouts.user', HeaderComposer::class);
    }

    /**
     * Resolve shared settings when a view is rendered, not when the service
     * provider boots. Long-running workers and admin save redirects can
     * otherwise keep a stale empty logo URL while /brand/logo serves correctly.
     *
     * Point Password::defaults()->uncompromised() at our verifier instead of
     * Laravel's NotPwnedVerifier. Rules\Password resolves this contract from the
     * container on every check, so all seven password flows are covered without
     * editing a call site.
     *
     * ValidationServiceProvider is deferred and also binds this contract, so a
     * plain singleton() in register() gets overwritten the first time anything
     * resolves 'validator'. Forcing that provider to load first marks it as
     * loaded, after which it never registers again and our binding is the last
     * word.
     */
    private function overrideUncompromisedVerifier(): void
    {
        if ($this->app->isDeferredService(UncompromisedVerifier::class)) {
            $this->app->loadDeferredProvider(UncompromisedVerifier::class);
        }

        $this->app->singleton(UncompromisedVerifier::class, function ($app): UncompromisedVerifier {
            return new ObservableUncompromisedVerifier(
                $app->make(HttpFactory::class),
                $app->make(HibpCircuitBreaker::class),
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function systemSettings(): array
    {
        try {
            if (Schema::hasTable('settings')) {
                $settings = Setting::allWithDefaults();
            } else {
                $settings = Setting::defaults();
            }
        } catch (\Throwable) {
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
        $settings['site_logo_version'] = (string) ($settings['site_logo_version'] ?? '');
        $settings['site_logo_url'] = Branding::versionedLogoUrl(
            (string) ($settings['site_logo'] ?? ''),
            $settings['site_logo_version']
        );

        return $settings;
    }
}
