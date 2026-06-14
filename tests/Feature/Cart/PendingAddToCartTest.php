<?php

namespace Tests\Feature\Cart;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingAddToCartTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $stock = 5): Product
    {
        return Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock_quantity' => $stock,
            'price' => 10000,
            'is_active' => true,
        ]);
    }

    public function test_guest_add_to_cart_redirects_to_login(): void
    {
        $product = $this->product();

        $response = $this->post(route('cart.add', $product), ['quantity' => 2]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', __('Please sign in or register to add items to your cart.'));
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    public function test_guest_add_to_cart_stores_exactly_one_pending_action(): void
    {
        $first = $this->product();
        $second = $this->product();

        $this->post(route('cart.add', $first), ['quantity' => 1]);
        $this->post(route('cart.add', $second), ['quantity' => 3]);

        $pending = session('pending_cart_action');
        $this->assertIsArray($pending);
        $this->assertSame($second->id, $pending['product_id']);
        $this->assertSame(3, $pending['quantity']);
        $this->assertArrayHasKey('expires_at', $pending);
        $this->assertArrayHasKey('redirect_to', $pending);
        $this->assertArrayNotHasKey('items', $pending);
        $this->assertArrayNotHasKey('price', $pending);
    }

    public function test_login_then_resume_adds_product_to_db_cart(): void
    {
        $product = $this->product(5);
        $this->post(route('cart.add', $product), ['quantity' => 2]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cart.pending.resume'));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_register_flow_resume_adds_product_for_new_user(): void
    {
        $product = $this->product(5);
        $this->post(route('cart.add', $product), ['quantity' => 1]);

        $newUser = User::factory()->create();

        $this->actingAs($newUser)->get(route('cart.pending.resume'))
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_authenticated_add_to_cart_still_works(): void
    {
        $user = User::factory()->create();
        $product = $this->product(5);

        $this->actingAs($user)->post(route('cart.add', $product), ['quantity' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertNull(session('pending_cart_action'));
    }

    public function test_unavailable_stock_after_login_does_not_add_and_shows_message(): void
    {
        $product = $this->product(5);
        $this->post(route('cart.add', $product), ['quantity' => 2]);

        $product->update(['stock_quantity' => 0]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('cart.pending.resume'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    public function test_expired_pending_action_does_not_add_and_redirects_safely(): void
    {
        $product = $this->product(5);
        $this->post(route('cart.add', $product), ['quantity' => 1]);

        $pending = session('pending_cart_action');
        $pending['expires_at'] = now()->subMinutes(5)->timestamp;
        session()->put('pending_cart_action', $pending);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('cart.pending.resume'));

        $response->assertRedirect(route('user.shop.home'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    public function test_resume_called_twice_does_not_duplicate_product(): void
    {
        $product = $this->product(5);
        $this->post(route('cart.add', $product), ['quantity' => 2]);

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('cart.pending.resume'));
        $this->actingAs($user)->get(route('cart.pending.resume'));

        $this->assertSame(
            1,
            \DB::table('cart_items')->where('product_id', $product->id)->count(),
            'Pending resume must not duplicate the cart item.'
        );
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_external_redirect_to_is_rejected_and_replaced_with_safe_internal(): void
    {
        $product = $this->product(0);
        $this->post(route('cart.add', $product), ['quantity' => 1]);

        $pending = session('pending_cart_action');
        $pending['redirect_to'] = 'https://evil.example.com/phish';
        session()->put('pending_cart_action', $pending);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('cart.pending.resume'));

        $response->assertRedirect(route('user.shop.home'));
        $response->assertSessionHas('error');

        $protocolRelative = $pending;
        $protocolRelative['redirect_to'] = '//evil.example.com/path';
        session()->put('pending_cart_action', $protocolRelative);
        $this->actingAs($user)->get(route('cart.pending.resume'))
            ->assertRedirect(route('user.shop.home'));
    }

    public function test_pending_action_is_removed_after_resume(): void
    {
        $product = $this->product(5);
        $this->post(route('cart.add', $product), ['quantity' => 1]);

        $this->assertNotNull(session('pending_cart_action'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('cart.pending.resume'));

        $this->assertNull(session('pending_cart_action'));
    }
}
