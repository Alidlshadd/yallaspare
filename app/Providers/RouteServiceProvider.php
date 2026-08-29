<?php

namespace App\Providers;

use App\Models\Product;
use App\Support\IraqiPhoneNumber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        Route::bind('product', function (string $value): Product {
            return Product::query()
                ->when(
                    ctype_digit($value),
                    fn ($query) => $query->whereKey((int) $value),
                    fn ($query) => $query->where('slug', $value)
                )
                ->firstOrFail();
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(240)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('public-write', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('commerce-write', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('checkout-write', function (Request $request) {
            return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('admin-write', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('admin-2fa', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('phone-verification-send', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('phone-verification-check', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Express checkout sends codes to numbers nobody has signed in with,
        // so the number itself is the thing to bound: without the first limit
        // one browser could walk a range of numbers, each a fresh "account"
        // and so a fresh allowance. Resends carry no number field and fall
        // back to the address, which the session cooldown already paces.
        RateLimiter::for('express-checkout-code', function (Request $request) {
            $phone = IraqiPhoneNumber::digits($request->input('phone'));

            return [
                Limit::perMinutes(10, 3)->by($phone !== null ? 'phone:'.sha1($phone) : 'ip:'.$request->ip()),
                Limit::perMinutes(10, 8)->by('ip:'.$request->ip()),
            ];
        });

        // Web counterparts of the mobile-* limiters below. They are deliberately
        // looser: carrier-grade NAT is common in this market, so a whole
        // neighbourhood can share one address, and a browser form re-POSTs on
        // every validation failure where the API client would not.
        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Two limits: tight per address so one mailbox cannot be flooded, looser
        // per IP so someone spraying many addresses still gets stopped.
        RateLimiter::for('auth-password-email', function (Request $request) {
            return [
                Limit::perMinute(3)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
                Limit::perMinute(10)->by($request->ip()),
            ];
        });

        // Submitting the new password sends no mail and needs a valid token, so
        // the only job here is to bound automation. Kept generous because the
        // password rules reject often enough that retries are normal.
        RateLimiter::for('auth-password-reset', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('mobile-lookup', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('mobile-login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('mobile-register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('mobile-password-reset', function (Request $request) {
            return Limit::perMinute(3)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
