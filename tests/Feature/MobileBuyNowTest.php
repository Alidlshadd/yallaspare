<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileBuyNowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1]);
        }
    }

    private function userWithAddress(array $userOverrides = [], array $addressOverrides = []): User
    {
        $user = User::factory()->create($userOverrides);
        UserAddress::query()->forceCreate(array_merge([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'IQ',
            'city' => 'Erbil',
            'address_line1' => '100 Test Street',
            'phone' => '+964 770 000 0000',
            'is_default' => true,
        ], $addressOverrides));

        return $user->fresh();
    }

    public function test_preview_requires_auth(): void
    {
        $this->postJson('/api/mobile/products/999/buy-now/preview', [])->assertStatus(401);
    }

    public function test_preview_returns_404_for_unknown_product(): void
    {
        $user = $this->userWithAddress();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/this-slug-does-not-exist/buy-now/preview', [])
            ->assertStatus(404);
    }

    public function test_preview_returns_422_for_inactive_product(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', [])
            ->assertStatus(422)
            ->assertJsonPath('message', __('errors.product_unavailable'));
    }

    public function test_preview_returns_422_when_out_of_stock(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['stock_quantity' => 0, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', [])
            ->assertStatus(422)
            ->assertJsonPath('message', __('errors.stock_insufficient'));
    }

    public function test_preview_clamps_quantity_to_stock(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 3, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', ['quantity' => 5]);

        $response->assertOk();
        $this->assertSame(3, (int) $response->json('data.quantity'));
        $this->assertSame(5, (int) $response->json('data.quantity_requested'));
    }

    public function test_preview_returns_totals_for_default_address(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 12500, 'stock_quantity' => 10, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', ['quantity' => 2]);

        $response->assertOk();
        $totals = $response->json('data.totals');
        $this->assertSame(25000.0, (float) $totals['subtotal']);
        $this->assertSame(5000.0, (float) $totals['shipping_fee']);
        $this->assertSame(0.0, (float) $totals['discount_amount']);
        $this->assertSame(30000.0, (float) $totals['grand_total']);
        $this->assertSame($product->id, (int) $response->json('data.product.id'));
        $this->assertNotEmpty($response->json('data.address.id'));
    }

    public function test_preview_invalid_coupon_returns_200_with_failure_envelope(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', [
                'quantity' => 1,
                'coupon_code' => 'NO-SUCH-CODE',
            ]);

        $response->assertOk();
        $this->assertFalse($response->json('data.coupon_summary.valid'));
        $this->assertSame(0.0, (float) $response->json('data.totals.discount_amount'));
    }

    public function test_place_requires_auth(): void
    {
        $this->postJson('/api/mobile/products/999/buy-now/place', ['quantity' => 1])->assertStatus(401);
    }

    public function test_place_requires_quantity(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', [])
            ->assertStatus(422);
    }

    public function test_place_creates_order_with_correct_totals_and_decrements_stock(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 12500, 'stock_quantity' => 10, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', ['quantity' => 2]);

        $response->assertOk();
        $orderId = $response->json('order.id');
        $this->assertNotNull($orderId);

        $order = Order::query()->with('items')->findOrFail($orderId);
        $this->assertSame(25000.0, (float) $order->subtotal_amount);
        $this->assertSame(5000.0, (float) $order->shipping_fee);
        $this->assertSame(0.0, (float) $order->discount_amount);
        $this->assertSame(30000.0, (float) $order->grand_total);
        $this->assertSame($user->id, (int) $order->user_id);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, (int) $order->items->first()->quantity);
        $this->assertSame($product->id, (int) $order->items->first()->product_id);
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
    }

    public function test_place_hard_fails_on_insufficient_stock(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 1, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', ['quantity' => 5])
            ->assertStatus(422)
            ->assertJsonPath('message', __('errors.stock_insufficient'));

        $this->assertSame(0, Order::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, (int) $product->fresh()->stock_quantity);
    }

    public function test_place_hard_fails_on_inactive_product(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', ['quantity' => 1])
            ->assertStatus(422)
            ->assertJsonPath('message', __('errors.product_unavailable'));

        $this->assertSame(0, Order::query()->where('user_id', $user->id)->count());
    }

    public function test_place_hard_fails_on_invalid_coupon(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', [
                'quantity' => 1,
                'coupon_code' => 'NO-SUCH',
            ])
            ->assertStatus(422);

        $this->assertSame(0, Order::query()->where('user_id', $user->id)->count());
    }

    public function test_place_respects_address_payment_notes_and_coupon(): void
    {
        Setting::setValue('shipping_fee', 5000);
        $user = $this->userWithAddress();
        $other = UserAddress::query()->forceCreate([
            'user_id' => $user->id,
            'label' => 'Office',
            'country' => 'IQ',
            'city' => 'Sulaymaniyah',
            'address_line1' => '200 Office Lane',
            'phone' => '+964 770 111 1111',
            'is_default' => false,
        ]);
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);
        Coupon::query()->forceCreate([
            'code' => 'TAKE10',
            'name' => 'Take 10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', [
                'quantity' => 2,
                'address_id' => $other->id,
                'payment_method' => 'bank_transfer',
                'notes' => 'call first',
                'coupon_code' => 'take10',
            ])
            ->assertOk();

        $order = Order::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Sulaymaniyah', $order->delivery_city);
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertSame('call first', $order->notes);
        $this->assertSame('TAKE10', $order->coupon_code);
        $this->assertSame(2000.0, (float) $order->discount_amount);
        $this->assertSame(23000.0, (float) $order->grand_total);
    }
}
