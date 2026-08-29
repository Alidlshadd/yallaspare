<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutGovernorateShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_charged_the_shipping_fee_of_its_governorate(): void
    {
        Setting::setValue('shipping_fee', '5000');
        $governorate = Governorate::factory()->create([
            'name_en' => 'Erbil',
            'shipping_fee' => 9000,
            'delivery_days' => 2,
        ]);

        [$user, $address] = $this->makeCheckoutContext($governorate);

        $this->actingAs($user)
            ->post(route('checkout.store'), ['address_id' => $address->id])
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame(9000.0, (float) $order->shipping_fee);
        $this->assertSame(59000.0, (float) $order->grand_total);
        $this->assertSame($governorate->id, $order->governorate_id);
        $this->assertSame('Erbil', $order->delivery_governorate);
        $this->assertSame(2, $order->delivery_days);
    }

    public function test_a_governorate_with_a_zero_fee_ships_free_rather_than_falling_back(): void
    {
        Setting::setValue('shipping_fee', '5000');
        $governorate = Governorate::factory()->create(['shipping_fee' => 0, 'delivery_days' => 1]);

        [$user, $address] = $this->makeCheckoutContext($governorate);

        $this->actingAs($user)
            ->post(route('checkout.store'), ['address_id' => $address->id])
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame(0.0, (float) $order->shipping_fee);
        $this->assertSame(50000.0, (float) $order->grand_total);
    }

    public function test_an_address_saved_before_the_shipping_map_still_pays_the_flat_fee(): void
    {
        Setting::setValue('shipping_fee', '5000');

        [$user, $address] = $this->makeCheckoutContext(null);

        $this->actingAs($user)
            ->post(route('checkout.store'), ['address_id' => $address->id])
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame(5000.0, (float) $order->shipping_fee);
        $this->assertNull($order->governorate_id);
        $this->assertNull($order->delivery_governorate);
        $this->assertNull($order->delivery_days);
    }

    public function test_a_free_shipping_coupon_waives_the_governorate_fee(): void
    {
        Setting::setValue('shipping_fee', '5000');
        $governorate = Governorate::factory()->create(['shipping_fee' => 9000, 'delivery_days' => 3]);

        [$user, $address] = $this->makeCheckoutContext($governorate);

        Coupon::query()->create([
            'code' => 'SHIPFREE',
            'type' => 'free_shipping',
            'value' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.review'), [
                'address_id' => $address->id,
                'coupon_code' => 'SHIPFREE',
                'coupon_action' => 'apply',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post(route('checkout.store'), ['address_id' => $address->id])
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame(9000.0, (float) $order->shipping_fee);
        $this->assertSame(9000.0, (float) $order->discount_amount);
        $this->assertSame(50000.0, (float) $order->grand_total);
    }

    public function test_the_review_screen_quotes_the_destination_and_its_delivery_promise(): void
    {
        $governorate = Governorate::factory()->create([
            'name_en' => 'Sulaymaniyah',
            'shipping_fee' => 7500,
            'delivery_days' => 4,
        ]);

        [$user, $address] = $this->makeCheckoutContext($governorate);

        $this->actingAs($user)
            ->post(route('checkout.review'), ['address_id' => $address->id])
            ->assertOk()
            ->assertSee('Sulaymaniyah')
            ->assertSee('7,500')
            ->assertSee('Delivery in 4 days');
    }

    public function test_the_delivery_screen_prices_each_saved_address_separately(): void
    {
        $near = Governorate::factory()->create(['name_en' => 'Baghdad', 'shipping_fee' => 3000, 'delivery_days' => 1]);
        $far = Governorate::factory()->create(['name_en' => 'Basrah', 'shipping_fee' => 12000, 'delivery_days' => 5]);

        [$user, $address] = $this->makeCheckoutContext($near);

        UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Warehouse',
            'country' => 'Iraq',
            'governorate_id' => $far->id,
            'city' => 'Basrah',
            'address_line1' => 'Port road',
            'phone' => '07701234567',
        ]);

        $this->actingAs($user)
            ->get(route('checkout.delivery'))
            ->assertOk()
            ->assertSee('3,000')
            ->assertSee('Delivery in 1 days')
            ->assertSee('12,000')
            ->assertSee('Delivery in 5 days');

        $this->assertSame($near->id, $address->governorate_id);
    }

    public function test_a_saved_address_must_name_a_governorate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.addresses.store'), [
                'label' => 'Home',
                'country' => 'Iraq',
                'city' => 'Erbil',
                'address_line1' => 'Street 10',
            ])
            ->assertSessionHasErrors('governorate_id');

        $this->assertDatabaseCount('user_addresses', 0);
    }

    public function test_a_saved_address_keeps_the_governorate_it_was_given(): void
    {
        $user = User::factory()->create();
        $governorate = Governorate::factory()->create();

        $this->actingAs($user)
            ->post(route('account.addresses.store'), [
                'label' => 'Home',
                'country' => 'Iraq',
                'governorate_id' => $governorate->id,
                'city' => 'Erbil',
                'address_line1' => 'Street 10',
            ])
            ->assertRedirect(route('account.addresses.index'));

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'governorate_id' => $governorate->id,
        ]);
    }

    public function test_the_mobile_checkout_review_quotes_the_governorate_rate(): void
    {
        Setting::setValue('shipping_fee', '5000');
        $governorate = Governorate::factory()->create([
            'name_en' => 'Dohuk',
            'shipping_fee' => 11000,
            'delivery_days' => 3,
        ]);

        [$user, $address] = $this->makeCheckoutContext($governorate);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', ['address_id' => $address->id]);

        $response->assertOk();
        $this->assertSame(11000.0, (float) $response->json('data.totals.shipping_fee'));
        $this->assertSame(3, $response->json('data.shipping.delivery_days'));
        $this->assertSame('Dohuk', $response->json('data.shipping.governorate'));
        $this->assertTrue($response->json('data.shipping.is_governorate_rate'));
        $this->assertSame($governorate->id, $response->json('data.address.governorate_id'));
    }

    public function test_the_mobile_shipping_map_is_published_for_clients(): void
    {
        Governorate::factory()->create([
            'name_en' => 'Kirkuk',
            'shipping_fee' => 6000,
            'delivery_days' => 2,
        ]);

        $this->getJson('/api/mobile/governorates')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Kirkuk',
                'shipping_fee' => 6000,
                'delivery_days' => 2,
            ]);
    }

    /**
     * @return array{0: User, 1: UserAddress}
     */
    private function makeCheckoutContext(?Governorate $governorate): array
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
            'governorate_id' => $governorate?->id,
            'city' => 'Baghdad',
            'address_line1' => 'Street 10',
            'phone' => '07701234567',
            'is_default' => true,
        ]);

        $cart = Cart::query()->create(['user_id' => $user->id]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        return [$user, $address];
    }
}
