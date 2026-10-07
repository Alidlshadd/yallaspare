<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceChange;
use App\Models\User;
use App\Services\Pricing\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function setupProduct(): Product
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'email_verified_at' => now()]);
        $this->actingAs($admin);
        app(ExchangeRateService::class)->setRate('170000', $admin);

        return Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'price_currency' => 'USD', 'price_usd' => '10.0000', 'price' => 0,
        ]);
    }

    public function test_inline_save_returns_the_authoritative_price_and_rate_without_redirecting(): void
    {
        $product = $this->setupProduct();

        $this->putJson(route('admin.exchange-rate.products.update', $product), ['basis' => 'iqd', 'target_iqd' => '16,000'])
            ->assertOk()->assertJsonPath('changed', true)
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('product.usd', '9.4118')
            ->assertJsonPath('product.iqd', '16000.00')
            ->assertJsonPath('product.rate', '170000.00');
        $this->assertSame(1, ProductPriceChange::query()->count());
    }

    public function test_invalid_inline_save_returns_field_errors_and_preserves_the_saved_price(): void
    {
        $product = $this->setupProduct();

        $this->putJson(route('admin.exchange-rate.products.update', $product), ['basis' => 'usd', 'usd_price' => '-4'])
            ->assertUnprocessable()->assertJsonValidationErrors('usd_price');
        $this->assertSame('10.0000', $product->fresh()->price_usd);
        $this->assertSame(0, ProductPriceChange::query()->count());
    }

    public function test_unchanged_inline_save_returns_a_successful_no_change_response(): void
    {
        $product = $this->setupProduct();

        $this->putJson(route('admin.exchange-rate.products.update', $product), ['basis' => 'usd', 'usd_price' => '10'])
            ->assertOk()->assertJsonPath('changed', false)->assertJsonPath('product.iqd', '17000.00');
        $this->assertSame(0, ProductPriceChange::query()->count());
    }

    public function test_rate_save_returns_display_metadata_and_reprices_usd_products(): void
    {
        $product = $this->setupProduct();

        $this->putJson(route('admin.exchange-rate.update'), ['usd_rate_per_100' => '180,000', 'default_price_currency' => 'USD'])
            ->assertOk()->assertJsonPath('rate', '180000.00')->assertJsonPath('default_currency', 'USD')
            ->assertJsonStructure(['message', 'updated_at', 'updated_by']);
        $this->assertSame('18000.00', $product->fresh()->price);
    }

    public function test_json_requests_still_require_product_management_permission(): void
    {
        $product = $this->setupProduct();
        $this->actingAs(User::factory()->create());

        $this->putJson(route('admin.exchange-rate.products.update', $product), ['basis' => 'usd', 'usd_price' => '1'])
            ->assertForbidden();
        $this->assertSame('10.0000', $product->fresh()->price_usd);
    }

    public function test_bulk_json_preview_retains_selection_and_only_changes_prices_after_apply(): void
    {
        $product = $this->setupProduct();
        $response = $this->postJson(route('admin.exchange-rate.bulk.preview'), [
            'bulk_mode' => 'percent', 'bulk_value' => '10',
            'bulk_scope' => 'selected', 'ids' => [$product->id],
        ])->assertOk()->assertJsonStructure(['redirect']);

        $this->assertSame('10.0000', $product->fresh()->price_usd);
        $this->get($response->json('redirect'))->assertOk()
            ->assertSee('data-check="bulk" checked', false);
        parse_str(parse_url($response->json('redirect'), PHP_URL_QUERY), $query);

        $applied = $this->postJson(route('admin.exchange-rate.bulk.apply'), ['token' => $query['bulk']])
            ->assertOk()->assertJsonStructure(['redirect'])->assertSessionHas('success');
        $this->assertSame('11.0000', $product->fresh()->price_usd);
        $this->get($applied->json('redirect'))->assertOk()->assertSee('The price of 1 products was changed.');
        $this->postJson(route('admin.exchange-rate.bulk.apply'), ['token' => $query['bulk']])
            ->assertOk()->assertSessionHas('warning');
        $this->assertSame('11.0000', $product->fresh()->price_usd);
        $this->assertSame(1, ProductPriceChange::query()->count());
    }

    public function test_bulk_json_validation_does_not_navigate_or_change_prices(): void
    {
        $product = $this->setupProduct();
        $this->postJson(route('admin.exchange-rate.bulk.preview'), [
            'bulk_mode' => 'percent', 'bulk_value' => 'invalid',
            'bulk_scope' => 'selected', 'ids' => [$product->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('bulk_value');
        $this->assertSame('10.0000', $product->fresh()->price_usd);
    }

    public function test_kurdish_keyboard_digits_work_for_rates_and_inline_prices(): void
    {
        $product = $this->setupProduct();
        $this->putJson(route('admin.exchange-rate.update'), [
            'usd_rate_per_100' => '۱۸۰٬۰۰۰', 'default_price_currency' => 'USD',
        ])->assertOk()->assertJsonPath('rate', '180000.00');
        $this->putJson(route('admin.exchange-rate.products.update', $product), [
            'basis' => 'usd', 'usd_price' => '۱۲٫۵۰',
        ])->assertOk()->assertJsonPath('product.usd', '12.5000')->assertJsonPath('product.iqd', '22500.00');
    }
}
