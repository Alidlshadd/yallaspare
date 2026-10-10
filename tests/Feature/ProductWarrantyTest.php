<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductWarranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductWarrantyTest extends TestCase
{
    use RefreshDatabase;

    public function test_typed_text_is_read_as_the_period_it_names(): void
    {
        $this->assertSame('2_months', ProductWarranty::normalize('2 months'));
        $this->assertSame('6_months', ProductWarranty::normalize(' 6 Months '));
        $this->assertSame('1_month', ProductWarranty::normalize('1 month'));
        $this->assertSame('1_year', ProductWarranty::normalize('12 months'));
        $this->assertSame('1_year', ProductWarranty::normalize('1 Year'));
        $this->assertSame('2_years', ProductWarranty::normalize('2 years'));
        $this->assertSame('none', ProductWarranty::normalize('No warranty'));
        $this->assertSame('3_months', ProductWarranty::normalize('3_months'));

        $this->assertNull(ProductWarranty::normalize(''));
        $this->assertNull(ProductWarranty::normalize('5 months'));
        $this->assertNull(ProductWarranty::normalize('Lifetime on the housing'));
    }

    public function test_a_period_is_shown_in_the_readers_language(): void
    {
        app()->setLocale('en');
        $this->assertSame('2 months', ProductWarranty::label('2_months'));

        app()->setLocale('ar');
        $this->assertSame('شهران', ProductWarranty::label('2_months'));
        $this->assertSame('سنة واحدة', ProductWarranty::label('1_year'));

        app()->setLocale('ku');
        $this->assertSame('2 مانگ', ProductWarranty::label('2_months'));

        // Text from before the list is shown as it was typed; nothing is nothing.
        $this->assertSame('Lifetime on the housing', ProductWarranty::label('Lifetime on the housing'));
        $this->assertNull(ProductWarranty::label(null));
        $this->assertNull(ProductWarranty::label(''));
    }

    public function test_every_period_is_translated(): void
    {
        foreach (['ar', 'ku'] as $locale) {
            app()->setLocale('en');
            $english = ProductWarranty::options();

            app()->setLocale($locale);

            foreach (ProductWarranty::options() as $code => $label) {
                $this->assertNotSame($english[$code], $label, "{$code} is not translated into {$locale}.");
            }
        }
    }

    public function test_the_product_form_offers_the_periods_and_refuses_typed_text(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();

        $form = $this->actingAs($admin)->get(route('admin.products.create'))->assertOk();
        $form->assertSee('<select id="warranty" name="warranty"', false);
        $form->assertSee('value="6_months"', false);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->payload($category, ['sku' => 'W-OK', 'warranty' => '6_months']))
            ->assertSessionHasNoErrors();
        $this->assertSame('6_months', Product::where('sku', 'W-OK')->value('warranty'));

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->payload($category, ['sku' => 'W-BAD', 'warranty' => 'six months or so']))
            ->assertSessionHasErrors('warranty');
        $this->assertFalse(Product::where('sku', 'W-BAD')->exists());
    }

    public function test_older_typed_text_survives_an_edit_until_it_is_changed(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'W-OLD',
            'warranty' => 'Lifetime on the housing',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Lifetime on the housing');

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->payload($category, ['sku' => 'W-OLD', 'warranty' => 'Lifetime on the housing']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Lifetime on the housing', $product->fresh()->warranty);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product->fresh()), $this->payload($category, ['sku' => 'W-OLD', 'warranty' => '1_year']))
            ->assertSessionHasNoErrors();
        $this->assertSame('1_year', $product->fresh()->warranty);
    }

    public function test_the_product_page_shows_the_period_in_arabic(): void
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'is_active' => true,
            'warranty' => '2_months',
        ]);

        $this->get(route('shop.show', $product).'?lang=ar')
            ->assertOk()
            ->assertSee('شهران')
            ->assertDontSee('2_months');
    }

    public function test_the_migration_rewrites_recognisable_text_and_leaves_the_rest(): void
    {
        $category = Category::factory()->create();
        $typed = Product::factory()->create(['category_id' => $category->id, 'warranty' => '2 months']);
        $odd = Product::factory()->create(['category_id' => $category->id, 'warranty' => 'Ask in store']);
        $empty = Product::factory()->create(['category_id' => $category->id, 'warranty' => null]);

        (require database_path('migrations/2026_10_10_100000_normalize_product_warranty_values.php'))->up();

        $this->assertSame('2_months', $typed->fresh()->warranty);
        $this->assertSame('Ask in store', $odd->fresh()->warranty);
        $this->assertNull($empty->fresh()->warranty);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'name_en' => 'Oil Filter',
            'name_ar' => 'Oil Filter',
            'name_ku' => 'Oil Filter',
            'description_en' => 'Test description',
            'price' => 15000,
            'dealer_price' => 12000,
            'stock_quantity' => 20,
            'brand' => 'Bosch',
            'category_id' => $category->id,
            'is_active' => true,
        ], $overrides);
    }
}
