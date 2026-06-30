<?php

namespace Tests\Feature\Analytics;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create(['id' => 1]);
    }

    public function test_admin_can_open_analytics_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Site Analytics')
            ->assertSee('Page Views')
            ->assertSee('Add to Cart');
    }

    public function test_finance_manager_can_open_analytics_dashboard(): void
    {
        $financeManager = User::factory()->create([
            'role' => User::ROLE_FINANCE_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($financeManager)
            ->get(route('admin.analytics.index'))
            ->assertOk();
    }

    public function test_product_manager_cannot_open_analytics_dashboard(): void
    {
        $productManager = User::factory()->create([
            'role' => User::ROLE_PRODUCT_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($productManager)
            ->get(route('admin.analytics.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.analytics.index'))
            ->assertRedirect(route('login'));
    }

    public function test_days_query_parameter_renders_with_valid_value(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['days' => 7]))
            ->assertOk()
            ->assertSee('Last 7 days');

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['days' => 999]))
            ->assertOk()
            ->assertSee('Last 30 days');
    }

    public function test_dashboard_surfaces_real_event_data(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
        $product = Product::factory()->create(['name_en' => 'Brake Pad Test']);

        DB::table('analytics_events')->insert([
            ['event_type' => 'product_view', 'product_id' => $product->id, 'created_at' => now()],
            ['event_type' => 'product_view', 'product_id' => $product->id, 'created_at' => now()],
            ['event_type' => 'add_to_cart', 'product_id' => $product->id, 'created_at' => now()],
        ]);

        DB::table('search_analytics')->insert([
            'keyword' => 'oil filter',
            'search_count' => 5,
            'last_searched_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Force a fresh snapshot (cache may hold stale data between assertions).
        cache()->forget('analytics.snapshot.30');

        $response = $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk();

        $response->assertSee('Brake Pad Test');
        $response->assertSee('oil filter');
    }
}
