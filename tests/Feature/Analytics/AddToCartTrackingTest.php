<?php

namespace Tests\Feature\Analytics;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AddToCartTrackingTest extends TestCase
{
    use RefreshDatabase;

    private string $browserUserAgent = 'Mozilla/5.0 Chrome/121 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create(['id' => 1]);
    }

    public function test_adding_to_cart_records_event_and_increments_counter(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 10]);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->post(route('cart.add', $product), ['quantity' => 2])
            ->assertRedirect();

        $this->assertSame(1, DB::table('analytics_events')
            ->where('event_type', 'add_to_cart')
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->count());

        $this->assertSame(1, (int) DB::table('product_analytics')
            ->where('product_id', $product->id)
            ->value('add_to_cart_count'));
    }

    public function test_two_separate_adds_increment_counter_twice(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 50]);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->post(route('cart.add', $product), ['quantity' => 1])
            ->assertRedirect();

        $this->actingAs($user)
            ->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->post(route('cart.add', $product), ['quantity' => 3])
            ->assertRedirect();

        $this->assertSame(2, DB::table('analytics_events')
            ->where('event_type', 'add_to_cart')
            ->where('product_id', $product->id)
            ->count());

        $this->assertSame(2, (int) DB::table('product_analytics')
            ->where('product_id', $product->id)
            ->value('add_to_cart_count'));
    }

    public function test_bot_add_to_cart_is_ignored(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 10]);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_USER_AGENT' => 'Googlebot/2.1'])
            ->post(route('cart.add', $product), ['quantity' => 1])
            ->assertRedirect();

        $this->assertSame(0, DB::table('analytics_events')
            ->where('event_type', 'add_to_cart')
            ->count());
    }

    public function test_unauthenticated_add_does_not_track(): void
    {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 10]);

        $this->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->post(route('cart.add', $product), ['quantity' => 1])
            ->assertRedirect(route('login'));

        $this->assertSame(0, DB::table('analytics_events')
            ->where('event_type', 'add_to_cart')
            ->count());
    }
}
