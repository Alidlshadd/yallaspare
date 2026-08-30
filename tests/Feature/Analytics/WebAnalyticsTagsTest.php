<?php

namespace Tests\Feature\Analytics;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAnalyticsTagsTest extends TestCase
{
    use RefreshDatabase;

    private function configureTags(?string $ga4 = 'G-TEST12345', ?string $meta = '111122223333'): void
    {
        config([
            'analytics.ga4.measurement_id' => (string) $ga4,
            'analytics.meta.pixel_id' => (string) $meta,
        ]);
    }

    public function test_no_tag_and_no_policy_change_when_nothing_is_configured(): void
    {
        $this->configureTags('', '');

        $response = $this->get(route('shop.index'))->assertOk();

        $response->assertDontSee('googletagmanager.com', false);
        $response->assertDontSee('connect.facebook.net', false);

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("connect-src 'self';", $csp);
        $this->assertStringNotContainsString('google-analytics.com', $csp);
        $this->assertStringNotContainsString('facebook.com', $csp);
    }

    public function test_a_configured_tag_is_rendered_and_the_policy_lets_it_report(): void
    {
        $this->configureTags();

        $response = $this->get(route('shop.index'))->assertOk();

        $response->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST12345', false);
        $response->assertSee('connect.facebook.net', false);

        $csp = (string) $response->headers->get('Content-Security-Policy');

        // The script loads either way under 'strict-dynamic'; without these the
        // beacon is what gets blocked, and the reports just stay empty.
        $this->assertStringContainsString('https://*.google-analytics.com', $csp);
        $this->assertStringContainsString('https://www.facebook.com', $csp);
    }

    public function test_each_tag_only_widens_the_policy_for_itself(): void
    {
        $this->configureTags(ga4: 'G-TEST12345', meta: '');

        $csp = (string) $this->get(route('shop.index'))->assertOk()
            ->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://*.google-analytics.com', $csp);
        $this->assertStringNotContainsString('facebook.com', $csp);
    }

    public function test_the_admin_panel_carries_no_measurement_tag(): void
    {
        $this->configureTags();

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->forceFill(['role' => User::ROLE_SUPER_ADMIN])->save();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('connect.facebook.net', false);
    }

    public function test_viewing_a_product_reports_view_item(): void
    {
        $this->configureTags();
        $product = $this->product(['price' => 12000]);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSee('"name":"view_item"', false)
            ->assertSee('"ViewContent"', false);
    }

    public function test_adding_to_the_cart_reports_on_the_page_the_visitor_lands_on(): void
    {
        $this->configureTags();
        $product = $this->product(['price' => 12000, 'stock_quantity' => 10]);

        // The add itself redirects, so nothing can be rendered on that response.
        $this->post(route('cart.add', $product), ['quantity' => 2])->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('"name":"add_to_cart"', false)
            ->assertSee('"value":24000', false);
    }

    public function test_an_event_is_reported_once_and_then_gone(): void
    {
        $this->configureTags();
        $product = $this->product(['stock_quantity' => 10]);

        $this->post(route('cart.add', $product), ['quantity' => 1])->assertRedirect();
        $this->get(route('cart.index'))->assertOk()->assertSee('"name":"add_to_cart"', false);

        $this->get(route('cart.index'))->assertOk()->assertDontSee('"name":"add_to_cart"', false);
    }

    public function test_an_add_that_added_nothing_is_not_reported_as_an_add(): void
    {
        $this->configureTags();
        $product = $this->product(['stock_quantity' => 1]);

        $this->post(route('cart.add', $product), ['quantity' => 1])->assertRedirect();
        $this->get(route('cart.index'))->assertOk();

        // The cart already holds the whole shelf; this one adds nothing.
        $this->post(route('cart.add', $product), ['quantity' => 5])->assertRedirect();

        $this->get(route('cart.index'))->assertOk()->assertDontSee('"name":"add_to_cart"', false);
    }

    public function test_searching_reports_the_term(): void
    {
        $this->configureTags();
        $this->product(['name_en' => 'Brake Pad']);

        $this->get(route('shop.index', ['search' => 'brake']))
            ->assertOk()
            ->assertSee('"search_term":"brake"', false);
    }

    public function test_a_purchase_is_reported_once_however_often_the_page_is_reloaded(): void
    {
        $this->configureTags();
        [$user, $order] = $this->placedOrder();

        $this->actingAs($user)
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('"name":"purchase"', false)
            ->assertSee('"transaction_id":"'.$order->order_number.'"', false);

        $this->actingAs($user)
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->assertDontSee('"name":"purchase"', false);
    }

    public function test_nothing_identifying_is_sent_with_a_purchase(): void
    {
        $this->configureTags();
        [$user, $order] = $this->placedOrder();

        $response = $this->actingAs($user)->get(route('checkout.success', $order))->assertOk();
        $payload = $this->measurementPayload($response->getContent());

        $this->assertNotSame('', $payload, 'The purchase event should have rendered.');
        $this->assertStringNotContainsString($user->name, $payload);
        $this->assertStringNotContainsString((string) $user->phone_normalized, $payload);
        $this->assertStringNotContainsString('Karrada', $payload);
    }

    public function test_a_bot_is_never_reported(): void
    {
        $this->configureTags();
        $product = $this->product();

        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->get(route('shop.show', $product))
            ->assertOk()
            ->assertDontSee('"name":"view_item"', false);
    }

    private function measurementPayload(string $html): string
    {
        $start = strpos($html, 'const events = ');

        if ($start === false) {
            return '';
        }

        return substr($html, $start, 2000);
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function placedOrder(): array
    {
        $governorate = Governorate::factory()->create(['shipping_fee' => 5000]);
        $user = User::factory()->create([
            'name' => 'Dana Hassan',
            'phone' => '0770 448 8315',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'Iraq',
            'governorate_id' => $governorate->id,
            'city' => 'Baghdad',
            'address_line1' => 'Karrada, block 9',
            'phone' => '07704488315',
        ]);
        $product = $this->product(['price' => 12000, 'stock_quantity' => 10]);

        $cart = Cart::query()->create(['user_id' => $user->id]);
        CartItem::query()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)
            ->post(route('checkout.store'), ['address_id' => $address->id])
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame(2, (int) OrderItem::query()->where('order_id', $order->id)->sum('quantity'));

        return [$user, $order];
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
