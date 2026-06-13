<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_review_can_be_loaded_with_get_after_posting_review_details(): void
    {
        [$user, $address, $product] = $this->makeCheckoutContext();

        $this->actingAs($user)
            ->post(route('checkout.review'), [
                'address_id' => $address->id,
                'notes' => 'Leave at the front desk',
            ])
            ->assertOk()
            ->assertSee('Review your order details')
            ->assertSee('Leave at the front desk')
            ->assertSee($product->name_en);

        $this->get(route('checkout.review'))
            ->assertOk()
            ->assertSee('Review your order details')
            ->assertSee('Leave at the front desk')
            ->assertSee($product->name_en);
    }

    public function test_checkout_review_get_redirects_to_cart_without_saved_review_state(): void
    {
        [$user] = $this->makeCheckoutContext();

        $this->actingAs($user)
            ->get(route('checkout.review'))
            ->assertRedirect(route('cart.index'));
    }

    public function test_checkout_store_creates_order_and_clears_cart(): void
    {
        [$user, $address, $product] = $this->makeCheckoutContext();

        $response = $this->actingAs($user)
            ->post(route('checkout.store'), [
                'address_id' => $address->id,
                'notes' => 'Call on arrival',
            ]);

        $order = Order::query()->first();

        $response->assertRedirect(route('checkout.success', $order));

        $this->assertNotNull($order);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('pending', $order->status);
        $this->assertStringContainsString('Call on arrival', (string) $order->notes);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $product->id,
        ]);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_checkout_store_applies_coupon_and_records_usage(): void
    {
        [$user, $address, $product] = $this->makeCheckoutContext();
        $coupon = Coupon::query()->create([
            'code' => 'SAVE10',
            'name' => 'Save 10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['checkout.coupon_code' => 'SAVE10'])
            ->post(route('checkout.store'), [
                'address_id' => $address->id,
            ]);

        $order = Order::query()->first();

        $response->assertRedirect(route('checkout.success', $order));
        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertSame('SAVE10', $order->coupon_code);
        $this->assertSame('5000.00', (string) $order->discount_amount);
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);
        $this->assertSame(1, (int) $coupon->fresh()->used_count);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_checkout_review_rejects_invalid_coupon(): void
    {
        [$user, $address] = $this->makeCheckoutContext();

        $this->actingAs($user)
            ->post(route('checkout.review'), [
                'address_id' => $address->id,
                'coupon_code' => 'NOPE',
                'coupon_action' => 'apply',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('coupon_code')
            ->assertSessionMissing('checkout.coupon_code');
        $this->assertSame(0, Order::query()->count());
    }

    public function test_checkout_store_fails_when_cart_product_is_out_of_stock(): void
    {
        [$user, $address, $product] = $this->makeCheckoutContext();
        $product->update(['stock_quantity' => 1]);

        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'address_id' => $address->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Order::query()->count());
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_buy_now_checkout_creates_order_without_clearing_normal_cart(): void
    {
        [$user, $address, $cartProduct] = $this->makeCheckoutContext();
        $buyNowProduct = Product::factory()->create([
            'category_id' => $cartProduct->category_id,
            'price' => 30000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('checkout.buy-now.place', $buyNowProduct), [
                'quantity' => 2,
                'address_id' => $address->id,
                'notes' => 'Buy now note',
            ]);

        $order = Order::query()->first();

        $response->assertRedirect(route('checkout.success', $order));
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(Order::PAYMENT_PENDING, $order->payment_status);
        $this->assertStringContainsString('Buy now note', (string) $order->notes);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $buyNowProduct->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $cartProduct->id,
            'quantity' => 2,
        ]);
        $this->assertSame(3, $buyNowProduct->fresh()->stock_quantity);
        $this->assertSame(5, $cartProduct->fresh()->stock_quantity);
    }

    public function test_checkout_store_validation_errors_still_show(): void
    {
        [$user] = $this->makeCheckoutContext();

        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'address_id' => 'not-an-integer',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('address_id');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_cart_add_limits_quantity_to_available_stock(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 8,
            'price' => 25000,
        ]);

        $this->actingAs($user)
            ->post(route('cart.add', $product), [
                'quantity' => 10,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 8,
        ]);
    }

    public function test_cart_add_returns_json_summary_for_ajax_requests(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 8,
            'price' => 25000,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('cart.add', $product), [
                'quantity' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('cart_count', 1)
            ->assertJsonPath('message', 'Added to cart successfully');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_cart_update_limits_quantity_to_available_stock(): void
    {
        [$user, , $product] = $this->makeCheckoutContext();
        $product->update(['stock_quantity' => 8]);
        $item = CartItem::query()->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('cart.update', $item), [
                'quantity' => 10,
            ])
            ->assertRedirect();

        $this->assertSame(8, (int) $item->fresh()->quantity);
    }

    public function test_checkout_options_requires_login(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 8,
            'price' => 25000,
        ]);

        $this->get(route('checkout.options', [
            'product' => $product->id,
            'quantity' => 10,
        ], false))
            ->assertRedirect(route('login'));
    }

    public function test_checkout_options_limits_quantity_to_available_stock_for_authenticated_users(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 8,
            'price' => 25000,
        ]);
        UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'Iraq',
            'city' => 'Baghdad',
            'address_line1' => 'Street 10',
            'phone' => '123456789',
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->get(route('checkout.options', [
                'product' => $product->id,
                'quantity' => 10,
            ], false))
            ->assertOk()
            ->assertSee('name="quantity" value="8"', false)
            ->assertSee('Final order check')
            ->assertSee('Quantity')
            ->assertSee('8');
    }

    public function test_pending_order_can_be_cancelled_directly_by_customer(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 3,
            'price' => 25000,
        ]);
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'order_number' => 'ORD-PENDING-CANCEL',
            'subtotal_amount' => 50000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 50000,
            'total_amount' => 50000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => 'Street 10',
            'delivery_city' => 'Baghdad',
            'delivery_phone' => '123456789',
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 25000,
            'subtotal' => 50000,
        ]);

        $this->actingAs($user)
            ->post(route('account.orders.cancellation-request', $order))
            ->assertRedirect()
            ->assertSessionHas('status', 'Order cancelled successfully.');

        $this->assertSame(Order::STATUS_CANCELLED, (string) $order->fresh()->status);
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => InventoryMovement::TYPE_IN,
            'quantity' => 2,
            'reference' => 'ORD-PENDING-CANCEL',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => Order::STATUS_PENDING,
            'to_status' => Order::STATUS_CANCELLED,
            'changed_by' => $user->id,
        ]);
    }

    /**
     * @return array{User, UserAddress, Product}
     */
    private function makeCheckoutContext(): array
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 5,
            'price' => 25000,
        ]);
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'Iraq',
            'city' => 'Baghdad',
            'address_line1' => 'Street 10',
            'phone' => '123456789',
            'is_default' => true,
        ]);
        $cart = Cart::query()->create([
            'user_id' => $user->id,
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        return [$user, $address, $product];
    }
}
