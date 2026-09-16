<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Checkout\CheckoutService;
use App\Services\WelcomeOfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WelcomeOfferTest extends TestCase
{
    use RefreshDatabase;

    private function enable(array $overrides = []): void
    {
        Setting::setMany(array_merge([
            'welcome_offer_enabled' => '1',
            'welcome_offer_type' => 'percent',
            'welcome_offer_value' => '20',
            'welcome_offer_minimum_subtotal' => '0',
            'welcome_offer_maximum_discount' => '0',
            'welcome_offer_valid_days' => '30',
        ], $overrides));
    }

    private function context(): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'price' => 25000, 'stock_quantity' => 10, 'is_active' => true,
        ]);
        $address = UserAddress::query()->create([
            'user_id' => $user->id, 'label' => 'Home', 'country' => 'Iraq',
            'city' => 'Baghdad', 'address_line1' => 'Street 10',
            'phone' => '+9647700000000', 'is_default' => true,
            'governorate_id' => Governorate::factory()->create(['shipping_fee' => 7000])->id,
        ]);
        $cart = Cart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        return [$user, $product, $address, $cart];
    }

    public static function rewards(): array
    {
        return [['percent', 20, 10000, 47000], ['fixed', 6000, 6000, 51000], ['free_shipping', 0, 7000, 50000]];
    }

    #[DataProvider('rewards')]
    public function test_web_mobile_and_order_totals_match(string $type, int $value, int $discount, int $total): void
    {
        $this->enable(['welcome_offer_type' => $type, 'welcome_offer_value' => $value]);
        [$user, $product, $address, $cart] = $this->context();

        $this->actingAs($user)->post(route('checkout.review'), ['address_id' => $address->id])
            ->assertOk()->assertSee(number_format($total, 0).' IQD')
            ->assertSee('-'.number_format($discount, 0).' IQD')->assertSee('data-welcome-offer', false);

        $this->actingAs($user, 'sanctum')->postJson('/api/mobile/checkout/review', ['address_id' => $address->id])
            ->assertOk()->assertJsonPath('data.totals.grand_total', $total)
            ->assertJsonPath('data.welcome_offer.valid', true);
        $this->postJson('/api/mobile/products/'.$product->id.'/buy-now/preview', ['address_id' => $address->id, 'quantity' => 2])
            ->assertOk()->assertJsonPath('data.totals.grand_total', $total);

        $order = app(CheckoutService::class)->placeCartOrder($cart, $user, $address, null, '');
        $this->assertSame((float) $total, (float) $order->grand_total);
        $this->assertSame((float) $discount, (float) $order->discount_amount);
        $this->assertSame($type, $order->welcome_offer['type']);
        $this->assertNotNull($user->fresh()->welcome_offer_used_at);
        $this->assertNull($order->coupon_id);
    }

    public function test_only_new_customers_receive_a_snapshot_and_updates_do_not_rewrite_it(): void
    {
        $existing = User::factory()->create();
        $this->enable();
        $new = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->enable(['welcome_offer_value' => 40]);
        $later = User::factory()->create();
        $service = app(WelcomeOfferService::class);

        $this->assertNull($service->available($existing));
        $this->assertNull($admin->welcome_offer);
        $this->assertSame(2000.0, $service->preview($new->fresh(), 10000)['discount']);
        $this->assertSame(4000.0, $service->preview($later, 10000)['discount']);
        Setting::setValue('welcome_offer_enabled', '0');
        $this->assertFalse($service->preview($new, 10000)['valid']);
        $this->assertNull(User::factory()->create()->welcome_offer);
    }

    public function test_minimum_cap_expiry_and_fixed_discount_cannot_make_total_negative(): void
    {
        $this->enable(['welcome_offer_minimum_subtotal' => 20000, 'welcome_offer_maximum_discount' => 3000]);
        $user = User::factory()->create();
        $service = app(WelcomeOfferService::class);
        $this->assertFalse($service->preview($user, 19999)['valid']);
        $this->assertSame(3000.0, $service->preview($user, 20000)['discount']);
        $this->travel(31)->days();
        $this->assertFalse($service->preview($user, 20000)['valid']);
        $this->travelBack();

        $this->enable(['welcome_offer_type' => 'fixed', 'welcome_offer_value' => 999999, 'welcome_offer_valid_days' => 0]);
        [$user, $product, $address] = $this->context();
        $order = app(CheckoutService::class)->placeBuyNowOrder($product, 1, $user, $address, null, '');
        $this->assertSame(7000.0, (float) $order->grand_total);
    }

    public function test_second_order_and_cancellation_do_not_restore_reward_even_with_stale_user(): void
    {
        $this->enable();
        [$user, $product, $address] = $this->context();
        $checkout = app(CheckoutService::class);
        $first = $checkout->placeBuyNowOrder($product, 1, $user, $address, null, '');
        $first->forceFill(['status' => Order::STATUS_CANCELLED])->save();
        $second = $checkout->placeBuyNowOrder($product, 1, $user, $address, null, '');
        $this->assertSame(5000.0, (float) $first->discount_amount);
        $this->assertSame(0.0, (float) $second->discount_amount);
        $this->assertNull($second->welcome_offer);
    }

    public function test_coupon_replaces_reward_and_first_order_eligibility_is_consumed(): void
    {
        $this->enable();
        [$user, $product, $address] = $this->context();
        Coupon::query()->create(['code' => 'TEST10', 'type' => 'percent', 'value' => 10, 'is_active' => true]);
        $checkout = app(CheckoutService::class);
        $first = $checkout->placeBuyNowOrder($product, 1, $user, $address, null, 'TEST10');
        $this->assertSame(2500.0, (float) $first->discount_amount);
        $this->assertNull($first->welcome_offer);
        $this->assertNotNull($user->fresh()->welcome_offer_used_at);
        $second = $checkout->placeBuyNowOrder($product, 1, $user, $address, null, '');
        $this->assertSame(0.0, (float) $second->discount_amount);
    }

    public function test_failed_order_rolls_back_reward_consumption(): void
    {
        $this->enable();
        [$user, $product, $address] = $this->context();
        OrderItem::creating(fn () => throw new \RuntimeException('Simulated inventory failure'));
        try {
            app(CheckoutService::class)->placeBuyNowOrder($product, 1, $user, $address, null, '');
            $this->fail('Expected failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated inventory failure', $exception->getMessage());
        } finally {
            OrderItem::flushEventListeners();
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertNull($user->fresh()->welcome_offer_used_at);
        $this->assertTrue(app(WelcomeOfferService::class)->preview($user->fresh(), 25000)['valid']);
    }

    public function test_admin_can_save_offer_and_invalid_settings_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $payload = [
            'welcome_offer_enabled' => '1', 'welcome_offer_type' => 'percent',
            'welcome_offer_value' => '20', 'welcome_offer_minimum_subtotal' => '0',
            'welcome_offer_maximum_discount' => '10000', 'welcome_offer_valid_days' => '30',
        ];
        $this->actingAs($admin)->put(route('admin.discounts.welcome-offer.update'), $payload)
            ->assertRedirect(route('admin.discounts.edit').'#welcome-offer');
        $this->assertSame('20', Setting::getValue('welcome_offer_value'));
        $this->get(route('admin.discounts.edit'))->assertOk()->assertSee('id="welcome-offer"', false);
        $this->put(route('admin.discounts.welcome-offer.update'), array_merge($payload, ['welcome_offer_value' => 101]))
            ->assertSessionHasErrors('welcome_offer_value');
        $this->assertSame('20', Setting::getValue('welcome_offer_value'));
    }

    public function test_web_and_mobile_registration_grant_reward_and_localized_banners_render(): void
    {
        $this->enable();
        foreach (['en', 'ar', 'ku'] as $locale) {
            $this->withSession(['locale' => $locale])->get('/register')->assertOk()
                ->assertSee('data-welcome-offer', false)->assertDontSee('welcome.percent');
        }
        $payload = [
            'name' => 'New customer', 'email' => 'welcome@example.test',
            'country_code' => '+964', 'phone' => '07704488315',
            'password' => 'WelcomeTest!2026', 'password_confirmation' => 'WelcomeTest!2026',
        ];
        $this->post('/register', $payload)->assertRedirect(route('verification.notice'));
        $this->assertSame(20, (int) User::where('email', $payload['email'])->firstOrFail()->welcome_offer['value']);
        auth()->logout();
        $payload['email'] = 'mobile-welcome@example.test';
        $payload['phone'] = '07704488316';
        $this->postJson('/api/mobile/register', $payload)->assertCreated();
        $this->assertSame(20, (int) User::where('email', $payload['email'])->firstOrFail()->welcome_offer['value']);
    }

    public function test_customer_cannot_change_settings_or_assign_reward_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('admin.discounts.welcome-offer.update'), [])->assertForbidden();
        $user->fill(['welcome_offer' => ['type' => 'percent', 'value' => 100], 'welcome_offer_used_at' => now()]);
        $this->assertNull($user->welcome_offer);
        $this->assertNull($user->welcome_offer_used_at);
    }
}
