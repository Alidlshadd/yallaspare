<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileCheckoutReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure category id=1 exists since ProductFactory defaults to category_id => 1.
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

    private function fillCart(User $user, Product $product, int $qty = 2): void
    {
        $cart = Cart::query()->forceCreate(['user_id' => $user->id]);
        CartItem::query()->forceCreate([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $qty,
        ]);
    }

    public function test_review_requires_auth(): void
    {
        $this->postJson('/api/mobile/checkout/review', [])->assertStatus(401);
    }

    public function test_review_returns_422_for_empty_cart(): void
    {
        $user = $this->userWithAddress();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', []);

        $response->assertStatus(422);
        $this->assertStringContainsString('cart', strtolower((string) $response->json('message')));
    }

    public function test_review_returns_422_when_no_address(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);
        $this->fillCart($user, $product);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', [])
            ->assertStatus(422);
    }

    public function test_review_returns_totals_for_default_address(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 12500, 'stock_quantity' => 10, 'is_active' => true]);
        $this->fillCart($user, $product, 2);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', []);

        $response->assertOk();
        $totals = $response->json('data.totals');
        $this->assertSame(25000.0, (float) $totals['subtotal']);
        $this->assertSame(5000.0, (float) $totals['shipping_fee']);
        $this->assertSame(0.0, (float) $totals['discount_amount']);
        $this->assertSame(30000.0, (float) $totals['grand_total']);
        $this->assertNotEmpty($response->json('data.address.id'));
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_review_explicit_address_id_used(): void
    {
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
        $this->fillCart($user, $product, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', ['address_id' => $other->id]);

        $response->assertOk();
        $this->assertSame($other->id, $response->json('data.address.id'));
    }

    public function test_review_invalid_coupon_returns_200_with_failure_envelope(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);
        $this->fillCart($user, $product, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', ['coupon_code' => 'NOPE-BAD-CODE']);

        $response->assertOk();
        $this->assertFalse($response->json('data.coupon_summary.valid'));
        $this->assertSame(0.0, (float) $response->json('data.totals.discount_amount'));
    }

    public function test_review_uses_setting_for_shipping_fee(): void
    {
        Setting::setValue('shipping_fee', 7777);
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);
        $this->fillCart($user, $product, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', []);

        $response->assertOk();
        $this->assertSame(7777.0, (float) $response->json('data.totals.shipping_fee'));
    }

    public function test_review_echoes_notes(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);
        $this->fillCart($user, $product, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', ['notes' => 'ring before delivery']);

        $response->assertOk();
        $this->assertSame('ring before delivery', $response->json('data.notes'));
    }
}
