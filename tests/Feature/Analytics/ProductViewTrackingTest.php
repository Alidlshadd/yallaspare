<?php

namespace Tests\Feature\Analytics;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    private string $browserUserAgent = 'Mozilla/5.0 Chrome/121 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create(['id' => 1]);
    }

    public function test_product_detail_records_one_view_per_recent_session(): void
    {
        $product = Product::factory()->create(['name_en' => 'Tracked Brake Pad']);

        $this->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->get(route('shop.show', $product))
            ->assertOk();

        $this->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->get(route('shop.show', $product))
            ->assertOk();

        $this->assertDatabaseCount('product_views', 1);
        $this->assertSame(1, DB::table('analytics_events')->where('event_type', 'product_view')->count());
        $this->assertSame(1, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('views_count'));
    }

    public function test_logged_in_product_view_stores_user_context(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = Product::factory()->create(['name_en' => 'Customer Viewed Filter']);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->get(route('shop.show', $product))
            ->assertOk();

        $this->assertDatabaseHas('product_views', [
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'product_view',
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_bot_product_view_is_ignored(): void
    {
        $product = Product::factory()->create(['name_en' => 'Bot Ignored Filter']);

        $this->withServerVariables(['HTTP_USER_AGENT' => 'Googlebot/2.1'])
            ->get(route('shop.show', $product))
            ->assertOk();

        $this->assertDatabaseCount('product_views', 0);
        $this->assertSame(0, DB::table('analytics_events')->where('event_type', 'product_view')->count());
        $this->assertSame(0, DB::table('product_analytics')->where('product_id', $product->id)->count());
    }

    public function test_admin_product_pages_show_view_count(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
        $product = Product::factory()->create(['name_en' => 'Admin Visible Views']);

        DB::table('product_analytics')->insert([
            'product_id' => $product->id,
            'views_count' => 12,
            'add_to_cart_count' => 0,
            'wishlist_count' => 0,
            'last_viewed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['search' => 'Admin Visible Views']))
            ->assertOk()
            ->assertSee('12 views');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Product Analytics')
            ->assertSee('12');
    }
}
