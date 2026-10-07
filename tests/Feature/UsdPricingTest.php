<?php

namespace Tests\Feature;

use App\Exceptions\CartPricesChangedException;
use App\Http\View\Composers\HeaderComposer;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ManualInvoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Invoices\ManualInvoiceService;
use App\Services\Pricing\ExchangeRateService;
use App\Support\Pricing\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Dollar-priced products, the hand-set exchange rate, and what must not move
 * when it changes.
 */
class UsdPricingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function setRate(string $perHundred): array
    {
        return app(ExchangeRateService::class)->setRate($perHundred, $this->admin);
    }

    private function usdProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory()->create()->id,
            'name_en' => 'Dollar Brake Disc',
            'price_currency' => 'USD',
            'price_usd' => '10.00',
            'price' => 0,
            'stock_quantity' => 20,
        ], $overrides));
    }

    private function iqdProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory()->create()->id,
            'name_en' => 'Dinar Oil Filter',
            'price' => 20000,
            'stock_quantity' => 20,
        ], $overrides));
    }

    private function customer(): User
    {
        return User::factory()->create();
    }

    private function addressFor(User $user): UserAddress
    {
        return UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'Iraq',
            'city' => 'Baghdad',
            'address_line1' => 'Street 10',
            'phone' => '123456789',
            'is_default' => true,
        ]);
    }

    /**
     * The exchange rate page's own form: the rate and the default currency,
     * nothing else, and no password prompt in the way.
     */
    private function saveSettings(array $overrides)
    {
        return $this
            ->actingAs($this->admin)
            ->put(route('admin.exchange-rate.update'), array_merge([
                'default_price_currency' => ExchangeRate::defaultCurrency(),
            ], $overrides));
    }

    // ── The conversion itself ──────────────────────────────────────

    public function test_dollars_convert_at_the_rate_per_hundred_and_round_half_up_to_a_dinar(): void
    {
        $this->assertSame('15000', ExchangeRate::toIqd('10', '150000'));
        $this->assertSame('17000', ExchangeRate::toIqd('10.00', '170000'));
        // 10.99 × 1475.50 = 16215.745 → 16216; 0.01 × 1450 = 14.5 → 15.
        $this->assertSame('16216', ExchangeRate::toIqd('10.99', '147550'));
        $this->assertSame('15', ExchangeRate::toIqd('0.01', '145000'));
        // A float handed over by a cast is pinned before any arithmetic.
        $this->assertSame('1485', ExchangeRate::toIqd(0.99, '150000'));
        $this->assertSame('1500', ExchangeRate::perDollar('150000'));
        $this->assertSame('1475.5', ExchangeRate::perDollar('147550'));
    }

    public function test_a_rate_that_is_not_a_positive_number_is_not_a_rate(): void
    {
        foreach (['0', '-150000', 'abc', '', '1e5', '0.00', null] as $invalid) {
            $this->assertNull(ExchangeRate::normalizeRate($invalid), var_export($invalid, true));
        }

        $this->assertSame('150000.00', ExchangeRate::normalizeRate('150000'));
    }

    // ── Settings ───────────────────────────────────────────────────

    public function test_the_admin_sets_the_rate_and_it_records_when_and_who(): void
    {
        $this->saveSettings(['usd_rate_per_100' => '150000', 'default_price_currency' => 'USD'])
            ->assertRedirect(route('admin.exchange-rate.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('150000.00', ExchangeRate::perHundred());
        $this->assertSame('USD', ExchangeRate::defaultCurrency());
        $this->assertSame((string) $this->admin->id, Setting::getValue('usd_rate_updated_by'));
        $this->assertSame($this->admin->name, Setting::getValue('usd_rate_updated_by_name'));
        $this->assertNotEmpty(Setting::getValue('usd_rate_updated_at'));

        $this->usdProduct(['name_en' => 'Listed Dollar Part']);

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit'))
            ->assertOk()
            ->assertSee('How many IQD is 100 USD?')
            ->assertSee('1 USD = 1500 IQD')
            ->assertSee($this->admin->name)
            ->assertSee('Listed Dollar Part')
            ->assertSee('15,000 IQD');
    }

    public function test_a_rate_typed_with_separators_or_arabic_digits_is_understood(): void
    {
        $this->saveSettings(['usd_rate_per_100' => '150,000'])->assertSessionHasNoErrors();
        $this->assertSame('150000.00', ExchangeRate::perHundred());

        $this->saveSettings(['usd_rate_per_100' => '١٧٠٠٠٠'])->assertSessionHasNoErrors();
        $this->assertSame('170000.00', ExchangeRate::perHundred());

        $this->saveSettings(['usd_rate_per_100' => '147 550.5'])->assertSessionHasNoErrors();
        $this->assertSame('147550.50', ExchangeRate::perHundred());
    }

    public function test_the_rate_page_is_only_for_those_who_manage_settings(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('admin.exchange-rate.edit'))->assertForbidden();
        $this->actingAs($customer)
            ->put(route('admin.exchange-rate.update'), ['default_price_currency' => 'IQD', 'usd_rate_per_100' => '1'])
            ->assertForbidden();

        $this->assertNull(ExchangeRate::perHundred());
    }

    public function test_the_system_settings_form_no_longer_touches_the_rate(): void
    {
        $this->setRate('150000');

        $this
            ->withSession([
                'admin_2fa.verified_user_id' => $this->admin->id,
                'auth.password_confirmed_at' => time(),
            ])
            ->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'Yalla Spare',
                'currency_code' => 'IQD',
                'currency_symbol' => 'IQD',
                'low_stock_threshold' => 5,
                'shipping_fee' => 5000,
                'storefront_hero_title' => 'Find the right spare parts faster',
                'storefront_hero_subtitle' => 'Browse the catalog.',
                'storefront_hero_button_label' => 'Shop now',
                'storefront_hero_button_url' => '',
                'usd_rate_per_100' => '999999',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('150000.00', ExchangeRate::perHundred());
    }

    public function test_a_zero_negative_or_invalid_rate_is_refused_and_the_old_one_stays(): void
    {
        $this->setRate('150000');

        foreach (['0', '-5', 'abc', '1e9'] as $invalid) {
            $this->saveSettings(['usd_rate_per_100' => $invalid])
                ->assertSessionHasErrors('usd_rate_per_100');
        }

        $this->assertSame('150000.00', ExchangeRate::perHundred());

        // Left empty, the form keeps the rate rather than clearing it.
        $this->saveSettings(['usd_rate_per_100' => ''])->assertSessionHasNoErrors();
        $this->assertSame('150000.00', ExchangeRate::perHundred());
    }

    public function test_new_products_start_on_usd_once_a_rate_exists(): void
    {
        $this->assertSame('USD', ExchangeRate::defaultCurrency());

        // With no rate there is nothing to convert with, so the form still
        // opens on dinars and says why dollars are not on offer.
        $this->actingAs($this->admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('<option value="IQD" selected', false)
            ->assertSee('No exchange rate has been set yet.');

        $this->setRate('150000');

        $this->actingAs($this->admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('<option value="USD" selected', false);
    }

    public function test_changing_the_default_currency_does_not_reinterpret_existing_products(): void
    {
        $this->setRate('150000');
        $dinar = $this->iqdProduct();
        $dollar = $this->usdProduct();

        $this->saveSettings(['default_price_currency' => 'USD'])->assertSessionHasNoErrors();

        $dinar->refresh();
        $this->assertFalse($dinar->isUsdPriced());
        $this->assertSame('20000.00', $dinar->price);
        $this->assertNull($dinar->price_usd);

        $this->saveSettings(['default_price_currency' => 'IQD'])->assertSessionHasNoErrors();

        $dollar->refresh();
        $this->assertTrue($dollar->isUsdPriced());
        $this->assertSame('10.0000', $dollar->price_usd);
        $this->assertSame('15000.00', $dollar->price);
    }

    // ── Products ───────────────────────────────────────────────────

    public function test_a_usd_product_follows_the_rate_and_an_iqd_product_does_not(): void
    {
        $this->setRate('150000');
        $dollar = $this->usdProduct(['dealer_price_usd' => '8.00']);
        $dinar = $this->iqdProduct(['dealer_price' => 18000]);

        $this->assertSame('15000.00', $dollar->fresh()->price);
        $this->assertSame('12000.00', $dollar->fresh()->dealer_price);

        $result = $this->setRate('170000');

        $this->assertTrue($result['changed']);
        $this->assertSame(1, $result['repriced']);

        $dollar->refresh();
        $this->assertSame('10.0000', $dollar->price_usd);
        $this->assertSame('17000.00', $dollar->price);
        $this->assertSame('13600.00', $dollar->dealer_price);
        $this->assertSame(17000.0, $dollar->priceFor(null));

        $dinar->refresh();
        $this->assertSame('20000.00', $dinar->price);
        $this->assertSame('18000.00', $dinar->dealer_price);
    }

    public function test_repeated_rate_changes_leave_no_residue(): void
    {
        $this->setRate('147550');
        $product = $this->usdProduct(['price_usd' => '10.99']);
        $this->assertSame('16216.00', $product->fresh()->price);

        foreach (['133333', '171717', '99999.99', '147550'] as $rate) {
            $this->setRate($rate);
        }

        $product->refresh();
        $this->assertSame('10.9900', $product->price_usd);
        $this->assertSame('16216.00', $product->price);
    }

    public function test_saving_the_same_rate_again_changes_nothing(): void
    {
        $this->setRate('150000');
        $stamp = Setting::getValue('usd_rate_updated_at');

        $this->travel(5)->minutes();
        $result = $this->setRate('150000.00');

        $this->assertFalse($result['changed']);
        $this->assertSame($stamp, Setting::getValue('usd_rate_updated_at'));
    }

    public function test_the_admin_prices_a_product_in_usd_from_the_form(): void
    {
        $this->setRate('150000');
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name_en' => 'Dollar Alternator',
            'name_ar' => 'Dollar Alternator',
            'name_ku' => 'Dollar Alternator',
            'price_currency' => 'USD',
            'price' => '10',
            'dealer_price' => '8.50',
            'cost_price' => '6',
            'stock_quantity' => 4,
            'sku' => 'USD-ALT-1',
            'category_id' => $category->id,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $product = Product::query()->where('sku', 'USD-ALT-1')->firstOrFail();

        $this->assertTrue($product->isUsdPriced());
        $this->assertSame('10.0000', $product->price_usd);
        $this->assertSame('8.5000', $product->dealer_price_usd);
        $this->assertSame('6.0000', $product->cost_price_usd);
        $this->assertSame('15000.00', $product->price);
        $this->assertSame('12750.00', $product->dealer_price);
        $this->assertSame('9000.00', $product->cost_price);
        $this->assertSame(6000.0, $product->unitProfit());

        $this->actingAs($this->admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Price currency')
            ->assertSee('Purchase price (your cost)')
            ->assertSee('value="10.00"', false);
    }

    public function test_an_iqd_price_from_the_form_is_stored_as_typed(): void
    {
        $this->setRate('150000');
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name_en' => 'Dinar Wiper',
            'name_ar' => 'Dinar Wiper',
            'name_ku' => 'Dinar Wiper',
            'price_currency' => 'IQD',
            'price' => '12500',
            'cost_price' => '9000',
            'stock_quantity' => 4,
            'sku' => 'IQD-WIP-1',
            'category_id' => $category->id,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $product = Product::query()->where('sku', 'IQD-WIP-1')->firstOrFail();
        $this->setRate('170000');
        $product->refresh();

        $this->assertFalse($product->isUsdPriced());
        $this->assertNull($product->price_usd);
        $this->assertSame('12500.00', $product->price);
        $this->assertSame('9000.00', $product->cost_price);
    }

    public function test_a_product_cannot_be_priced_in_usd_without_a_rate(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name_en' => 'No Rate Part',
            'name_ar' => 'No Rate Part',
            'name_ku' => 'No Rate Part',
            'price_currency' => 'USD',
            'price' => '10',
            'stock_quantity' => 1,
            'sku' => 'NO-RATE-1',
            'category_id' => $category->id,
        ])->assertSessionHasErrors('price_currency');

        $this->assertDatabaseMissing('products', ['sku' => 'NO-RATE-1']);
    }

    public function test_the_purchase_price_never_leaves_with_a_serialized_product(): void
    {
        $product = $this->iqdProduct(['cost_price' => 9000]);

        $this->assertArrayNotHasKey('cost_price', $product->fresh()->toArray());
        $this->assertStringNotContainsString('cost_price', $product->fresh()->toJson());
    }

    // ── Storefront ─────────────────────────────────────────────────

    public function test_catalogue_search_and_product_page_show_the_price_at_the_new_rate(): void
    {
        $this->setRate('150000');
        $product = $this->usdProduct(['name_en' => 'Zephyr Radiator']);

        $this->get(route('shop.show', $product))->assertOk()->assertSee('15,000');

        $this->setRate('170000');

        $this->get(route('shop.show', $product))->assertOk()->assertSee('17,000')->assertDontSee('15,000');
        $this->get(route('shop.index', ['search' => 'Zephyr']))->assertOk()->assertSee('17,000')->assertDontSee('15,000');
    }

    public function test_the_header_cart_total_follows_the_rate_at_once(): void
    {
        $this->setRate('150000');
        $user = $this->customer();
        $product = $this->usdProduct();
        $cart = Cart::query()->create(['user_id' => $user->id]);
        app(CartService::class)->addProduct($cart, $product, 2);

        $this->assertSame(30000.0, app(HeaderComposer::class)->cartSummaryFor($user)['subtotal']);

        $this->setRate('170000');

        $this->assertSame(34000.0, app(HeaderComposer::class)->cartSummaryFor($user)['subtotal']);
    }

    // ── Cart and checkout ──────────────────────────────────────────

    public function test_the_cart_tells_the_customer_when_the_rate_moved_a_price_and_only_once(): void
    {
        $this->setRate('150000');
        $user = $this->customer();
        $dollar = $this->usdProduct();
        $dinar = $this->iqdProduct();
        $cart = Cart::query()->create(['user_id' => $user->id]);
        app(CartService::class)->addProduct($cart, $dollar, 1);
        app(CartService::class)->addProduct($cart, $dinar, 1);

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()
            ->assertDontSee('Prices updated');

        $this->setRate('170000');

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Prices updated')
            ->assertSee('was 15,000.00 IQD, now 17,000.00 IQD')
            ->assertSee('37,000');

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()
            ->assertDontSee('Prices updated');
    }

    public function test_checkout_stops_once_when_the_rate_changed_after_the_customer_last_saw_the_cart(): void
    {
        $this->setRate('150000');
        $user = $this->customer();
        $address = $this->addressFor($user);
        $product = $this->usdProduct();
        $cart = Cart::query()->create(['user_id' => $user->id]);
        app(CartService::class)->addProduct($cart, $product, 2);

        $this->setRate('170000');

        $this->actingAs($user)
            ->post(route('checkout.store'), ['address_id' => $address->id])
            ->assertSessionHas('error');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(20, (int) $product->fresh()->stock_quantity);

        // They have now been told; the same request goes through at the new price.
        $this->actingAs($user)
            ->post(route('checkout.store'), ['address_id' => $address->id])
            ->assertSessionMissing('error');

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame(34000.0, (float) $order->subtotal_amount);
        $this->assertSame(17000.0, (float) $order->items->first()->unit_price);
    }

    public function test_the_checkout_service_itself_refuses_a_cart_priced_at_an_old_rate(): void
    {
        $this->setRate('150000');
        $user = $this->customer();
        $address = $this->addressFor($user);
        $cart = Cart::query()->create(['user_id' => $user->id]);
        app(CartService::class)->addProduct($cart, $this->usdProduct(), 1);
        $this->setRate('170000');

        $this->expectException(CartPricesChangedException::class);

        app(CheckoutService::class)->placeCartOrder($cart->fresh(), $user, $address, null, '');
    }

    public function test_an_order_keeps_its_prices_rate_and_cost_when_the_rate_changes_later(): void
    {
        $this->setRate('150000');
        $user = $this->customer();
        $address = $this->addressFor($user);
        $dollar = $this->usdProduct(['cost_price_usd' => '6.00']);
        $dinar = $this->iqdProduct(['cost_price' => 14000]);
        $cart = Cart::query()->create(['user_id' => $user->id]);
        app(CartService::class)->addProduct($cart, $dollar, 2);
        app(CartService::class)->addProduct($cart, $dinar, 1);

        $order = app(CheckoutService::class)->placeCartOrder($cart->fresh(), $user, $address, null, '');
        $totalAtSale = (float) $order->grand_total;

        $this->assertSame(50000.0, (float) $order->subtotal_amount);

        $this->setRate('170000');
        $dollar->update(['cost_price_usd' => '9.00']);

        $order = $order->fresh('items');
        $dollarLine = $order->items->firstWhere('product_id', $dollar->id);
        $dinarLine = $order->items->firstWhere('product_id', $dinar->id);

        $this->assertSame($totalAtSale, (float) $order->grand_total);
        $this->assertSame(50000.0, (float) $order->subtotal_amount);

        $this->assertSame(15000.0, (float) $dollarLine->unit_price);
        $this->assertSame('USD', $dollarLine->price_currency);
        $this->assertSame(10.0, (float) $dollarLine->usd_unit_price);
        $this->assertSame(150000.0, (float) $dollarLine->usd_rate_per_100);
        $this->assertSame(9000.0, (float) $dollarLine->unit_cost);

        $this->assertSame(20000.0, (float) $dinarLine->unit_price);
        $this->assertSame('IQD', $dinarLine->price_currency);
        $this->assertNull($dinarLine->usd_unit_price);
        $this->assertNull($dinarLine->usd_rate_per_100);
        $this->assertSame(14000.0, (float) $dinarLine->unit_cost);

        // The catalogue did move.
        $this->assertSame('17000.00', $dollar->fresh()->price);
        $this->assertSame('15300.00', $dollar->fresh()->cost_price);
    }

    public function test_a_buy_now_order_uses_the_current_rate(): void
    {
        $this->setRate('150000');
        $user = $this->customer();
        $address = $this->addressFor($user);
        $product = $this->usdProduct();
        $this->setRate('170000');

        $order = app(CheckoutService::class)->placeBuyNowOrder($product, 1, $user, $address, null, '');

        $this->assertSame(17000.0, (float) $order->subtotal_amount);
        $this->assertSame(170000.0, (float) $order->items()->first()->usd_rate_per_100);
    }

    // ── Net profit ─────────────────────────────────────────────────

    public function test_the_revenue_page_reports_net_profit_from_the_cost_at_the_time_of_sale(): void
    {
        $this->setRate('150000');
        $user = $this->customer();
        $address = $this->addressFor($user);
        $costed = $this->usdProduct(['cost_price_usd' => '6.00']);
        $uncosted = $this->iqdProduct();
        $cart = Cart::query()->create(['user_id' => $user->id]);
        app(CartService::class)->addProduct($cart, $costed, 2);
        app(CartService::class)->addProduct($cart, $uncosted, 1);

        $order = app(CheckoutService::class)->placeCartOrder($cart->fresh(), $user, $address, null, '');
        $order->forceFill(['status' => 'delivered'])->save();

        // Neither a later rate nor a later cost may rewrite what was earned.
        $this->setRate('170000');
        $costed->update(['cost_price_usd' => '9.99']);

        // 2 × 15,000 sold, 2 × 9,000 paid: 12,000 earned on the costed line.
        $this->actingAs($this->admin)
            ->get(route('admin.revenue.index'))
            ->assertOk()
            ->assertSee('Net profit in range')
            ->assertSee('12,000')
            ->assertSee('1 lines have no purchase price and are left out.');

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Net profit')
            ->assertSee('12,000')
            ->assertSee('$10.00 · 1 USD = 1500 IQD');
    }

    // ── Manual invoices ────────────────────────────────────────────

    private function invoiceCustomer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Karwan Garage',
            'phone' => '+9647701234567',
            'city' => 'Erbil',
            'address' => '60 Meter Street',
        ]);
    }

    private function usdDraft(Product $product, int $quantity = 2): ManualInvoice
    {
        $picked = $this->actingAs($this->admin)
            ->getJson(route('admin.manual-invoices.products.search', ['q' => $product->name_en]))
            ->assertOk()
            ->json('data.0');

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.store'), [
            'customer_id' => $this->invoiceCustomer()->id,
            'invoice_date' => '2026-10-07',
            'discount_amount' => 0,
            'delivery_fee' => 0,
            'action' => 'draft',
            'items' => [[
                'product_id' => $picked['id'],
                'description' => $picked['name'],
                'sku' => $picked['sku'],
                'quantity' => $quantity,
                'unit_price' => $picked['price'],
                'usd_unit_price' => $picked['usd_price'],
                'usd_rate_per_100' => $picked['usd_rate'],
            ]],
        ])->assertSessionHasNoErrors();

        return ManualInvoice::query()->latest('id')->firstOrFail();
    }

    public function test_the_invoice_picker_offers_a_usd_product_at_the_current_rate(): void
    {
        $this->setRate('150000');
        $product = $this->usdProduct();
        $this->setRate('170000');

        $picked = $this->actingAs($this->admin)
            ->getJson(route('admin.manual-invoices.products.search', ['q' => $product->name_en]))
            ->json('data.0');

        $this->assertSame(17000, (int) $picked['price']);
        $this->assertSame('10.0000', $picked['usd_price']);
        $this->assertSame('170000.00', $picked['usd_rate']);
    }

    public function test_a_draft_shows_the_difference_and_can_be_recalculated_at_the_current_rate(): void
    {
        $this->setRate('150000');
        $product = $this->usdProduct(['cost_price_usd' => '6.00']);
        $draft = $this->usdDraft($product);

        $this->assertSame(30000.0, $draft->total);
        $this->assertNull(app(ManualInvoiceService::class)->exchangeRateDrift($draft->load('items')));

        $this->setRate('170000');

        // Opening or re-reading the draft does not reprice it on its own.
        $this->assertSame(30000.0, $draft->fresh()->total);

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.show', $draft))
            ->assertOk()
            ->assertSee('The exchange rate has changed since this draft was priced.')
            ->assertSee('Recalculate at the current rate')
            ->assertSee('30,000')
            ->assertSee('34,000')
            ->assertSee('+4,000');

        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.reprice', $draft))
            ->assertRedirect(route('admin.manual-invoices.show', $draft));

        $draft = $draft->fresh('items');
        $line = $draft->items->first();

        $this->assertSame(34000.0, $draft->total);
        $this->assertSame(17000.0, $line->unit_price);
        $this->assertSame(10.0, (float) $line->usd_unit_price);
        $this->assertSame(170000.0, (float) $line->usd_rate_per_100);
        $this->assertSame(10200.0, (float) $line->unit_cost);
        $this->assertNull(app(ManualInvoiceService::class)->exchangeRateDrift($draft));
    }

    public function test_a_unit_price_typed_by_hand_is_a_dinar_line_the_rate_cannot_move(): void
    {
        $this->setRate('150000');
        $product = $this->usdProduct();

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.store'), [
            'customer_id' => $this->invoiceCustomer()->id,
            'invoice_date' => '2026-10-07',
            'action' => 'draft',
            'items' => [[
                'product_id' => $product->id,
                'description' => 'Agreed price',
                'quantity' => 1,
                'unit_price' => 14000,
            ]],
        ])->assertSessionHasNoErrors();

        $draft = ManualInvoice::query()->latest('id')->firstOrFail();
        $this->setRate('170000');

        $this->assertNull($draft->items()->first()->usd_unit_price);
        $this->assertNull(app(ManualInvoiceService::class)->exchangeRateDrift($draft->load('items')));
    }

    public function test_a_finalized_invoice_is_not_changed_by_a_later_rate_and_cannot_be_recalculated(): void
    {
        $this->setRate('150000');
        $product = $this->usdProduct();
        $draft = $this->usdDraft($product);

        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.finalize', $draft))
            ->assertRedirect();

        $this->setRate('170000');

        $invoice = $draft->fresh('items');
        $this->assertTrue($invoice->isFinalized());
        $this->assertSame(30000.0, $invoice->total);
        $this->assertSame(15000.0, $invoice->items->first()->unit_price);
        $this->assertSame(150000.0, (float) $invoice->items->first()->usd_rate_per_100);
        $this->assertNull(app(ManualInvoiceService::class)->exchangeRateDrift($invoice));

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('Recalculate at the current rate');

        try {
            app(ManualInvoiceService::class)->repriceDraft($invoice, $this->admin);
            $this->fail('A finalized invoice was repriced.');
        } catch (ValidationException) {
            $this->assertSame(30000.0, $invoice->fresh()->total);
        }
    }
}
