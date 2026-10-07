<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\PriceManagementController;
use App\Http\View\Composers\HeaderComposer;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ManualInvoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPriceChange;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Pricing\ExchangeRateService;
use App\Services\Pricing\ProductPriceService;
use App\Support\Pricing\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The price tools on the exchange rate page: converting dinar prices to
 * dollars once, editing a price, changing many — and everything that must
 * stay put while they do.
 */
class PriceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $this->category = Category::factory()->create();
    }

    private function setRate(string $perHundred): void
    {
        app(ExchangeRateService::class)->setRate($perHundred, $this->admin);
    }

    private function iqd(string $sku, float $price, array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => $this->category->id,
            'name_en' => 'Part '.$sku,
            'sku' => $sku,
            'price' => $price,
            'stock_quantity' => 7,
        ], $overrides));
    }

    private function usd(string $sku, string $priceUsd, array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => $this->category->id,
            'name_en' => 'Part '.$sku,
            'sku' => $sku,
            'price_currency' => 'USD',
            'price_usd' => $priceUsd,
            'price' => 0,
            'stock_quantity' => 7,
        ], $overrides));
    }

    /** The one-time token the page mints when it shows a conversion preview. */
    private function conversionToken(string $conversionRate = '150000'): string
    {
        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit', ['convert' => 'preview', 'conversion_rate' => $conversionRate]))
            ->assertOk();

        return (string) array_key_last((array) session(PriceManagementController::SESSION_KEY));
    }

    /** Ask for a bulk change and return the token its preview was filed under. */
    private function bulkToken(array $payload): string
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.exchange-rate.bulk.preview'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        return (string) $query['bulk'];
    }

    private function applyBulk(string $token): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.exchange-rate.bulk.apply'), ['token' => $token]);
    }

    // ── The arithmetic ─────────────────────────────────────────────

    public function test_old_dinar_prices_become_dollars_at_the_conversion_rate(): void
    {
        $this->assertSame('10.0000', ExchangeRate::fromIqd('15000', '150000'));
        $this->assertSame('20.0000', ExchangeRate::fromIqd('30000', '150000'));
        $this->assertSame('50.0000', ExchangeRate::fromIqd('75000', '150000'));
        // Not every price divides evenly; four decimals keep what is left.
        $this->assertSame('8.3333', ExchangeRate::fromIqd('12500', '150000'));
    }

    public function test_a_dinar_target_comes_back_as_exactly_that_many_dinars(): void
    {
        foreach (['150000', '170000', '147550', '131000.50'] as $rate) {
            foreach (['250', '16000', '17000', '18250', '999750', '1234567'] as $target) {
                $plan = ExchangeRate::usdForTargetIqd($target, $rate);

                $this->assertTrue($plan['exact'], "{$target} at {$rate}");
                $this->assertSame($target, ExchangeRate::toIqd($plan['usd'], $rate), "{$target} at {$rate}");
            }
        }

        $this->assertSame('9.4118', ExchangeRate::usdForTargetIqd('16000', '170000')['usd']);
        $this->assertSame('10.0000', ExchangeRate::usdForTargetIqd('17000', '170000')['usd']);
        $this->assertSame('9.4118', ExchangeRate::formatUsd('9.4118'));
        $this->assertSame('10.00', ExchangeRate::formatUsd('10.0000'));
    }

    // ── Converting dinar products to dollars ───────────────────────

    public function test_the_conversion_is_previewed_before_anything_changes(): void
    {
        $this->setRate('170000');
        $product = $this->iqd('CNV-1', 15000);

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit', ['convert' => 'preview', 'conversion_rate' => '150000']))
            ->assertOk()
            ->assertSee('Convert existing IQD products to USD')
            ->assertSee('Part CNV-1')
            ->assertSee('15,000 IQD')
            ->assertSee('$10.00')
            ->assertSee('17,000 IQD')
            ->assertSee('Convert all 1 products');

        $this->assertFalse($product->fresh()->isUsdPriced());
        $this->assertSame(0, ProductPriceChange::query()->count());
    }

    public function test_dinar_products_convert_at_the_conversion_rate_and_sell_at_the_selling_rate(): void
    {
        $this->setRate('170000');
        $a = $this->iqd('CNV-1', 15000, ['dealer_price' => 12000, 'cost_price' => 9000]);
        $b = $this->iqd('CNV-2', 30000);
        $c = $this->iqd('CNV-3', 75000);
        $already = $this->usd('USD-1', '5.00');

        $this->actingAs($this->admin)
            ->post(route('admin.exchange-rate.convert'), ['token' => $this->conversionToken(), 'scope' => 'all'])
            ->assertRedirect(route('admin.exchange-rate.edit'))
            ->assertSessionHas('success');

        foreach ([[$a, '10.0000', '17000.00'], [$b, '20.0000', '34000.00'], [$c, '50.0000', '85000.00']] as [$product, $usd, $iqd]) {
            $product->refresh();
            $this->assertTrue($product->isUsdPriced());
            $this->assertSame($usd, $product->price_usd);
            $this->assertSame($iqd, $product->price);
        }

        // The dealer price went with it, at the same two rates.
        $this->assertSame('8.0000', $a->dealer_price_usd);
        $this->assertSame('13600.00', $a->dealer_price);

        // What was paid for the part, and how many are on the shelf, did not move.
        $this->assertSame('9000.00', $a->cost_price);
        $this->assertNull($a->cost_price_usd);
        $this->assertSame(7, (int) $a->stock_quantity);

        // A product already in dollars was not divided again.
        $this->assertSame('5.0000', $already->fresh()->price_usd);

        $record = ProductPriceChange::query()->where('product_id', $a->id)->sole();
        $this->assertSame('conversion', $record->action);
        $this->assertSame('IQD', $record->old_currency);
        $this->assertSame('USD', $record->new_currency);
        $this->assertSame(15000.0, $record->old_price_iqd);
        $this->assertSame(17000.0, $record->new_price_iqd);
        $this->assertNull($record->old_price_usd);
        $this->assertSame('10.0000', $record->new_price_usd);
        $this->assertSame('150000.00', $record->conversion_rate_per_100);
        $this->assertSame('170000.00', $record->selling_rate_per_100);
        $this->assertSame($this->admin->id, (int) $record->user_id);
        $this->assertSame(3, ProductPriceChange::query()->count());

        // The conversion rate is remembered on its own; the selling rate is untouched.
        $this->assertSame('150000.00', ExchangeRate::conversionRate());
        $this->assertSame('170000.00', ExchangeRate::perHundred());
    }

    public function test_with_both_rates_equal_no_dinar_price_moves(): void
    {
        $this->setRate('150000');
        $product = $this->iqd('CNV-1', 15000);

        $this->actingAs($this->admin)
            ->post(route('admin.exchange-rate.convert'), ['token' => $this->conversionToken(), 'scope' => 'all']);

        $product->refresh();
        $this->assertSame('10.0000', $product->price_usd);
        $this->assertSame('15000.00', $product->price);
    }

    public function test_running_the_conversion_again_never_divides_a_price_twice(): void
    {
        $this->setRate('170000');
        $product = $this->iqd('CNV-1', 15000);
        $token = $this->conversionToken();

        $this->actingAs($this->admin)->post(route('admin.exchange-rate.convert'), ['token' => $token, 'scope' => 'all']);

        // The same form submitted again: its token is spent.
        $this->actingAs($this->admin)
            ->post(route('admin.exchange-rate.convert'), ['token' => $token, 'scope' => 'all'])
            ->assertSessionHas('warning');

        // A fresh conversion aimed straight at the same product: it is in
        // dollars now, so it is not a candidate.
        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit', ['convert' => 'preview']))
            ->assertOk()
            ->assertSee('There are no IQD-priced products left to convert.');

        $result = app(ProductPriceService::class)->convertToUsd([$product->id], '150000', $this->admin);

        $this->assertSame(['converted' => 0, 'skipped' => 1], ['converted' => $result['converted'], 'skipped' => $result['skipped']]);
        $this->assertSame('10.0000', $product->fresh()->price_usd);
        $this->assertSame('17000.00', $product->fresh()->price);
        $this->assertSame(1, ProductPriceChange::query()->count());
    }

    public function test_only_the_ticked_products_are_converted(): void
    {
        $this->setRate('150000');
        $ticked = $this->iqd('CNV-1', 15000);
        $left = $this->iqd('CNV-2', 30000);

        $this->actingAs($this->admin)
            ->post(route('admin.exchange-rate.convert'), ['token' => $this->conversionToken(), 'scope' => 'selected', 'ids' => [$ticked->id]])
            ->assertSessionHas('success');

        $this->assertTrue($ticked->fresh()->isUsdPriced());
        $this->assertFalse($left->fresh()->isUsdPriced());
        $this->assertSame('30000.00', $left->fresh()->price);
    }

    public function test_converting_needs_a_selling_rate(): void
    {
        $product = $this->iqd('CNV-1', 15000);

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit', ['convert' => 'preview']))
            ->assertOk()
            ->assertSee('Set the exchange rate first.')
            ->assertDontSee('Convert all');

        $this->assertFalse($product->fresh()->isUsdPriced());
    }

    // ── The rate, as opposed to a price ────────────────────────────

    public function test_changing_the_rate_moves_the_dinar_price_and_leaves_the_dollar_price_and_the_history_alone(): void
    {
        $this->setRate('150000');
        $product = $this->usd('USD-1', '10.00');

        $this->assertSame('15000.00', $product->fresh()->price);

        $this->actingAs($this->admin)
            ->put(route('admin.exchange-rate.update'), ['default_price_currency' => 'USD', 'usd_rate_per_100' => '170000'])
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('10.0000', $product->price_usd);
        $this->assertSame('17000.00', $product->price);
        $this->assertSame(0, ProductPriceChange::query()->count());
    }

    // ── Editing one product ────────────────────────────────────────

    public function test_the_page_lists_products_with_search_filter_and_pages(): void
    {
        $this->setRate('170000');
        $this->usd('USD-BRAKE', '10.00', ['name_en' => 'Zephyr Brake Disc']);
        $this->iqd('IQD-FILTER', 8000, ['name_en' => 'Quartz Oil Filter']);

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit'))
            ->assertOk()
            ->assertSee('Exchange Rate &amp; Price Management', false)
            ->assertSee('Edit product prices')
            ->assertSee('Zephyr Brake Disc')
            ->assertSee('Quartz Oil Filter')
            ->assertSee('value="10.00"', false)
            ->assertSee('17,000 IQD');

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit', ['q' => 'zephyr']))
            ->assertSee('Zephyr Brake Disc')
            ->assertDontSee('Quartz Oil Filter');

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit', ['currency' => 'IQD']))
            ->assertSee('Quartz Oil Filter')
            ->assertDontSee('Zephyr Brake Disc');

        foreach (range(1, 22) as $i) {
            $this->iqd('PAGE-'.$i, 1000 + $i, ['name_en' => 'Aaa Paged Part '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit'))
            ->assertSee('Aaa Paged Part 01')
            ->assertDontSee('Zephyr Brake Disc');

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit', ['page' => 2]))
            ->assertSee('Zephyr Brake Disc');
    }

    public function test_a_dollar_price_is_edited_in_dollars(): void
    {
        $this->setRate('170000');
        $product = $this->usd('USD-1', '10.00');

        $this->actingAs($this->admin)
            ->put(route('admin.exchange-rate.products.update', $product), ['basis' => 'usd', 'usd_price' => '12.50'])
            ->assertSessionHas('success');

        $product->refresh();
        $this->assertSame('12.5000', $product->price_usd);
        $this->assertSame('21250.00', $product->price);

        $record = ProductPriceChange::query()->sole();
        $this->assertSame('edit', $record->action);
        $this->assertSame('10.0000', $record->old_price_usd);
        $this->assertSame('12.5000', $record->new_price_usd);
        $this->assertSame(17000.0, $record->old_price_iqd);
        $this->assertSame(21250.0, $record->new_price_iqd);
    }

    public function test_naming_a_dinar_price_works_out_the_dollar_price_that_sells_for_it(): void
    {
        $this->setRate('170000');
        $product = $this->usd('USD-1', '10.00');

        // 17,000 today; the admin wants 16,000.
        $this->actingAs($this->admin)
            ->put(route('admin.exchange-rate.products.update', $product), ['basis' => 'iqd', 'usd_price' => '10.00', 'target_iqd' => '16,000'])
            ->assertSessionHas('success');

        $product->refresh();
        $this->assertSame('9.4118', $product->price_usd);
        $this->assertSame('16000.00', $product->price);

        // And it is a dollar price: the next rate moves it like any other.
        $this->setRate('150000');
        $this->assertSame('14118.00', $product->fresh()->price);
        $this->assertSame('9.4118', $product->fresh()->price_usd);
    }

    public function test_a_dinar_product_keeps_its_currency_when_its_price_is_edited(): void
    {
        $this->setRate('170000');
        $product = $this->iqd('IQD-1', 16000);

        $this->actingAs($this->admin)
            ->put(route('admin.exchange-rate.products.update', $product), ['basis' => 'iqd', 'target_iqd' => '17000'])
            ->assertSessionHas('success');

        $product->refresh();
        $this->assertFalse($product->isUsdPriced());
        $this->assertNull($product->price_usd);
        $this->assertSame('17000.00', $product->price);

        $this->setRate('190000');
        $this->assertSame('17000.00', $product->fresh()->price);
    }

    public function test_bad_prices_are_refused_and_an_unchanged_price_writes_no_history(): void
    {
        $this->setRate('170000');
        $product = $this->usd('USD-1', '10.00');

        foreach ([['usd_price' => '0'], ['usd_price' => '-4'], ['usd_price' => 'abc'], ['usd_price' => '1.23456'], ['basis' => 'iqd', 'target_iqd' => '0']] as $payload) {
            $this->actingAs($this->admin)
                ->put(route('admin.exchange-rate.products.update', $product), $payload)
                ->assertSessionHasErrors();
        }

        $this->actingAs($this->admin)
            ->put(route('admin.exchange-rate.products.update', $product), ['basis' => 'usd', 'usd_price' => '10'])
            ->assertSessionHas('warning');

        $this->assertSame('10.0000', $product->fresh()->price_usd);
        $this->assertSame(0, ProductPriceChange::query()->count());
    }

    // ── Changing many prices ───────────────────────────────────────

    public function test_a_bulk_change_is_previewed_then_applied_once(): void
    {
        $this->setRate('170000');
        $dollar = $this->usd('USD-1', '10.00', ['dealer_price_usd' => '8.00', 'cost_price_usd' => '6.00']);
        $dinar = $this->iqd('IQD-1', 20000, ['dealer_price' => 18000, 'cost_price' => 14000]);
        $untouched = $this->usd('USD-2', '30.00');

        $token = $this->bulkToken([
            'bulk_mode' => 'percent', 'bulk_value' => '10', 'bulk_scope' => 'selected', 'ids' => [$dollar->id, $dinar->id],
        ]);

        // The preview: old and new for each, how many, and nothing saved.
        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit', ['bulk' => $token]))
            ->assertOk()
            ->assertSee('Confirm the price change')
            ->assertSee('It will be applied to 2 products.')
            ->assertSee('17,000 IQD')
            ->assertSee('18,700 IQD')
            ->assertSee('$11.00')
            ->assertSee('22,000 IQD')
            ->assertSee('Nothing has been saved yet.');

        $this->assertSame('10.0000', $dollar->fresh()->price_usd);
        $this->assertSame(0, ProductPriceChange::query()->count());

        $this->applyBulk($token)->assertSessionHas('success');

        $dollar->refresh();
        $dinar->refresh();
        $this->assertSame('11.0000', $dollar->price_usd);
        $this->assertSame('18700.00', $dollar->price);
        $this->assertSame('8.8000', $dollar->dealer_price_usd);
        $this->assertSame('14960.00', $dollar->dealer_price);
        $this->assertSame('22000.00', $dinar->price);
        $this->assertSame('19800.00', $dinar->dealer_price);
        $this->assertSame('30.0000', $untouched->fresh()->price_usd);

        // Cost and stock are not this operation's to change.
        $this->assertSame('6.0000', $dollar->cost_price_usd);
        $this->assertSame('14000.00', $dinar->cost_price);
        $this->assertSame(7, (int) $dollar->stock_quantity);

        // The same confirmation sent again does nothing: no second 10%.
        $this->applyBulk($token)->assertSessionHas('warning');

        $this->assertSame('11.0000', $dollar->fresh()->price_usd);
        $this->assertSame('22000.00', $dinar->fresh()->price);
        $this->assertSame(2, ProductPriceChange::query()->where('action', 'bulk')->count());
        $this->assertSame(1, ProductPriceChange::query()->distinct()->count('batch_id'));
        $this->assertSame('+10%', ProductPriceChange::query()->first()->note);
    }

    public function test_a_fixed_dinar_amount_on_a_dollar_product_lands_on_the_dinar_figure(): void
    {
        $this->setRate('170000');
        $dollar = $this->usd('USD-1', '10.00', ['dealer_price_usd' => '8.00']);
        $dinar = $this->iqd('IQD-1', 20000);

        // Everything the filter selects: dollar products only.
        $token = $this->bulkToken([
            'bulk_mode' => 'iqd', 'bulk_value' => '-1,000', 'bulk_scope' => 'filtered', 'currency' => 'USD',
        ]);

        $this->applyBulk($token)->assertSessionHas('success');

        $dollar->refresh();
        $this->assertSame('16000.00', $dollar->price);
        $this->assertSame('9.4118', $dollar->price_usd);
        // A fixed amount is about the selling price; the dealer price stays.
        $this->assertSame('8.0000', $dollar->dealer_price_usd);
        $this->assertSame('20000.00', $dinar->fresh()->price);
    }

    public function test_a_fixed_dollar_amount_on_a_dinar_product_is_added_at_the_selling_rate(): void
    {
        $this->setRate('170000');
        $dollar = $this->usd('USD-1', '10.00');
        $dinar = $this->iqd('IQD-1', 20000);

        $token = $this->bulkToken([
            'bulk_mode' => 'usd', 'bulk_value' => '1.5', 'bulk_scope' => 'selected', 'ids' => [$dollar->id, $dinar->id],
        ]);

        $this->applyBulk($token);

        $this->assertSame('11.5000', $dollar->fresh()->price_usd);
        $this->assertSame('22550.00', $dinar->fresh()->price);
        $this->assertFalse($dinar->fresh()->isUsdPriced());
    }

    public function test_a_change_that_would_take_a_price_to_nothing_skips_that_product(): void
    {
        $this->setRate('170000');
        $cheap = $this->iqd('IQD-CHEAP', 500);
        $dear = $this->iqd('IQD-DEAR', 20000);

        $token = $this->bulkToken([
            'bulk_mode' => 'iqd', 'bulk_value' => '-1000', 'bulk_scope' => 'selected', 'ids' => [$cheap->id, $dear->id],
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit', ['bulk' => $token]))
            ->assertSee('The price would fall to zero or below.');

        $this->applyBulk($token);

        $this->assertSame('500.00', $cheap->fresh()->price);
        $this->assertSame('19000.00', $dear->fresh()->price);
    }

    public function test_a_bulk_change_needs_a_real_amount_and_something_to_apply_it_to(): void
    {
        $this->setRate('170000');
        $product = $this->usd('USD-1', '10.00');

        foreach ([
            ['bulk_mode' => 'percent', 'bulk_value' => '0', 'bulk_scope' => 'selected', 'ids' => [$product->id]],
            ['bulk_mode' => 'percent', 'bulk_value' => '-100', 'bulk_scope' => 'selected', 'ids' => [$product->id]],
            ['bulk_mode' => 'percent', 'bulk_value' => 'abc', 'bulk_scope' => 'selected', 'ids' => [$product->id]],
            ['bulk_mode' => 'percent', 'bulk_value' => '10', 'bulk_scope' => 'selected'],
            ['bulk_mode' => 'yen', 'bulk_value' => '10', 'bulk_scope' => 'filtered'],
        ] as $payload) {
            $this->actingAs($this->admin)
                ->post(route('admin.exchange-rate.bulk.preview'), $payload)
                ->assertSessionHasErrors();
        }

        $this->assertSame('10.0000', $product->fresh()->price_usd);
    }

    // ── Who may do this ────────────────────────────────────────────

    public function test_price_tools_are_for_those_who_manage_products(): void
    {
        $this->setRate('170000');
        $product = $this->usd('USD-1', '10.00');
        $customer = User::factory()->create();

        $attempts = fn () => [
            $this->put(route('admin.exchange-rate.products.update', $product), ['usd_price' => '1']),
            $this->post(route('admin.exchange-rate.convert'), ['token' => 'x', 'scope' => 'all']),
            $this->post(route('admin.exchange-rate.bulk.preview'), ['bulk_mode' => 'percent', 'bulk_value' => '50', 'bulk_scope' => 'filtered']),
            $this->post(route('admin.exchange-rate.bulk.apply'), ['token' => 'x']),
        ];

        $this->actingAs($customer);
        foreach ($attempts() as $response) {
            $response->assertForbidden();
        }

        // Someone who manages settings but not the catalogue sees the rate
        // and none of the price tools.
        $settingsOnly = User::factory()->create(['role' => User::ROLE_SETTINGS_MANAGER, 'email_verified_at' => now()]);

        if (! $settingsOnly->can(User::PERMISSION_PRODUCTS_MANAGE)) {
            $this->actingAs($settingsOnly)
                ->get(route('admin.exchange-rate.edit'))
                ->assertOk()
                ->assertSee('How many IQD is 100 USD?')
                ->assertDontSee('Edit product prices')
                ->assertDontSee('Convert existing IQD products to USD');

            foreach ($attempts() as $response) {
                $response->assertForbidden();
            }
        }

        $this->assertSame('10.0000', $product->fresh()->price_usd);
        $this->assertSame(0, ProductPriceChange::query()->count());
    }

    // ── The rest of the shop ───────────────────────────────────────

    public function test_a_price_edit_reaches_the_shop_and_the_cart_and_leaves_past_orders_alone(): void
    {
        $this->setRate('170000');
        $product = $this->usd('USD-1', '10.00', ['name_en' => 'Zephyr Radiator']);
        $buyer = User::factory()->create();
        $address = UserAddress::query()->create([
            'user_id' => $buyer->id, 'label' => 'Home', 'country' => 'Iraq', 'city' => 'Baghdad',
            'address_line1' => 'Street 10', 'phone' => '123456789', 'is_default' => true,
        ]);

        // An order at the old price.
        $cart = Cart::query()->create(['user_id' => $buyer->id]);
        app(CartService::class)->addProduct($cart, $product, 1);
        $order = app(CheckoutService::class)->placeCartOrder($cart->fresh(), $buyer, $address, null, '');

        // A cart holding the product at the old price.
        app(CartService::class)->addProduct($cart->fresh(), $product, 2);
        $this->assertSame(34000.0, app(HeaderComposer::class)->cartSummaryFor($buyer)['subtotal']);

        $this->actingAs($this->admin)
            ->put(route('admin.exchange-rate.products.update', $product), ['basis' => 'iqd', 'target_iqd' => '18000']);

        // Catalogue, search and the header's cart total follow at once.
        $this->get(route('shop.show', $product))->assertOk()->assertSee('18,000');
        $this->get(route('shop.index', ['search' => 'Zephyr']))->assertOk()->assertSee('18,000');
        $this->assertSame(36000.0, app(HeaderComposer::class)->cartSummaryFor($buyer)['subtotal']);

        // Checkout will not charge a total the customer was never shown.
        $this->actingAs($buyer)
            ->post(route('checkout.store'), ['address_id' => $address->id])
            ->assertSessionHas('error');
        $this->assertSame(1, Order::query()->count());

        // The cart says what changed; after that the order goes through.
        $cart->items()->update(['seen_unit_price' => 17000]);
        $this->actingAs($buyer)->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Some prices in your cart have changed.')
            ->assertSee('Price updated: was 17,000.00 IQD, now 18,000.00 IQD');

        $this->actingAs($buyer)
            ->post(route('checkout.store'), ['address_id' => $address->id])
            ->assertSessionMissing('error');

        $newOrder = Order::query()->latest('id')->firstOrFail();
        $this->assertSame(36000.0, (float) $newOrder->subtotal_amount);

        // The order placed before the edit still says what was charged then.
        $order->refresh();
        $this->assertSame(17000.0, (float) $order->subtotal_amount);
        $this->assertSame(17000.0, (float) $order->items()->first()->unit_price);
        $this->assertSame(10.0, (float) $order->items()->first()->usd_unit_price);
    }

    public function test_a_new_manual_invoice_picks_up_an_edited_price_exactly(): void
    {
        $this->setRate('170000');
        $product = $this->usd('USD-1', '10.00');

        $this->actingAs($this->admin)
            ->put(route('admin.exchange-rate.products.update', $product), ['basis' => 'iqd', 'target_iqd' => '16000']);

        $picked = $this->actingAs($this->admin)
            ->getJson(route('admin.manual-invoices.products.search', ['q' => 'USD-1']))
            ->json('data.0');

        $this->assertSame(16000, (int) $picked['price']);
        $this->assertSame('9.4118', $picked['usd_price']);

        $customer = Customer::query()->create(['name' => 'Karwan Garage', 'phone' => '+9647701234567']);

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-09',
            'action' => 'draft',
            'items' => [[
                'product_id' => $picked['id'], 'description' => $picked['name'], 'quantity' => 3,
                'unit_price' => $picked['price'], 'usd_unit_price' => $picked['usd_price'], 'usd_rate_per_100' => $picked['usd_rate'],
            ]],
        ])->assertSessionHasNoErrors();

        $invoice = ManualInvoice::query()->latest('id')->firstOrFail();

        // Four decimals survive the round trip, so the line is the price.
        $this->assertSame(16000.0, $invoice->items()->first()->unit_price);
        $this->assertSame(48000.0, $invoice->total);
    }

    public function test_recent_changes_are_listed_on_the_page(): void
    {
        $this->setRate('170000');
        $product = $this->iqd('CNV-1', 15000, ['name_en' => 'Listed History Part']);

        $this->actingAs($this->admin)
            ->post(route('admin.exchange-rate.convert'), ['token' => $this->conversionToken(), 'scope' => 'all']);

        $this->actingAs($this->admin)
            ->get(route('admin.exchange-rate.edit'))
            ->assertOk()
            ->assertSeeInOrder(['Recent price changes', 'Listed History Part', 'Converted to USD', '1 USD = 1500 IQD', '15,000 IQD', '17,000 IQD', $this->admin->name]);

        $this->assertNotNull($product->fresh()->price_usd);
    }
}
