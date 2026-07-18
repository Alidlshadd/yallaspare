<?php

namespace Tests\Feature;

use App\Models\BackInStockSubscription;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RecentlyViewedProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebCommerceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1, 'name_en' => 'Brake Parts', 'slug' => 'brake-parts']);
        }
    }

    public function test_customer_can_reorder_available_order_items_to_cart(): void
    {
        $user = User::factory()->create();
        $available = Product::factory()->create(['stock_quantity' => 3, 'is_active' => true]);
        $unavailable = Product::factory()->create(['stock_quantity' => 0, 'is_active' => true]);
        $order = $this->orderFor($user);
        OrderItem::query()->forceCreate([
            'order_id' => $order->id,
            'product_id' => $available->id,
            'quantity' => 5,
            'unit_price' => 1000,
            'subtotal' => 5000,
        ]);
        OrderItem::query()->forceCreate([
            'order_id' => $order->id,
            'product_id' => $unavailable->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);

        $response = $this->actingAs($user)
            ->post(route('account.orders.reorder', $order));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $available->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $unavailable->id,
        ]);
    }

    public function test_customer_can_replace_cart_when_reordering(): void
    {
        $user = User::factory()->create();
        $existingProduct = Product::factory()->create(['stock_quantity' => 5, 'is_active' => true]);
        $orderedProduct = Product::factory()->create(['stock_quantity' => 5, 'is_active' => true]);
        $cart = Cart::query()->forceCreate(['user_id' => $user->id]);
        $cart->items()->forceCreate([
            'product_id' => $existingProduct->id,
            'quantity' => 2,
        ]);
        $order = $this->orderFor($user);
        OrderItem::query()->forceCreate([
            'order_id' => $order->id,
            'product_id' => $orderedProduct->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);

        $this->actingAs($user)
            ->post(route('account.orders.reorder', $order), ['replace_cart' => true])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('cart_items', ['product_id' => $existingProduct->id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $orderedProduct->id, 'quantity' => 1]);
    }

    public function test_back_in_stock_subscription_can_be_created_and_removed_from_web(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 0, 'is_active' => true]);

        $this->actingAs($user)
            ->post(route('shop.back-in-stock.store', $product))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, BackInStockSubscription::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->count());

        $this->actingAs($user)
            ->delete(route('shop.back-in-stock.destroy', $product))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('back_in_stock_subscriptions', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_out_of_stock_product_card_offers_a_stock_request_action(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name_en' => 'Requested Water Pump',
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('shop.index'))
            ->assertOk()
            ->assertSee(__('Send Request'))
            ->assertSee(route('shop.back-in-stock.store', $product), false);

        $this->post(route('shop.back-in-stock.store', $product))
            ->assertRedirect()
            ->assertSessionHas('status', __('Request sent. We will notify you when this product is back in stock.'));

        $this->assertDatabaseHas('back_in_stock_subscriptions', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_back_in_stock_subscription_is_not_created_for_stocked_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 4, 'is_active' => true]);

        $this->actingAs($user)
            ->post(route('shop.back-in-stock.store', $product))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('back_in_stock_subscriptions', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_web_search_autocomplete_returns_product_category_and_brand_suggestions(): void
    {
        $category = Category::factory()->create(['name_en' => 'Brake Systems', 'slug' => 'brake-systems']);
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Ceramic Brake Pad',
            'brand' => 'BrakeCo',
            'sku' => 'BRK-123',
            'stock_quantity' => 8,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name_en' => 'Hidden Brake Pad',
            'brand' => 'BrakeCo',
            'is_active' => false,
        ]);

        $response = $this->getJson(route('shop.autocomplete', ['q' => 'brake']));

        $response->assertOk();
        $this->assertSame('Ceramic Brake Pad', $response->json('data.products.0.label'));
        $this->assertContains('Brake Systems', collect($response->json('data.categories'))->pluck('label')->all());
        $this->assertSame('BrakeCo', $response->json('data.brands.0.label'));
        $this->assertCount(1, $response->json('data.products'));
    }

    public function test_product_detail_records_and_displays_recently_viewed_products(): void
    {
        $user = User::factory()->create();
        $older = Product::factory()->create(['name_en' => 'Older Rotor', 'stock_quantity' => 5, 'is_active' => true]);
        $current = Product::factory()->create(['name_en' => 'Current Pad', 'stock_quantity' => 5, 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('shop.show', $older))
            ->assertOk();

        $response = $this->actingAs($user)
            ->get(route('shop.show', $current));

        $response->assertOk();
        $response->assertSee('Older Rotor');
        $this->assertDatabaseHas('recently_viewed_products', [
            'user_id' => $user->id,
            'product_id' => $current->id,
        ]);
        $this->assertSame(2, RecentlyViewedProduct::query()->where('user_id', $user->id)->count());
    }

    private function orderFor(User $user): Order
    {
        $order = new Order();
        $order->forceFill([
            'user_id' => $user->id,
            'order_number' => 'WEB-TEST-' . fake()->unique()->numberBetween(1000, 9999),
            'subtotal_amount' => 0,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 0,
            'total_amount' => 0,
            'status' => Order::STATUS_DELIVERED,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => '100 Test Street',
            'delivery_city' => 'Erbil',
            'delivery_phone' => '+964 770 000 0000',
        ])->save();

        return $order;
    }
}
