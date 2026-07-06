<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\SearchAnalytic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchInsightsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function seedKeywords(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Brake Pad Set',
            'stock_quantity' => 5,
        ]);

        SearchAnalytic::query()->create([
            'keyword' => 'brake pad',
            'search_count' => 40,
            'last_searched_at' => now()->subHour(),
        ]);

        SearchAnalytic::query()->create([
            'keyword' => 'gearbox oil 75w90',
            'search_count' => 25,
            'last_searched_at' => now()->subDays(20),
        ]);
    }

    public function test_search_insights_page_renders_register_and_gap_panel(): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeywords();

        $response = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.search-insights.index'));

        $response->assertOk();
        $response->assertSee('Search Terminal');
        $response->assertSee('brake pad');
        $response->assertSee('gearbox oil 75w90');
        $response->assertSee('0 HIT');
        $response->assertSee('Coverage Gap Register');
        $response->assertSee('Add Product');
        $response->assertSee('Demand Pulse');
    }

    public function test_zero_hit_filter_shows_only_unmatched_keywords(): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeywords();

        $response = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.search-insights.index', ['zero_hit' => 1]));

        $response->assertOk();
        // Register rows render keywords in a font-bold span; the hero's global
        // "Top Keyword" still says "brake pad", so scope the check to the row markup.
        $response->assertSee('font-bold text-slate-900 dark:text-slate-100">gearbox oil 75w90<', false);
        $response->assertDontSee('font-bold text-slate-900 dark:text-slate-100">brake pad<', false);
    }

    public function test_window_filter_hides_stale_keywords(): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeywords();

        $response = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.search-insights.index', ['window' => 7]));

        $response->assertOk();
        $response->assertSee('brake pad');
        $response->assertDontSee('gearbox oil 75w90');
    }
}
