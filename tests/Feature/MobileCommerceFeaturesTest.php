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

class MobileCommerceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1, 'name_en' => 'Brake Parts']);
        }
    }

    public function test_search_autocomplete_returns_product_category_and_brand_suggestions(): void
    {
        $category = Category::factory()->create(['name_en' => 'Brake Systems']);
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

        $response = $this->getJson('/api/mobile/search/autocomplete?q=brake');

        $response->assertOk();
        $this->assertSame('Ceramic Brake Pad', $response->json('data.products.0.label'));
        $this->assertContains('Brake Systems', collect($response->json('data.categories'))->pluck('label')->all());
        $this->assertSame('BrakeCo', $response->json('data.brands.0.label'));
        $this->assertCount(1, $response->json('data.products'));
    }

    public function test_recently_viewed_products_are_recorded_ordered_and_clearable(): void
    {
        $user = User::factory()->create();
        $older = Product::factory()->create(['name_en' => 'Older Product', 'is_active' => true]);
        $newer = Product::factory()->create(['name_en' => 'Newer Product', 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/recently-viewed/' . $older->id)
            ->assertCreated();

        $this->travel(1)->minute();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/recently-viewed/' . $newer->slug)
            ->assertCreated();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/mobile/recently-viewed');

        $response->assertOk();
        $this->assertSame('Newer Product', $response->json('data.0.product.name'));
        $this->assertSame('Older Product', $response->json('data.1.product.name'));

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/mobile/recently-viewed')
            ->assertOk();

        $this->assertSame(0, RecentlyViewedProduct::query()->where('user_id', $user->id)->count());
    }

    public function test_back_in_stock_subscription_is_idempotent_for_out_of_stock_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 0, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->slug . '/back-in-stock')
            ->assertCreated()
            ->assertJsonPath('data.subscribed', true)
            ->assertJsonPath('data.available_now', false);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->slug . '/back-in-stock')
            ->assertOk();

        $this->assertSame(1, BackInStockSubscription::query()->where('user_id', $user->id)->where('product_id', $product->id)->count());

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/mobile/products/' . $product->slug . '/back-in-stock')
            ->assertOk();

        $this->assertSame(0, BackInStockSubscription::query()->where('user_id', $user->id)->where('product_id', $product->id)->count());
    }

    public function test_back_in_stock_subscribe_returns_available_now_for_stocked_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 3, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/back-in-stock')
            ->assertOk()
            ->assertJsonPath('data.subscribed', false)
            ->assertJsonPath('data.available_now', true);

        $this->assertDatabaseMissing('back_in_stock_subscriptions', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_reorder_adds_available_order_items_to_cart_and_reports_skipped_items(): void
    {
        $user = User::factory()->create();
        $available = Product::factory()->create(['name_en' => 'Available Rotor', 'stock_quantity' => 3, 'is_active' => true]);
        $outOfStock = Product::factory()->create(['name_en' => 'Unavailable Pad', 'stock_quantity' => 0, 'is_active' => true]);
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
            'product_id' => $outOfStock->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/orders/' . $order->id . '/reorder');

        $response->assertOk();
        $this->assertSame(3, $response->json('added.0.added_quantity'));
        $this->assertTrue($response->json('added.0.limited_by_stock'));
        $this->assertSame('out_of_stock', $response->json('skipped.0.reason'));
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $available->id,
            'quantity' => 3,
        ]);
    }

    public function test_reorder_can_replace_existing_cart(): void
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

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/orders/' . $order->id . '/reorder', ['replace_cart' => true])
            ->assertOk();

        $this->assertDatabaseMissing('cart_items', ['product_id' => $existingProduct->id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $orderedProduct->id, 'quantity' => 1]);
    }

    private function orderFor(User $user): Order
    {
        $order = new Order();
        $order->forceFill([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-' . fake()->unique()->numberBetween(1000, 9999),
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
