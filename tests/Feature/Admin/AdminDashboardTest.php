<?php

namespace Tests\Feature\Admin;

use App\Models\AdminNotificationRead;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\LowStockNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_with_sqlite_compatible_analytics(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('admin-sidebar-collapsed', false);
        $response->assertSee('admin-sidebar', false);
        $response->assertSee('data-admin-sidebar-toggle', false);
        $response->assertSee('Toggle sidebar', false);
    }

    public function test_low_stock_notifications_use_sqlite_compatible_expressions(): void
    {
        Cache::flush();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
        $category = Category::factory()->create();
        Setting::setValue('low_stock_threshold', '5');

        $lowStockProduct = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 2,
            'low_stock_threshold' => null,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 10,
            'low_stock_threshold' => null,
            'is_active' => true,
        ]);

        $service = app(LowStockNotificationService::class);

        $this->assertSame(1, $service->getUnreadLowStockCount($admin->id));

        AdminNotificationRead::query()->create([
            'user_id' => $admin->id,
            'notification_key' => $service->makeKey($lowStockProduct->id),
            'read_at' => now(),
        ]);
        Cache::flush();

        $this->assertSame(0, $service->getUnreadLowStockCount($admin->id));
    }
}
