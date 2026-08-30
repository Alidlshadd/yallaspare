<?php

use App\Http\Controllers\Account\AccountAddressController;
use App\Http\Controllers\Account\AccountCredentialsController;
use App\Http\Controllers\Account\AccountOrdersController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\BulkStockAdjustmentController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\DiscountCouponController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\GoalController;
use App\Http\Controllers\Admin\GovernorateShippingController;
use App\Http\Controllers\Admin\InventoryMovementController;
use App\Http\Controllers\Admin\LowStockController;
use App\Http\Controllers\Admin\MessagingController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OperationsInsightController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OtpiqWhatsAppController;
use App\Http\Controllers\Admin\PopupController;
use App\Http\Controllers\Admin\ProductBrandController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductReviewController as AdminProductReviewController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ProgressCenterController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleFitmentController;
use App\Http\Controllers\Admin\WaylPaymentController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogLandingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\ExpressCheckoutController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PaymentReturnController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController as CatalogShopController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\User\CustomerPhoneSetupController;
use App\Http\Controllers\User\PhoneVerificationController;
use App\Http\Controllers\User\ShopController as UserShopController;
use App\Http\Controllers\User\UserAccountController;
use App\Http\Controllers\User\UserSettingsController;
use App\Http\Controllers\User\WishlistController;
use App\Models\Setting;
use App\Models\User;
use App\Support\BrandIcon;
use App\Support\Branding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('user.shop.home');
})->name('home');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::post('/language/{locale}', function (Request $request, string $locale) {
    abort_unless(in_array($locale, ['en', 'ar', 'ku'], true), 404);

    $request->session()->put('locale', $locale);
    if ($request->user()) {
        $request->user()->forceFill(['locale_preference' => $locale])->save();
    }

    app()->setLocale($locale);

    $redirectTo = (string) $request->input('redirect_to', '');
    $previousUrl = URL::previous();
    // url('/') has no trailing slash, so require an exact match or a proper
    // boundary after it — a bare prefix check would accept lookalike hosts
    // such as https://example.com.evil.com or https://example.com:8080.
    $appBase = rtrim(url('/'), '/');
    $isInternal = $redirectTo === $appBase
        || str_starts_with($redirectTo, $appBase.'/')
        || str_starts_with($redirectTo, $appBase.'?')
        || str_starts_with($redirectTo, $appBase.'#');
    $targetUrl = $isInternal ? $redirectTo : $previousUrl;

    return redirect()->to($targetUrl);
})->middleware('throttle:public-write')->name('language.switch');
Route::get('/shop', [UserShopController::class, 'shop'])->name('shop.index');
Route::get('/shop/autocomplete', [CatalogShopController::class, 'autocomplete'])->name('shop.autocomplete');
Route::get('/categories', [UserShopController::class, 'categories'])->name('categories.index');
Route::get('/categories/{category}', [UserShopController::class, 'category'])->name('categories.show');
Route::get('/shop/products/{product}', [CatalogShopController::class, 'show'])->name('shop.show');

// Landing pages a search engine can rank: one address per part brand and per
// car, rather than one shop URL wearing every filter combination.
Route::get('/brands', [CatalogLandingController::class, 'brands'])->name('catalog.brands');
Route::get('/brands/{slug}', [CatalogLandingController::class, 'brand'])
    ->where('slug', '[a-z0-9-]+')
    ->name('catalog.brand');
Route::get('/vehicles', [CatalogLandingController::class, 'vehicles'])->name('catalog.vehicles');
Route::get('/vehicles/{make}', [CatalogLandingController::class, 'vehicleBrand'])
    ->where('make', '[a-z0-9-]+')
    ->name('catalog.vehicle-brand');
Route::get('/vehicles/{make}/{model}', [CatalogLandingController::class, 'vehicleModel'])
    ->where(['make' => '[a-z0-9-]+', 'model' => '[a-z0-9-]+'])
    ->name('catalog.vehicle-model');

Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/support', [LegalController::class, 'support'])->name('legal.support');
Route::get('/about-us', [LegalController::class, 'about'])->name('legal.about');
Route::get('/vision', [LegalController::class, 'vision'])->name('legal.vision');
Route::get('/contact', [LegalController::class, 'contact'])->name('legal.contact');
Route::post('/contact', [LegalController::class, 'sendContact'])
    ->middleware('throttle:6,1')
    ->name('legal.contact.send');
Route::get('/contact-us', function () {
    return redirect()->route('legal.contact');
});
Route::get('/return-exchange', [LegalController::class, 'returnExchange'])->name('legal.return-exchange');
Route::get('/shipping-delivery', [LegalController::class, 'shippingDelivery'])->name('legal.shipping-delivery');
Route::get('/distance-sales-agreement', [LegalController::class, 'distanceSalesAgreement'])->name('legal.distance-sales-agreement');
Route::get('/brand/logo', function () {
    $logoPath = Branding::storagePathFromValue((string) Setting::getValue('site_logo', ''));

    if (! Branding::isSafeLogoPath($logoPath) || ! Storage::disk('public')->exists($logoPath)) {
        abort(404);
    }

    $mimeType = Branding::safeLogoMimeType($logoPath);
    if ($mimeType === null) {
        abort(404);
    }

    return response()->file(
        storage_path('app/public/'.$logoPath),
        [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($logoPath).'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]
    );
})->name('brand.logo');

// Every icon slot in the head resolves here when a logo is configured, so one
// upload changes the tab, the home-screen shortcut and the search result alike.
// Sizes are whitelisted in BrandIcon; anything else is a 404.
Route::get('/brand/icon-{size}.png', function (int $size) {
    $png = BrandIcon::render($size);

    if ($png === null) {
        abort(404);
    }

    return response($png, 200, [
        'Content-Type' => 'image/png',
        // The URL carries a version stamp, so the file behind it never changes
        // and can be held for a year.
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->whereNumber('size')->name('brand.icon');

// The packaged site.webmanifest points at fixed icon files, so an admin logo
// change never reached an installed home-screen shortcut. This serves the same
// document with the current logo instead.
Route::get('/brand/manifest.webmanifest', function () {
    $settings = Setting::allWithDefaults();
    $name = trim((string) ($settings['site_name'] ?? '')) ?: (string) config('app.name', 'Yalla Spare');

    $logoUrl = Branding::versionedLogoUrl(
        (string) ($settings['site_logo'] ?? ''),
        (string) ($settings['site_logo_version'] ?? '')
    );

    $iconVersion = BrandIcon::version();

    if ($iconVersion !== null) {
        // Real pixel sizes now, because these are rendered square at exactly
        // the size claimed rather than being whatever the admin uploaded.
        $icons = collect([192, 512])
            ->map(fn (int $size) => [
                'src' => url(route('brand.icon', ['size' => $size], false)).'?v='.$iconVersion,
                'sizes' => $size.'x'.$size,
                'type' => 'image/png',
            ])
            ->all();
    } elseif ($logoUrl !== null) {
        $mimeType = Branding::safeLogoMimeType(
            Branding::storagePathFromValue((string) ($settings['site_logo'] ?? ''))
        );

        $icons = [[
            'src' => url($logoUrl),
            'sizes' => 'any',
            'type' => $mimeType ?? 'image/png',
        ]];
    } else {
        $icons = [
            ['src' => url('/android-chrome-192x192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => url('/android-chrome-512x512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
        ];
    }

    return response()->json([
        'name' => $name,
        'short_name' => $name,
        'icons' => $icons,
        'theme_color' => '#070740',
        'background_color' => '#070740',
        'display' => 'standalone',
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('brand.manifest');

// A visitor fills a cart before deciding whether to have an account. The cart
// belongs to their session until they sign in, and CartService is what knows
// which of the two it is.
Route::post('/cart/{product}', [CartController::class, 'add'])->middleware('throttle:commerce-write')->name('cart.add');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])->middleware('throttle:commerce-write')->name('cart.update');
Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->middleware('throttle:commerce-write')->name('cart.remove');

// Checkout without an account. The visitor gives their details once and
// proves the phone with a code; ExpressCheckoutController explains what that
// buys and why the account is made for them rather than asked for.
Route::middleware('guest')->group(function () {
    Route::get('/checkout/express', [ExpressCheckoutController::class, 'show'])->name('checkout.express');
    Route::post('/checkout/express', [ExpressCheckoutController::class, 'store'])
        ->middleware('throttle:express-checkout-code')
        ->name('checkout.express.store');
    Route::get('/checkout/express/verify', [ExpressCheckoutController::class, 'verifyForm'])->name('checkout.express.verify');
    Route::post('/checkout/express/verify', [ExpressCheckoutController::class, 'verify'])
        ->middleware('throttle:phone-verification-check')
        ->name('checkout.express.verify.store');
    Route::post('/checkout/express/resend', [ExpressCheckoutController::class, 'resend'])
        ->middleware('throttle:express-checkout-code')
        ->name('checkout.express.resend');
});

Route::middleware(['auth', 'verified', 'customer.area', 'customer.phone', 'customer.phone.verified', 'user.2fa'])->group(function () {
    Route::get('/checkout/options/{product}', [CheckoutController::class, 'options'])->name('checkout.options');
    Route::match(['get', 'post'], '/checkout/buy-now/{product}', [CheckoutController::class, 'buyNow'])->middleware('throttle:checkout-write')->name('checkout.buy-now');
    Route::post('/checkout/buy-now/{product}/place', [CheckoutController::class, 'placeBuyNow'])->middleware('throttle:checkout-write')->name('checkout.buy-now.place');
    Route::get('/checkout/delivery', [CheckoutController::class, 'delivery'])->name('checkout.delivery');
    Route::match(['get', 'post'], '/checkout/review', [CheckoutController::class, 'review'])->middleware('throttle:checkout-write')->name('checkout.review');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout-write')->name('checkout.store');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/payments/{payment}/return', PaymentReturnController::class)->name('payments.return');
    Route::post('/shop/products/{product}/back-in-stock', [CatalogShopController::class, 'subscribeBackInStock'])->middleware('throttle:commerce-write')->name('shop.back-in-stock.store');
    Route::delete('/shop/products/{product}/back-in-stock', [CatalogShopController::class, 'unsubscribeBackInStock'])->middleware('throttle:commerce-write')->name('shop.back-in-stock.destroy');
    Route::post('/shop/products/{product}/reviews', [ProductReviewController::class, 'store'])->middleware('throttle:commerce-write')->name('shop.reviews.store');
});

Route::middleware(['auth', 'verified', 'customer.area', 'customer.phone', 'customer.phone.verified', 'user.2fa'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('user.account.edit');
    })->name('index');
    Route::get('/orders', [AccountOrdersController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AccountOrdersController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [AccountOrdersController::class, 'invoice'])->name('orders.invoice');
    Route::post('/orders/{order}/reorder', [AccountOrdersController::class, 'reorder'])->middleware('throttle:commerce-write')->name('orders.reorder');
    Route::post('/orders/{order}/cancellation-request', [AccountOrdersController::class, 'requestCancellation'])->middleware('throttle:commerce-write')->name('orders.cancellation-request');
    Route::post('/orders/{order}/return-request', [AccountOrdersController::class, 'requestReturn'])->middleware('throttle:commerce-write')->name('orders.return-request');
    Route::post('/credentials', [AccountCredentialsController::class, 'store'])
        ->middleware('throttle:commerce-write')
        ->name('credentials.store');
    Route::get('/addresses', [AccountAddressController::class, 'index'])->name('addresses.index');
    Route::get('/addresses/create', [AccountAddressController::class, 'create'])->name('addresses.create');
    Route::post('/addresses', [AccountAddressController::class, 'store'])->middleware('throttle:commerce-write')->name('addresses.store');
    Route::get('/addresses/{address}/edit', [AccountAddressController::class, 'edit'])->name('addresses.edit');
    Route::put('/addresses/{address}', [AccountAddressController::class, 'update'])->middleware('throttle:commerce-write')->name('addresses.update');
    Route::patch('/addresses/{address}/default', [AccountAddressController::class, 'setDefault'])->middleware('throttle:commerce-write')->name('addresses.default');
    Route::delete('/addresses/{address}', [AccountAddressController::class, 'destroy'])->middleware('throttle:commerce-write')->name('addresses.destroy');
});

Route::prefix('user')->name('user.')->group(function () {
    Route::get('/home', [UserShopController::class, 'home'])->name('shop.home');
    Route::get('/shop', [UserShopController::class, 'shop'])->name('shop.index');
});

// Phone setup runs before email verification in the onboarding flow, so it
// must not require a verified email address.
Route::middleware(['auth', 'customer.area'])->prefix('user')->name('user.')->group(function () {
    Route::get('/phone/setup', [CustomerPhoneSetupController::class, 'show'])->name('phone.setup');
    Route::post('/phone/setup', [CustomerPhoneSetupController::class, 'store'])
        ->middleware('throttle:phone-verification-send')
        ->name('phone.store');
});

// These two endpoints intentionally skip the verified-phone gate: they are the
// actions an unverified user performs to become verified.
Route::middleware(['auth', 'verified', 'customer.area', 'customer.phone', 'user.2fa'])->prefix('user')->name('user.')->group(function () {
    Route::post('/account/phone/verification', [PhoneVerificationController::class, 'send'])
        ->middleware('throttle:phone-verification-send')
        ->name('account.phone-verification.send');
    Route::post('/account/phone/verification/confirm', [PhoneVerificationController::class, 'verify'])
        ->middleware('throttle:phone-verification-check')
        ->name('account.phone-verification.verify');
});

Route::middleware(['auth', 'verified', 'customer.area', 'customer.phone', 'customer.phone.verified', 'user.2fa'])->prefix('user')->name('user.')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'store'])->middleware('throttle:commerce-write')->name('wishlist.store');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->middleware('throttle:commerce-write')->name('wishlist.destroy');
    Route::get('/account', [UserAccountController::class, 'edit'])->name('account.edit');
    Route::get('/account/personal', [UserAccountController::class, 'personal'])->name('account.personal');
    Route::get('/account/addresses', [UserAccountController::class, 'addressesPage'])->name('account.addresses');
    Route::get('/account/security', [UserAccountController::class, 'securityPage'])->name('account.security');
    Route::get('/account/activity', [UserAccountController::class, 'activity'])->name('account.activity');
    Route::get('/account/actions', [UserAccountController::class, 'actions'])->name('account.actions');
    Route::patch('/account', [UserAccountController::class, 'update'])->middleware('throttle:commerce-write')->name('account.update');
    Route::patch('/account/password', [UserAccountController::class, 'password'])->middleware('throttle:commerce-write')->name('account.password');
    Route::get('/settings', [UserSettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [UserSettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/appearance', [UserSettingsController::class, 'appearance'])->name('settings.appearance');
    Route::patch('/settings/appearance', [UserSettingsController::class, 'updateAppearance'])->name('settings.appearance.update');
    Route::get('/settings/language', [UserSettingsController::class, 'language'])->name('settings.language');
    Route::patch('/settings/language', [UserSettingsController::class, 'updateLanguage'])->name('settings.language.update');
    Route::get('/settings/notifications', [UserSettingsController::class, 'notifications'])->name('settings.notifications');
    Route::patch('/settings/notifications', [UserSettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::get('/settings/security', [UserSettingsController::class, 'security'])->name('settings.security');
    Route::patch('/settings/security', [UserSettingsController::class, 'updateSecurity'])->name('settings.security.update');
    Route::post('/settings/security/global-signout', [UserSettingsController::class, 'globalSignOut'])
        ->middleware('throttle:commerce-write')
        ->name('settings.security.global-signout');
    Route::get('/settings/communication', [UserSettingsController::class, 'communication'])->name('settings.communication');
    Route::patch('/settings/communication', [UserSettingsController::class, 'updateCommunication'])->name('settings.communication.update');
    Route::get('/settings/checkout', [UserSettingsController::class, 'checkout'])->name('settings.checkout');
    Route::patch('/settings/checkout', [UserSettingsController::class, 'updateCheckout'])->name('settings.checkout.update');
    Route::get('/settings/accessibility', [UserSettingsController::class, 'accessibility'])->name('settings.accessibility');
    Route::patch('/settings/accessibility', [UserSettingsController::class, 'updateAccessibility'])->name('settings.accessibility.update');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user && $user->isAdminPanelUser()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('user.shop.home');
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'customer.phone', 'customer.phone.verified', 'user.2fa', 'admin.2fa'])->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::patch('/', [ProfileController::class, 'update'])->name('update');
    Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'verified', 'admin', 'admin.2fa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_DASHBOARD_VIEW)
            ->name('dashboard');
        Route::get('/goals', [ProgressCenterController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_GOALS_VIEW)
            ->name('goals.index');
        Route::post('/goals', [GoalController::class, 'store'])
            ->middleware(['can:'.User::PERMISSION_GOALS_MANAGE, 'throttle:admin-write'])
            ->name('goals.store');
        Route::put('/goals/{goal}', [GoalController::class, 'update'])
            ->middleware(['can:'.User::PERMISSION_GOALS_MANAGE, 'throttle:admin-write'])
            ->name('goals.update');
        Route::patch('/goals/{goal}/progress', [GoalController::class, 'updateProgress'])
            ->middleware(['can:'.User::PERMISSION_GOALS_MANAGE, 'throttle:admin-write'])
            ->name('goals.progress');
        Route::delete('/goals/{goal}', [GoalController::class, 'destroy'])
            ->middleware(['can:'.User::PERMISSION_GOALS_MANAGE, 'throttle:admin-write'])
            ->name('goals.destroy');
        Route::get('/profile', [AdminProfileController::class, 'edit'])
            ->name('profile.edit');
        Route::patch('/profile', [AdminProfileController::class, 'update'])
            ->middleware('throttle:admin-write')
            ->name('profile.update');
        Route::get('/revenue', [RevenueController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_FINANCE_VIEW)
            ->name('revenue.index');
        Route::get('/revenue/export', [RevenueController::class, 'export'])
            ->middleware('can:'.User::PERMISSION_FINANCE_VIEW)
            ->name('revenue.export');
        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_DASHBOARD_VIEW)
            ->name('analytics.index');
        Route::get('/purchase-planning', [OperationsInsightController::class, 'purchasePlanning'])
            ->middleware('can:'.User::PERMISSION_STOCK_MANAGE)
            ->name('purchase-planning.index');
        Route::get('/product-requests', [OperationsInsightController::class, 'stockRequests'])
            ->middleware('can:stock-requests.manage')
            ->name('stock-requests.index');
        Route::patch('/product-requests/{product}/notify', [OperationsInsightController::class, 'markStockRequestsNotified'])
            ->middleware(['can:stock-requests.manage', 'throttle:admin-write'])
            ->name('stock-requests.notify');
        Route::get('/search-insights', [OperationsInsightController::class, 'searchInsights'])
            ->middleware('can:'.User::PERMISSION_DASHBOARD_VIEW)
            ->name('search-insights.index');
        Route::get('/dead-stock', [OperationsInsightController::class, 'deadStock'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('dead-stock.index');
        Route::get('/discounts', [DiscountCouponController::class, 'edit'])
            ->middleware('can:'.User::PERMISSION_FINANCE_MANAGE)
            ->name('discounts.edit');
        Route::get('/discounts/rules', [DiscountCouponController::class, 'rules'])
            ->middleware('can:'.User::PERMISSION_FINANCE_MANAGE)
            ->name('discounts.rules');
        Route::get('/discounts/products/search', [DiscountCouponController::class, 'searchProducts'])
            ->middleware('can:'.User::PERMISSION_FINANCE_MANAGE)
            ->name('discounts.products.search');
        Route::get('/discounts/coupons/create', [DiscountCouponController::class, 'createCoupon'])
            ->middleware('can:'.User::PERMISSION_FINANCE_MANAGE)
            ->name('discounts.coupons.create');
        Route::patch('/discounts/coupons/{coupon}', [DiscountCouponController::class, 'updateCoupon'])
            ->middleware(['can:'.User::PERMISSION_FINANCE_MANAGE, 'throttle:admin-write'])
            ->name('discounts.coupons.update');
        Route::patch('/discounts/coupons/{coupon}/toggle', [DiscountCouponController::class, 'toggleCoupon'])
            ->middleware(['can:'.User::PERMISSION_FINANCE_MANAGE, 'throttle:admin-write'])
            ->name('discounts.coupons.toggle');
        Route::delete('/discounts/coupons/{coupon}', [DiscountCouponController::class, 'destroyCoupon'])
            ->middleware(['can:'.User::PERMISSION_FINANCE_MANAGE, 'throttle:admin-write'])
            ->name('discounts.coupons.destroy');
        Route::put('/discounts', [DiscountCouponController::class, 'update'])
            ->middleware(['can:'.User::PERMISSION_FINANCE_MANAGE, 'throttle:admin-write'])
            ->name('discounts.update');
        Route::put('/discounts/rules', [DiscountCouponController::class, 'updateRules'])
            ->middleware(['can:'.User::PERMISSION_FINANCE_MANAGE, 'throttle:admin-write'])
            ->name('discounts.update-rules');
        Route::patch('/discounts/rules/{discount}/status', [DiscountCouponController::class, 'updateRuleStatus'])
            ->middleware(['can:'.User::PERMISSION_FINANCE_MANAGE, 'throttle:admin-write'])
            ->name('discounts.update-rule-status');
        Route::delete('/discounts/rules/{discount}', [DiscountCouponController::class, 'destroyRule'])
            ->middleware(['can:'.User::PERMISSION_FINANCE_MANAGE, 'throttle:admin-write'])
            ->name('discounts.destroy-rule');

        // Storefront announcement popups
        Route::get('/popups', [PopupController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('popups.index');
        Route::get('/popups/create', [PopupController::class, 'create'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('popups.create');
        Route::post('/popups', [PopupController::class, 'store'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->name('popups.store');
        Route::get('/popups/{popup}/edit', [PopupController::class, 'edit'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('popups.edit');
        Route::put('/popups/{popup}', [PopupController::class, 'update'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->name('popups.update');
        Route::patch('/popups/{popup}/toggle', [PopupController::class, 'toggle'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->name('popups.toggle');
        Route::delete('/popups/{popup}', [PopupController::class, 'destroy'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->name('popups.destroy');

        // Products
        Route::resource('products', ProductController::class)
            ->except(['show'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write']);
        Route::post('/products/import', [ProductController::class, 'import'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('products.import');
        Route::get('/products/export-excel', [ProductController::class, 'exportExcel'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('products.export-excel');
        Route::get('/products/{productIdentifier}', [ProductController::class, 'editByIdentifier'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('products.edit-by-identifier');

        // Product brands
        Route::get('/product-brands', [ProductBrandController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('product-brands.index');
        Route::post('/product-brands', [ProductBrandController::class, 'store'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('product-brands.store');
        Route::put('/product-brands/{productBrand}', [ProductBrandController::class, 'update'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('product-brands.update');
        Route::delete('/product-brands/{productBrand}', [ProductBrandController::class, 'destroy'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('product-brands.destroy');

        // Categories
        Route::resource('categories', CategoryController::class)
            ->except(['show'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write']);
        Route::post('/categories/import', [CategoryController::class, 'import'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('categories.import');
        Route::get('/categories/export-excel', [CategoryController::class, 'exportExcel'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('categories.export-excel');

        // Orders
        // IMPORTANT: static segments like /orders/export-excel must be registered
        // BEFORE Route::resource('orders'), otherwise the resource's /orders/{order}
        // show route swallows them and route-model-binding returns 404.
        Route::get('/orders/export-excel', [OrderController::class, 'exportExcel'])
            ->middleware('can:'.User::PERMISSION_ORDERS_MANAGE)
            ->name('orders.export-excel');
        Route::resource('orders', OrderController::class)
            ->only(['index', 'show', 'update', 'destroy'])
            ->middleware(['can:'.User::PERMISSION_ORDERS_MANAGE, 'throttle:admin-write']);
        Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])
            ->middleware('can:'.User::PERMISSION_ORDERS_MANAGE)
            ->name('orders.invoice');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->middleware(['can:'.User::PERMISSION_ORDERS_MANAGE, 'throttle:admin-write'])
            ->name('orders.update-status');
        Route::patch('/orders/{order}/shipment', [OrderController::class, 'updateShipment'])
            ->middleware(['can:'.User::PERMISSION_ORDERS_MANAGE, 'throttle:admin-write'])
            ->name('orders.update-shipment');
        Route::post('/orders/bulk-status', [OrderController::class, 'bulkUpdateStatus'])
            ->middleware(['can:'.User::PERMISSION_ORDERS_MANAGE, 'throttle:admin-write'])
            ->name('orders.bulk-status');
        Route::patch('/orders/{order}/payment', [OrderController::class, 'updatePayment'])
            ->middleware(['can:'.User::PERMISSION_ORDERS_MANAGE, 'throttle:admin-write'])
            ->name('orders.update-payment');
        Route::post('/orders/{order}/payments/{payment}/verify-wayl', [OrderController::class, 'verifyWaylPayment'])
            ->middleware(['can:'.User::PERMISSION_ORDERS_MANAGE, 'throttle:admin-write'])
            ->name('orders.payments.verify-wayl');
        Route::post('/orders/{order}/admin-notes', [OrderController::class, 'storeAdminNote'])
            ->middleware(['can:'.User::PERMISSION_ORDERS_MANAGE, 'throttle:admin-write'])
            ->name('orders.admin-notes.store');

        // Returns / Refunds
        Route::get('/returns', [ReturnRequestController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_ORDERS_MANAGE)
            ->name('returns.index');
        Route::get('/returns/export-excel', [ReturnRequestController::class, 'exportExcel'])
            ->middleware('can:'.User::PERMISSION_ORDERS_MANAGE)
            ->name('returns.export-excel');
        Route::patch('/returns/{return}', [ReturnRequestController::class, 'update'])
            ->middleware(['can:'.User::PERMISSION_ORDERS_MANAGE, 'throttle:admin-write'])
            ->name('returns.update');
        Route::post('/returns/bulk-update', [ReturnRequestController::class, 'bulkUpdate'])
            ->middleware(['can:'.User::PERMISSION_ORDERS_MANAGE, 'throttle:admin-write'])
            ->name('returns.bulk-update');

        // Reviews
        Route::get('/reviews', [AdminProductReviewController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('reviews.index');
        Route::get('/reviews/export-excel', [AdminProductReviewController::class, 'exportExcel'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('reviews.export-excel');
        Route::delete('/reviews/{review}', [AdminProductReviewController::class, 'destroy'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('reviews.destroy');

        // Vehicle Finder
        Route::get('/vehicle-fitments', [VehicleFitmentController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('vehicle-fitments.index');
        Route::get('/vehicle-fitments/products/search', [VehicleFitmentController::class, 'searchProducts'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('vehicle-fitments.products.search');
        Route::post('/vehicle-fitments/brands', [VehicleFitmentController::class, 'storeBrand'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.brands.store');
        Route::get('/vehicle-fitments/models/create', [VehicleFitmentController::class, 'createModel'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('vehicle-fitments.models.create');
        Route::get('/vehicle-fitments/models/{model}/edit', [VehicleFitmentController::class, 'editModel'])
            ->middleware('can:'.User::PERMISSION_PRODUCTS_MANAGE)
            ->name('vehicle-fitments.models.edit');
        Route::post('/vehicle-fitments/models', [VehicleFitmentController::class, 'storeModel'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.models.store');
        Route::patch('/vehicle-fitments/brands/{brand}', [VehicleFitmentController::class, 'updateBrand'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.brands.update');
        Route::patch('/vehicle-fitments/models/{model}', [VehicleFitmentController::class, 'updateModel'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.models.update');
        Route::patch('/vehicle-fitments/families/{family}', [VehicleFitmentController::class, 'updateFamily'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.families.update');
        Route::delete('/vehicle-fitments/families/{family}', [VehicleFitmentController::class, 'destroyFamily'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.families.destroy');
        Route::delete('/vehicle-fitments/brands/{brand}', [VehicleFitmentController::class, 'destroyBrand'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.brands.destroy');
        Route::delete('/vehicle-fitments/models/{model}', [VehicleFitmentController::class, 'destroyModel'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.models.destroy');
        Route::post('/vehicle-fitments', [VehicleFitmentController::class, 'storeFitment'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.store');
        Route::delete('/vehicle-fitments/{fitment}', [VehicleFitmentController::class, 'destroyFitment'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.destroy');

        // Users
        Route::get('/users', [UserController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_USERS_VIEW)
            ->name('users.index');
        Route::get('/users/export-excel', [UserController::class, 'exportExcel'])
            ->middleware('can:'.User::PERMISSION_USERS_VIEW)
            ->name('users.export-excel');
        Route::get('/users/{user}', [UserController::class, 'show'])
            ->middleware('can:manage-users')
            ->name('users.show');
        Route::patch('/users/{user}', [UserController::class, 'updateDetails'])
            ->middleware(['can:manage-users', 'throttle:admin-write'])
            ->name('users.update-details');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])
            ->middleware(['can:manage-users', 'password.confirm', 'throttle:admin-write'])
            ->name('users.update-role');
        Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])
            ->middleware(['can:manage-users', 'password.confirm', 'throttle:admin-write'])
            ->name('users.update-password');
        Route::patch('/users/{user}/ban', [UserController::class, 'updateBan'])
            ->middleware(['can:manage-users', 'throttle:admin-write'])
            ->name('users.update-ban');
        Route::delete('/users/{user}/ban', [UserController::class, 'destroyBan'])
            ->middleware(['can:manage-users', 'throttle:admin-write'])
            ->name('users.destroy-ban');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->middleware(['can:manage-users', 'password.confirm', 'throttle:admin-write'])
            ->name('users.destroy');

        // Dealers
        Route::get('/dealers', [DealerController::class, 'index'])
            ->middleware('can:manage-dealers')
            ->name('dealers.index');
        Route::patch('/dealers/{dealer}', [DealerController::class, 'update'])
            ->middleware(['can:manage-dealers', 'throttle:admin-write'])
            ->name('dealers.update');
        Route::patch('/dealers/{dealer}/demote', [DealerController::class, 'demote'])
            ->middleware(['can:manage-dealers', 'throttle:admin-write'])
            ->name('dealers.demote');

        // Inventory Movements
        Route::get('/inventory/movements', [InventoryMovementController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_STOCK_MANAGE)
            ->name('inventory.index');
        Route::post('/inventory/movements', [InventoryMovementController::class, 'store'])
            ->middleware(['can:'.User::PERMISSION_STOCK_MANAGE, 'throttle:admin-write'])
            ->name('inventory.store');
        Route::get('/inventory/movements/export', [InventoryMovementController::class, 'export'])
            ->middleware('can:'.User::PERMISSION_STOCK_MANAGE)
            ->name('inventory.export');

        // Bulk stock adjustment. Reviewing costs nothing and reads only, so it
        // sits behind the same permission but not the write throttle; applying
        // does.
        Route::get('/inventory/bulk-stock', [BulkStockAdjustmentController::class, 'create'])
            ->middleware('can:'.User::PERMISSION_STOCK_MANAGE)
            ->name('inventory.bulk-stock');
        Route::post('/inventory/bulk-stock/preview', [BulkStockAdjustmentController::class, 'preview'])
            ->middleware('can:'.User::PERMISSION_STOCK_MANAGE)
            ->name('inventory.bulk-stock.preview');
        Route::post('/inventory/bulk-stock', [BulkStockAdjustmentController::class, 'store'])
            ->middleware(['can:'.User::PERMISSION_STOCK_MANAGE, 'throttle:admin-write'])
            ->name('inventory.bulk-stock.apply');

        // System Settings
        Route::get('/settings', [SettingController::class, 'edit'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'password.confirm', 'throttle:admin-write'])
            ->name('settings.update');

        // Shipping — per governorate
        Route::get('/shipping/governorates', [GovernorateShippingController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('shipping.governorates');
        Route::put('/shipping/governorates', [GovernorateShippingController::class, 'update'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->name('shipping.governorates.update');
        Route::post('/shipping/governorates', [GovernorateShippingController::class, 'store'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->name('shipping.governorates.store');
        Route::delete('/shipping/governorates/{governorate}', [GovernorateShippingController::class, 'destroy'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->name('shipping.governorates.destroy');
        Route::get('/email', [EmailController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('email.index');
        Route::get('/messaging', [MessagingController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('messaging.index');
        Route::post('/messaging/test', [MessagingController::class, 'sendTest'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:phone-verification-send'])
            ->name('messaging.test');
        Route::get('/integrations/wayl', [WaylPaymentController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_FINANCE_VIEW)
            ->name('wayl.index');
        Route::post('/integrations/wayl/health', [WaylPaymentController::class, 'health'])
            ->middleware(['can:'.User::PERMISSION_FINANCE_MANAGE, 'throttle:admin-write'])
            ->name('wayl.health');
        Route::post('/integrations/wayl/diagnostics/create-link', [WaylPaymentController::class, 'createLinkDiagnostic'])
            ->middleware(['can:'.User::PERMISSION_FINANCE_MANAGE, 'throttle:admin-write'])
            ->name('wayl.diagnostics.create-link');
        Route::get('/whatsapp', [OtpiqWhatsAppController::class, 'index'])
            ->middleware('can:manage-whatsapp-webhooks')
            ->name('whatsapp.index');
        Route::get('/whatsapp/events', [OtpiqWhatsAppController::class, 'events'])
            ->middleware('can:manage-whatsapp-webhooks')
            ->name('whatsapp.events.index');
        Route::get('/whatsapp/events/{event}', [OtpiqWhatsAppController::class, 'show'])
            ->middleware('can:manage-whatsapp-webhooks')
            ->name('whatsapp.events.show');
        Route::patch('/whatsapp/events/{event}/read', [OtpiqWhatsAppController::class, 'markRead'])
            ->middleware(['can:manage-whatsapp-webhooks', 'throttle:admin-write'])
            ->name('whatsapp.events.read');
        Route::patch('/whatsapp/events/{event}/unread', [OtpiqWhatsAppController::class, 'markUnread'])
            ->middleware(['can:manage-whatsapp-webhooks', 'throttle:admin-write'])
            ->name('whatsapp.events.unread');
        Route::patch('/whatsapp/events/{event}/archive', [OtpiqWhatsAppController::class, 'archive'])
            ->middleware(['can:manage-whatsapp-webhooks', 'throttle:admin-write'])
            ->name('whatsapp.events.archive');
        Route::post('/whatsapp/events/{event}/retry', [OtpiqWhatsAppController::class, 'retry'])
            ->middleware(['can:manage-whatsapp-webhooks', 'throttle:admin-write'])
            ->name('whatsapp.events.retry');
        Route::patch('/whatsapp/processing', [OtpiqWhatsAppController::class, 'updateProcessing'])
            ->middleware(['can:manage-whatsapp-webhooks', 'throttle:admin-write'])
            ->name('whatsapp.processing.update');
        Route::get('/email/outbox', [EmailController::class, 'outbox'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('email.outbox');
        Route::post('/email/test', [EmailController::class, 'sendTest'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->name('email.test');
        Route::get('/email/broadcasts/create', [EmailController::class, 'createBroadcast'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('email.broadcasts.create');
        Route::post('/email/broadcast', [EmailController::class, 'sendBroadcast'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->name('email.broadcast');
        Route::get('/email/preview/{template}', [EmailController::class, 'preview'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->where('template', '[a-z0-9-]+')
            ->name('email.preview');

        // Email template editor
        Route::get('/email/templates', [EmailTemplateController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->name('email.templates.index');
        Route::get('/email/templates/{key}/{locale}/edit', [EmailTemplateController::class, 'edit'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->where(['key' => '[a-z0-9-]+', 'locale' => 'en|ar|ku'])
            ->name('email.templates.edit');
        Route::patch('/email/templates/{key}/{locale}', [EmailTemplateController::class, 'update'])
            ->middleware(['can:'.User::PERMISSION_SETTINGS_MANAGE, 'throttle:admin-write'])
            ->where(['key' => '[a-z0-9-]+', 'locale' => 'en|ar|ku'])
            ->name('email.templates.update');
        Route::get('/email/templates/{key}/{locale}/preview', [EmailTemplateController::class, 'preview'])
            ->middleware('can:'.User::PERMISSION_SETTINGS_MANAGE)
            ->where(['key' => '[a-z0-9-]+', 'locale' => 'en|ar|ku'])
            ->name('email.templates.preview');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_DASHBOARD_VIEW)
            ->name('notifications.index');
        Route::post('/notifications/read', [NotificationController::class, 'markRead'])
            ->middleware('can:'.User::PERMISSION_DASHBOARD_VIEW)
            ->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->middleware('can:'.User::PERMISSION_DASHBOARD_VIEW)
            ->name('notifications.read-all');

        // Low stock
        Route::get('/low-stock/count', [LowStockController::class, 'count'])
            ->middleware('can:'.User::PERMISSION_DASHBOARD_VIEW)
            ->name('low-stock.count');

        // Activity logs
        Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])
            ->middleware('can:'.User::PERMISSION_ACTIVITY_LOGS_VIEW)
            ->name('activity-logs.index');
        Route::get('/activity-logs/export-excel', [AdminActivityLogController::class, 'exportExcel'])
            ->middleware('can:'.User::PERMISSION_ACTIVITY_LOGS_VIEW)
            ->name('activity-logs.export-excel');
    });

Route::post('/csp-report', [CspReportController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('csp.report');

require __DIR__.'/auth.php';
