<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Searching the shop has to reach what a part fits, not only the columns on the
 * product row. An operator records "Tivoli" as a fitment relation and never
 * types it into the product name — which is correct, the name describes the
 * part — so a search reading only the products table finds nothing.
 */
class ShopCompatibilitySearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create([
            'id' => 1,
            'name_en' => 'Engine Parts',
            'name_ar' => 'Engine Parts',
            'name_ku' => 'Engine Parts',
            'slug' => 'engine-parts',
        ]);
    }

    public function test_searching_a_vehicle_model_name_returns_the_linked_product(): void
    {
        $product = $this->ssangYongOilFilter();

        $this->get(route('shop.index', ['search' => 'Tivoli']))
            ->assertOk()
            ->assertSee($product->name_en);
    }

    public function test_the_search_is_not_case_sensitive(): void
    {
        $product = $this->ssangYongOilFilter();

        $this->get(route('shop.index', ['search' => 'tivoli']))
            ->assertOk()
            ->assertSee($product->name_en);

        $this->get(route('shop.index', ['search' => 'TIVOLI']))
            ->assertOk()
            ->assertSee($product->name_en);
    }

    public function test_searching_a_model_that_does_not_fit_returns_nothing(): void
    {
        $product = $this->ssangYongOilFilter();

        $this->get(route('shop.index', ['search' => 'Korando']))
            ->assertOk()
            ->assertDontSee($product->name_en);
    }

    public function test_a_product_with_several_matching_fitments_appears_once(): void
    {
        $product = $this->ssangYongOilFilter();
        [$brand, $model] = $this->tivoli();

        // Two more Tivoli rows: another engine, another year range.
        $this->fitment($product, $brand, $model, '1.6 Diesel', 2015, 2019);
        $this->fitment($product, $brand, $model, '1.6 Petrol', 2020, 2023);

        $response = $this->get(route('shop.index', ['search' => 'Tivoli']))->assertOk();

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'data-product-card-id="'.$product->id.'"'),
            'The card was rendered more than once for a product with several matching fitments.'
        );
    }

    public function test_vehicle_brand_engine_and_fuel_searches_reach_the_product(): void
    {
        $product = $this->ssangYongOilFilter();

        foreach (['SsangYong', 'KGM', '1.6', 'Petrol', '2017'] as $term) {
            $this->get(route('shop.index', ['search' => $term]))
                ->assertOk()
                ->assertSee($product->name_en, false);
        }
    }

    public function test_sku_and_oem_search_still_work(): void
    {
        $product = $this->ssangYongOilFilter();

        $this->get(route('shop.index', ['search' => 'SY-1721840025']))
            ->assertOk()
            ->assertSee($product->name_en);

        $this->get(route('shop.index', ['search' => '1721840025']))
            ->assertOk()
            ->assertSee($product->name_en);
    }

    public function test_an_inactive_product_is_not_exposed_by_a_fitment_match(): void
    {
        $product = $this->ssangYongOilFilter();
        $product->forceFill(['is_active' => false])->save();

        $this->get(route('shop.index', ['search' => 'Tivoli']))
            ->assertOk()
            ->assertDontSee($product->name_en);
    }

    public function test_search_and_the_vehicle_dropdown_filters_narrow_together(): void
    {
        $oilFilter = $this->ssangYongOilFilter();
        [$brand, $tivoli] = $this->tivoli();

        $brakePad = Product::factory()->create(['name_en' => 'Tivoli Brake Pad Set']);
        $this->fitment($brakePad, $brand, $tivoli, '1.6 Petrol', 2015, 2019);

        $korando = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Korando',
            'name_en' => 'Korando',
            'slug' => 'korando',
            'production_start_year' => 2019,
            'production_end_year' => 2024,
        ]);
        $korandoPart = Product::factory()->create(['name_en' => 'Korando Oil Filter']);
        $this->fitment($korandoPart, $brand, $korando, '1.5 Petrol', 2019, 2024);

        // The word narrows to oil filters, the dropdown narrows to the Tivoli.
        $response = $this->get(route('shop.index', [
            'search' => 'Oil Filter',
            'brand' => 'SsangYong',
            'model' => (string) $tivoli->id,
        ]))->assertOk();

        $response->assertSee($oilFilter->name_en);
        $response->assertDontSee($brakePad->name_en);
        $response->assertDontSee($korandoPart->name_en);
    }

    public function test_pagination_links_keep_the_search_and_filters(): void
    {
        [$brand, $tivoli] = $this->tivoli();

        for ($i = 0; $i < 30; $i++) {
            $product = Product::factory()->create(['name_en' => "Tivoli Filter {$i}"]);
            $this->fitment($product, $brand, $tivoli, '1.6 Petrol', 2015, 2019);
        }

        $content = $this->get(route('shop.index', [
            'search' => 'Tivoli',
            'brand' => 'SsangYong',
            'sort' => 'price_asc',
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('page=2', $content);
        $this->assertStringContainsString('search=Tivoli', $content);
        $this->assertStringContainsString('brand=SsangYong', $content);
        $this->assertStringContainsString('sort=price_asc', $content);
    }

    public function test_autocomplete_finds_the_product_by_its_vehicle(): void
    {
        $product = $this->ssangYongOilFilter();

        $this->getJson(route('shop.autocomplete', ['q' => 'Tivoli']))
            ->assertOk()
            ->assertJsonPath('data.products.0.id', $product->id);
    }

    public function test_autocomplete_does_not_repeat_a_product_with_several_fitments(): void
    {
        $product = $this->ssangYongOilFilter();
        [$brand, $model] = $this->tivoli();
        $this->fitment($product, $brand, $model, '1.6 Diesel', 2020, 2023);

        $response = $this->getJson(route('shop.autocomplete', ['q' => 'Tivoli']))->assertOk();

        $ids = array_column($response->json('data.products'), 'id');
        $this->assertSame([$product->id], $ids);
    }

    public function test_the_card_shows_what_the_part_fits(): void
    {
        $product = $this->ssangYongOilFilter();

        $this->get(route('shop.index', ['search' => 'Tivoli']))
            ->assertOk()
            ->assertSee('Fits')
            ->assertSee('Tivoli');

        // The name itself is untouched — compatibility stays a relation.
        $this->assertSame('SsangYong Engine Oil Filter', $product->fresh()->name_en);
    }

    public function test_the_card_counts_the_remaining_models_rather_than_listing_them(): void
    {
        $product = $this->ssangYongOilFilter();
        [$brand] = $this->tivoli();

        foreach (['Korando', 'Rexton'] as $index => $name) {
            $model = VehicleModel::query()->create([
                'vehicle_brand_id' => $brand->id,
                'name' => $name,
                'name_en' => $name,
                'slug' => strtolower($name),
                'production_start_year' => 2019,
                'production_end_year' => 2024,
            ]);
            $this->fitment($product, $brand, $model, '1.5 Petrol', 2019, 2024);
        }

        $content = $this->get(route('shop.index', ['search' => 'Tivoli']))
            ->assertOk()
            ->getContent();

        // First model named, the other two counted.
        $this->assertStringContainsString('+2', $content);
    }

    public function test_the_listing_does_not_query_fitments_once_per_card(): void
    {
        [$brand, $tivoli] = $this->tivoli();

        for ($i = 0; $i < 12; $i++) {
            $product = Product::factory()->create(['name_en' => "Tivoli Part {$i}"]);
            $this->fitment($product, $brand, $tivoli, '1.6 Petrol', 2015, 2019);
        }

        $dataQueries = [];
        DB::listen(function ($query) use (&$dataQueries): void {
            // Schema introspection is not what this test is about. DbSchema
            // memoizes those per request and caches the positives across
            // requests, so in production they are paid once, not per card.
            if (str_contains($query->sql, 'sqlite_master') || str_contains($query->sql, 'information_schema')) {
                return;
            }

            $dataQueries[] = $query->sql;
        });

        $this->get(route('shop.index', ['search' => 'Tivoli']))->assertOk();

        // One IN(...) read covering every card on the page. Eager loading is
        // what makes the "Fits" hint safe to render inside a grid.
        $eagerLoads = count(array_filter(
            $dataQueries,
            fn (string $sql): bool => str_contains($sql, 'from "product_vehicle_fitments" where "product_vehicle_fitments"."product_id" in')
        ));

        $this->assertSame(1, $eagerLoads, 'Fitments were not loaded in a single read for the page.');

        // A per-card lookup would show up here as twelve.
        $perCardReads = count(array_filter(
            $dataQueries,
            fn (string $sql): bool => str_contains($sql, 'from "product_vehicle_fitments" where "product_vehicle_fitments"."product_id" = ')
        ));

        $this->assertSame(0, $perCardReads, "The listing read fitments {$perCardReads} times one product at a time.");
    }

    public function test_a_suggested_part_brand_leads_to_results(): void
    {
        $product = $this->ssangYongOilFilter();
        $product->forceFill(['brand' => 'Mann Filter'])->save();

        $suggestion = $this->getJson(route('shop.autocomplete', ['q' => 'Mann']))
            ->assertOk()
            ->json('data.brands.0');

        $this->assertNotNull($suggestion, 'No brand suggestion was returned.');

        // Following the suggestion must land on the product it was drawn from.
        $this->get($suggestion['url'])
            ->assertOk()
            ->assertSee($product->name_en);
    }

    /** @return array{VehicleBrand, VehicleModel} */
    private function tivoli(): array
    {
        $brand = VehicleBrand::query()->firstOrCreate(
            ['slug' => 'ssangyong'],
            ['name' => 'SsangYong']
        );

        $model = VehicleModel::query()->firstOrCreate(
            ['vehicle_brand_id' => $brand->id, 'slug' => 'tivoli'],
            [
                'name' => 'Tivoli',
                'name_en' => 'Tivoli',
                'production_start_year' => 2015,
                'production_end_year' => 2019,
            ]
        );

        VehicleModelEngineType::query()->firstOrCreate(
            ['vehicle_model_id' => $model->id, 'name' => '1.6 Petrol'],
            ['fuel_type' => 'petrol', 'engine_size' => 1.6]
        );

        return [$brand, $model];
    }

    private function ssangYongOilFilter(): Product
    {
        [$brand, $model] = $this->tivoli();

        $product = Product::factory()->create([
            'name_en' => 'SsangYong Engine Oil Filter',
            'name_ar' => 'SsangYong Engine Oil Filter',
            'name_ku' => 'SsangYong Engine Oil Filter',
            'sku' => 'SY-1721840025',
            'oem_number' => '1721840025',
            'brand' => 'KGM',
        ]);

        $this->fitment($product, $brand, $model, '1.6 Petrol', 2015, 2019);

        return $product;
    }

    private function fitment(
        Product $product,
        VehicleBrand $brand,
        VehicleModel $model,
        string $engine,
        int $yearFrom,
        int $yearTo,
    ): void {
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'engine' => $engine,
            'year_from' => $yearFrom,
            'year_to' => $yearTo,
        ]);
    }
}
