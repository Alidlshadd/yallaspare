<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_put_a_product_in_a_cart(): void
    {
        $product = $this->product();

        $this->post(route('cart.add', $product), ['quantity' => 2])
            ->assertRedirect();

        $cart = Cart::query()->firstOrFail();

        $this->assertNull($cart->user_id);
        $this->assertNotNull($cart->session_token);
        $this->assertSame($cart->session_token, session(CartService::SESSION_KEY));
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_a_guest_can_open_the_cart_page_and_see_what_is_in_it(): void
    {
        $product = $this->product(['name_en' => 'Guest Brake Pad']);

        $this->post(route('cart.add', $product), ['quantity' => 1])->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Guest Brake Pad');
    }

    public function test_a_guest_can_change_and_remove_a_line(): void
    {
        $product = $this->product(['stock_quantity' => 10]);

        $this->post(route('cart.add', $product), ['quantity' => 1])->assertRedirect();
        $item = CartItem::query()->firstOrFail();

        $this->patch(route('cart.update', $item), ['quantity' => 4])->assertRedirect();
        $this->assertSame(4, (int) $item->fresh()->quantity);

        $this->delete(route('cart.remove', $item))->assertRedirect();
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_a_guest_cannot_touch_a_line_from_another_visitors_cart(): void
    {
        $product = $this->product();
        $stranger = User::factory()->create();
        $strangersCart = Cart::query()->create(['user_id' => $stranger->id]);
        $strangersItem = CartItem::query()->create([
            'cart_id' => $strangersCart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->post(route('cart.add', $this->product()), ['quantity' => 1])->assertRedirect();

        $this->patch(route('cart.update', $strangersItem), ['quantity' => 9])->assertForbidden();
        $this->delete(route('cart.remove', $strangersItem))->assertForbidden();

        $this->assertSame(1, (int) $strangersItem->fresh()->quantity);
    }

    public function test_a_guest_without_a_cart_is_not_given_a_token_just_for_looking(): void
    {
        $this->get(route('cart.index'))->assertOk();

        $this->assertNull(session(CartService::SESSION_KEY));
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_the_quantity_never_goes_past_what_is_on_the_shelf(): void
    {
        $product = $this->product(['stock_quantity' => 3]);

        $this->post(route('cart.add', $product), ['quantity' => 9])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_signing_in_carries_the_guest_cart_into_the_account(): void
    {
        $product = $this->product(['stock_quantity' => 10]);
        $user = User::factory()->create(['password' => bcrypt('password-secret-1')]);

        $this->post(route('cart.add', $product), ['quantity' => 2])->assertRedirect();
        $guestCartId = Cart::query()->firstOrFail()->id;

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password-secret-1',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('carts', ['id' => $guestCartId]);
        $this->assertNull(session(CartService::SESSION_KEY));

        $userCart = Cart::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_merging_adds_to_what_the_account_already_held(): void
    {
        $product = $this->product(['stock_quantity' => 10]);
        $user = User::factory()->create();

        $accountCart = Cart::query()->create(['user_id' => $user->id]);
        CartItem::query()->create([
            'cart_id' => $accountCart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->post(route('cart.add', $product), ['quantity' => 2])->assertRedirect();

        app(CartService::class)->mergeGuestCartInto($user);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $accountCart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_merging_clamps_to_stock_rather_than_overselling(): void
    {
        $product = $this->product(['stock_quantity' => 4]);
        $user = User::factory()->create();

        $accountCart = Cart::query()->create(['user_id' => $user->id]);
        CartItem::query()->create([
            'cart_id' => $accountCart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->post(route('cart.add', $product), ['quantity' => 3])->assertRedirect();

        app(CartService::class)->mergeGuestCartInto($user);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $accountCart->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
    }

    public function test_a_product_that_sold_out_while_the_guest_waited_is_dropped_quietly(): void
    {
        $product = $this->product(['stock_quantity' => 5]);
        $user = User::factory()->create();

        $this->post(route('cart.add', $product), ['quantity' => 2])->assertRedirect();

        $product->update(['stock_quantity' => 0]);

        $merged = app(CartService::class)->mergeGuestCartInto($user);

        $this->assertSame(0, $merged);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_two_visitors_do_not_share_a_cart(): void
    {
        $product = $this->product(['stock_quantity' => 10]);

        $this->post(route('cart.add', $product), ['quantity' => 1])->assertRedirect();
        $firstToken = session(CartService::SESSION_KEY);

        $this->flushSession();

        $this->post(route('cart.add', $product), ['quantity' => 5])->assertRedirect();
        $secondToken = session(CartService::SESSION_KEY);

        $this->assertNotSame($firstToken, $secondToken);
        $this->assertDatabaseCount('carts', 2);
        $this->assertSame(2, CartItem::query()->count());
    }

    private function product(array $attributes = []): Product
    {
        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1]);
        }

        return Product::factory()->create(array_merge([
            'category_id' => 1,
            'is_active' => true,
            'stock_quantity' => 5,
        ], $attributes));
    }
}
