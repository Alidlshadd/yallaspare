<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The fitment form used a filter box beside a native <select>, and several
 * products are called "SsangYong Engine Air Filter". A flat line of text was
 * the only thing separating them, so the wrong part could be picked without
 * anything on the page saying so. These cover what the picker needs from the
 * server to make the difference visible.
 */
class ProductPickerSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create([
            'id' => 1,
            'name_en' => 'Filters',
            'name_ar' => 'Filters',
            'name_ku' => 'Filters',
            'slug' => 'filters',
        ]);
    }

    public function test_a_product_is_found_by_its_name(): void
    {
        $product = $this->oilFilter();

        $this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'engine oil filter']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $product->id);
    }

    public function test_a_product_is_found_by_its_sku(): void
    {
        $product = $this->oilFilter();

        $this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'SY-1721840025']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $product->id);
    }

    public function test_a_product_is_found_by_its_oem_number(): void
    {
        $product = $this->oilFilter();

        $this->actingAs($this->admin())
            ->getJson($this->url(['q' => '1721840025']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $product->id);
    }

    public function test_a_punctuated_number_finds_the_bare_one(): void
    {
        $product = $this->oilFilter();
        $product->forceFill(['sku' => 'SY1721840025', 'oem_number' => '1721840025'])->save();

        // Typed with the dash the label shows, stored without it.
        $this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'SY-1721840025']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $product->id);
    }

    public function test_the_search_ignores_letter_case(): void
    {
        $product = $this->oilFilter();

        foreach (['ENGINE OIL FILTER', 'engine oil filter'] as $term) {
            $this->actingAs($this->admin())
                ->getJson($this->url(['q' => $term]))
                ->assertOk()
                ->assertJsonPath('results.0.id', $product->id);
        }
    }

    public function test_products_sharing_a_name_are_separated_by_sku_and_image(): void
    {
        $first = $this->airFilter('SY-AIR-1');
        $second = $this->airFilter('SY-AIR-2');
        $this->image($first, 'products/first.png');
        $this->image($second, 'products/second.png');

        $results = $this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'Engine Air Filter']))
            ->assertOk()
            ->json('results');

        $this->assertCount(2, $results);
        $this->assertSame(['SY-AIR-1', 'SY-AIR-2'], array_column($results, 'sku'));

        $images = array_column($results, 'image_url');
        $this->assertNotSame($images[0], $images[1]);
        $this->assertStringContainsString('first', (string) $images[0]);
        $this->assertStringContainsString('second', (string) $images[1]);
    }

    public function test_a_row_carries_everything_the_operator_needs(): void
    {
        Setting::setValue('currency_code', 'IQD');
        $product = $this->oilFilter();
        $this->image($product, 'products/oil.png');

        $row = $this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'Oil Filter']))
            ->assertOk()
            ->json('results.0');

        $this->assertSame($product->id, $row['id']);
        $this->assertSame('SsangYong Engine Oil Filter', $row['name']);
        $this->assertSame('SY-1721840025', $row['sku']);
        $this->assertSame('1721840025', $row['oem']);
        $this->assertSame('KGM', $row['brand']);
        $this->assertStringContainsString('IQD', $row['price_formatted']);
        $this->assertSame(12, $row['stock_quantity']);
        $this->assertSame('in_stock', $row['stock_state']);
        $this->assertStringContainsString('oil', $row['image_url']);
        $this->assertTrue($row['selectable']);
    }

    public function test_the_cover_image_wins_over_the_first_upload(): void
    {
        $product = $this->oilFilter();
        $this->image($product, 'products/plain.png', sortOrder: 1);
        $this->image($product, 'products/cover.png', sortOrder: 2, primary: true);

        $row = $this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'Oil Filter']))
            ->assertOk()
            ->json('results.0');

        $this->assertStringContainsString('cover', $row['image_url']);
    }

    public function test_a_product_with_no_image_reports_none(): void
    {
        $this->oilFilter();

        $row = $this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'Oil Filter']))
            ->assertOk()
            ->json('results.0');

        // Null, so the picker draws its own placeholder rather than a broken img.
        $this->assertNull($row['image_url']);
    }

    public function test_stock_states_are_reported(): void
    {
        Setting::setValue('low_stock_threshold', 5);

        $inStock = $this->airFilter('SKU-IN', ['stock_quantity' => 40]);
        $low = $this->airFilter('SKU-LOW', ['stock_quantity' => 3]);
        $out = $this->airFilter('SKU-OUT', ['stock_quantity' => 0]);

        $rows = collect($this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'Engine Air Filter']))
            ->assertOk()
            ->json('results'))->keyBy('id');

        $this->assertSame('in_stock', $rows[$inStock->id]['stock_state']);
        $this->assertSame('low_stock', $rows[$low->id]['stock_state']);
        $this->assertSame('out_of_stock', $rows[$out->id]['stock_state']);
    }

    public function test_an_empty_query_returns_the_most_recent_products(): void
    {
        $older = $this->airFilter('SKU-OLD');
        $newer = $this->airFilter('SKU-NEW');

        $results = $this->actingAs($this->admin())
            ->getJson($this->url())
            ->assertOk()
            ->json('results');

        $this->assertSame($newer->id, $results[0]['id']);
        $this->assertSame($older->id, $results[1]['id']);
    }

    public function test_a_selection_can_be_redrawn_from_its_id_alone(): void
    {
        $product = $this->oilFilter();
        $this->image($product, 'products/oil.png');

        // What the picker asks for after a validation failure.
        $results = $this->actingAs($this->admin())
            ->getJson($this->url(['ids' => (string) $product->id]))
            ->assertOk()
            ->json('results');

        $this->assertCount(1, $results);
        $this->assertSame($product->id, $results[0]['id']);
        $this->assertStringContainsString('oil', $results[0]['image_url']);
    }

    public function test_an_archived_product_is_labelled_and_not_selectable(): void
    {
        $product = $this->oilFilter();
        $product->forceFill(['is_active' => false])->save();

        // It never turns up in a search...
        $this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'Oil Filter']))
            ->assertOk()
            ->assertJsonCount(0, 'results');

        // ...but a selection made before it was archived still redraws, marked.
        $row = $this->actingAs($this->admin())
            ->getJson($this->url(['ids' => (string) $product->id]))
            ->assertOk()
            ->json('results.0');

        $this->assertSame('archived', $row['stock_state']);
        $this->assertFalse($row['selectable']);
    }

    public function test_the_endpoint_is_closed_to_everyone_but_product_managers(): void
    {
        $this->oilFilter();

        $this->getJson($this->url(['q' => 'Oil']))->assertUnauthorized();

        $shopper = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($shopper)->getJson($this->url(['q' => 'Oil']))->assertForbidden();

        // An order manager is staff, but products are not their table.
        $orderManager = User::factory()->create([
            'role' => User::ROLE_ORDER_MANAGER,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($orderManager)->getJson($this->url(['q' => 'Oil']))->assertForbidden();
    }

    public function test_images_are_not_read_once_per_result(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $product = $this->airFilter('SKU-'.$i);
            $this->image($product, 'products/img-'.$i.'.png');
        }

        $perRow = 0;
        DB::listen(function ($query) use (&$perRow): void {
            if (str_contains($query->sql, 'product_images') && str_contains($query->sql, '"product_id" = ')) {
                $perRow++;
            }
        });

        $this->actingAs($this->admin())
            ->getJson($this->url(['q' => 'Engine Air Filter']))
            ->assertOk()
            ->assertJsonCount(15, 'results');

        $this->assertSame(0, $perRow, 'Images were read one product at a time.');
    }

    public function test_the_form_renders_the_picker_and_keeps_the_product_id_field(): void
    {
        $this->oilFilter();

        $content = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-product-picker', $content);
        $this->assertStringContainsString('data-product-picker-select', $content);
        $this->assertMatchesRegularExpression('/<select[^>]*name="product_id"/', $content);

        // The old pair is gone.
        $this->assertStringNotContainsString('data-admin-product-filter', $content);
        $this->assertStringNotContainsString('Filter by product name, SKU, or brand', $content);
    }

    public function test_a_failed_validation_keeps_the_chosen_product(): void
    {
        $product = $this->oilFilter();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [['vehicle_brand_id' => '', 'vehicle_model_id' => '']],
            ])
            ->assertSessionHasErrors()
            ->assertSessionHasInput('product_id', (string) $product->id);
    }

    /** @param array<string, mixed> $attributes */
    private function airFilter(string $sku, array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'name_en' => 'SsangYong Engine Air Filter',
            'name_ar' => 'SsangYong Engine Air Filter',
            'name_ku' => 'SsangYong Engine Air Filter',
            'sku' => $sku,
            'brand' => 'KGM',
            'stock_quantity' => 20,
        ], $attributes));
    }

    private function oilFilter(): Product
    {
        return Product::factory()->create([
            'name_en' => 'SsangYong Engine Oil Filter',
            'name_ar' => 'SsangYong Engine Oil Filter',
            'name_ku' => 'SsangYong Engine Oil Filter',
            'sku' => 'SY-1721840025',
            'oem_number' => '1721840025',
            'brand' => 'KGM',
            'price' => 15000,
            'stock_quantity' => 12,
        ]);
    }

    private function image(Product $product, string $path, int $sortOrder = 0, bool $primary = false): ProductImage
    {
        return ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $path,
            'sort_order' => $sortOrder,
            'is_primary' => $primary,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /** @param array<string, string> $params */
    private function url(array $params = []): string
    {
        return route('admin.vehicle-fitments.products.search', $params);
    }
}
