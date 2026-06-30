<?php

namespace Tests\Feature\Analytics;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WishlistTrackingTest extends TestCase
{
    use RefreshDatabase;

    private string $browserUserAgent = 'Mozilla/5.0 Chrome/121 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create(['id' => 1]);
    }

    public function test_adding_to_wishlist_records_event_and_increments_counter(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = Product::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->post(route('user.wishlist.store', $product))
            ->assertRedirect();

        $this->assertSame(1, DB::table('analytics_events')
            ->where('event_type', 'wishlist_click')
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->count());

        $this->assertSame(1, (int) DB::table('product_analytics')
            ->where('product_id', $product->id)
            ->value('wishlist_count'));
    }

    public function test_bot_wishlist_add_is_ignored(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = Product::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_USER_AGENT' => 'AhrefsBot/7.0'])
            ->post(route('user.wishlist.store', $product))
            ->assertRedirect();

        $this->assertSame(0, DB::table('analytics_events')
            ->where('event_type', 'wishlist_click')
            ->count());
    }
}
