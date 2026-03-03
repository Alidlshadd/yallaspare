<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\InventoryMovementController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\LowStockController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LegalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/support', [LegalController::class, 'support'])->name('legal.support');

Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::patch('/', [ProfileController::class, 'update'])->name('update');
    Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
});


Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Products
        Route::resource('products', ProductController::class)->except(['show']);
        Route::post('/products/import', [ProductController::class, 'import'])
            ->name('products.import');
        Route::get('/products/export-excel', [ProductController::class, 'exportExcel'])
            ->name('products.export-excel');

        // Categories
        Route::resource('categories', CategoryController::class)->except(['show']);

        // Orders
        Route::resource('orders', OrderController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.update-status');

        // Users
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])
            ->middleware('can:manage-users')
            ->name('users.show');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])
            ->middleware('can:manage-users')
            ->name('users.update-role');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->middleware('can:manage-users')
            ->name('users.destroy');

        // Dealers
        Route::get('/dealers', [DealerController::class, 'index'])
            ->middleware('can:manage-dealers')
            ->name('dealers.index');
        Route::patch('/dealers/{dealer}', [DealerController::class, 'update'])
            ->middleware('can:manage-dealers')
            ->name('dealers.update');
        Route::patch('/dealers/{dealer}/demote', [DealerController::class, 'demote'])
            ->middleware('can:manage-dealers')
            ->name('dealers.demote');

        // Inventory Movements
        Route::get('/inventory/movements', [InventoryMovementController::class, 'index'])
            ->name('inventory.index');
        Route::post('/inventory/movements', [InventoryMovementController::class, 'store'])
            ->name('inventory.store');

        // System Settings
        Route::get('/settings', [SettingController::class, 'edit'])
            ->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])
            ->name('settings.update');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::post('/notifications/read', [NotificationController::class, 'markRead'])
            ->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->name('notifications.read-all');

        // Low stock
        Route::get('/low-stock/count', [LowStockController::class, 'count'])
            ->name('low-stock.count');

        // Activity logs
        Route::get('/activity-logs', AdminActivityLogController::class)
            ->name('activity-logs.index');
    });

require __DIR__ . '/auth.php';
