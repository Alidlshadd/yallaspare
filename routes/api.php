<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum', 'verified'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileController::class, 'login'])->middleware('throttle:mobile-login');
    Route::post('/register', [MobileController::class, 'register'])->middleware('throttle:mobile-register');
    Route::post('/forgot-password', [MobileController::class, 'forgotPassword'])->middleware('throttle:mobile-password-reset');

    Route::get('/categories', [MobileController::class, 'categories']);
    Route::get('/brands', [MobileController::class, 'brands']);
    Route::get('/vehicle-fitments', [MobileController::class, 'vehicleFitments']);
    Route::post('/vin/decode', [MobileController::class, 'decodeVin'])->middleware('throttle:mobile-lookup');
    Route::get('/products', [MobileController::class, 'products']);
    Route::get('/products/{idOrSlug}', [MobileController::class, 'product']);
    Route::post('/coupons/preview', [MobileController::class, 'couponPreview'])->middleware('throttle:mobile-lookup');

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::get('/me', [MobileController::class, 'me']);
        Route::post('/token/refresh', [MobileController::class, 'refreshToken']);
        Route::post('/logout', [MobileController::class, 'logout']);
        Route::patch('/profile', [MobileController::class, 'updateProfile']);
        Route::patch('/profile/password', [MobileController::class, 'updatePassword']);
        Route::delete('/profile', [MobileController::class, 'deleteProfile']);
        Route::get('/notifications', [MobileController::class, 'notifications']);
        Route::get('/dealer/dashboard', [MobileController::class, 'dealerDashboard']);
        Route::get('/dealer/products', [MobileController::class, 'dealerProducts']);
        Route::get('/dealer/orders', [MobileController::class, 'dealerOrders']);
        Route::patch('/dealer/products/{idOrSlug}/stock', [MobileController::class, 'dealerUpdateStock']);

        Route::get('/cart', [MobileController::class, 'cart']);
        Route::post('/cart/items', [MobileController::class, 'addCartItem']);
        Route::patch('/cart/items/{productId}', [MobileController::class, 'updateCartItem']);
        Route::delete('/cart/items/{productId}', [MobileController::class, 'deleteCartItem']);

        Route::get('/wishlist', [MobileController::class, 'wishlist']);
        Route::post('/wishlist/{idOrSlug}', [MobileController::class, 'addWishlist']);
        Route::delete('/wishlist/{idOrSlug}', [MobileController::class, 'deleteWishlist']);

        Route::get('/products/{idOrSlug}/reviews', [MobileController::class, 'reviews']);
        Route::post('/products/{idOrSlug}/reviews', [MobileController::class, 'storeReview']);

        Route::get('/addresses', [MobileController::class, 'addresses']);
        Route::post('/addresses', [MobileController::class, 'storeAddress']);
        Route::patch('/addresses/{address}', [MobileController::class, 'updateAddress']);
        Route::patch('/addresses/{address}/default', [MobileController::class, 'setDefaultAddress']);
        Route::delete('/addresses/{address}', [MobileController::class, 'deleteAddress']);

        Route::post('/checkout', [MobileController::class, 'checkout']);
        Route::get('/orders', [MobileController::class, 'orders']);
        Route::get('/orders/{order}', [MobileController::class, 'order']);

        Route::post('/orders/{order}/cancellation-request', [MobileController::class, 'requestCancellation']);
        Route::post('/orders/{order}/return-request', [MobileController::class, 'requestReturn']);

        Route::get('/admin/dashboard', [MobileController::class, 'adminDashboard']);
        Route::get('/admin/{section}', [MobileController::class, 'adminModule']);
        Route::patch('/admin/products/{idOrSlug}', [MobileController::class, 'adminUpdateProduct']);
        Route::patch('/admin/orders/{order}/status', [MobileController::class, 'adminUpdateOrderStatus']);
        Route::patch('/admin/users/{user}/role', [MobileController::class, 'adminUpdateUserRole']);
        Route::patch('/admin/dealers/{user}', [MobileController::class, 'adminUpdateDealer']);
        Route::post('/admin/inventory/movements', [MobileController::class, 'adminCreateInventoryMovement']);
    });
});
