<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Models\VehicleModelFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "ssangyong rexton" found nothing, because the whole query went to a LIKE and
 * no single column holds a brand and a model together. These cover the shape
 * that fixes it: every word must be answered, but each word may be answered by
 * whichever part of the catalogue knows about it.
 */
class ShopMultiWordSearchTest extends TestCase
{
    use RefreshDatabase;

    private VehicleBrand $brand;

    private VehicleModelFamily $rextonFamily;

    private VehicleModel $rexton2022;

    private VehicleModel $rexton2013;

    private VehicleModel $tivoli;

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

        $this->brand = VehicleBrand::query()->create(['name' => 'SsangYong', 'slug' => 'ssangyong']);

        $this->rextonFamily = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $this->brand->id,
            'name' => 'Rexton',
            'slug' => 'rexton',
        ]);

        $this->rexton2022 = $this->variant($this->rextonFamily, 'Rexton', 2022, 2026, [['2.0 Petrol', 'petrol', 2.0]]);
        $this->rexton2013 = $this->variant($this->rextonFamily, 'Rexton', 2013, 2017, [['2.0 Petrol', 'petrol', 2.0]]);

        $tivoliFamily = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $this->brand->id,
            'name' => 'Tivoli',
            'slug' => 'tivoli',
        ]);
        $this->tivoli = $this->variant($tivoliFamily, 'Tivoli', 2015, 2019, [['1.6 Petrol', 'petrol', 1.6]]);
    }

    public function test_a_brand_and_a_model_match_through_different_relations(): void
    {
        $product = $this->part('Engine Oil Filter', $this->rexton2022);

        $this->get(route('shop.index', ['search' => 'ssangyong rexton']))
            ->assertOk()
            ->assertSee($product->name_en);
    }

    public function test_the_other_name_of_the_same_marque_works_too(): void
    {
        $product = $this->part('Engine Oil Filter', $this->rexton2022);

        // The cars are recorded as SsangYong; shoppers also type KGM.
        $this->get(route('shop.index', ['search' => 'kgm rexton']))
            ->assertOk()
            ->assertSee($product->name_en);
    }

    public function test_a_year_matches_the_range_that_covers_it(): void
    {
        $covering = $this->part('Rexton Late Oil Filter', $this->rexton2022);
        $notCovering = $this->part('Rexton Early Oil Filter', $this->rexton2013);

        $this->get(route('shop.index', ['search' => 'rexton 2024']))
            ->assertOk()
            ->assertSee($covering->name_en)
            ->assertDontSee($notCovering->name_en);
    }

    public function test_a_year_outside_every_range_returns_nothing(): void
    {
        $product = $this->part('Rexton Oil Filter', $this->rexton2013);

        $this->get(route('shop.index', ['search' => 'rexton 2024']))
            ->assertOk()
            ->assertDontSee($product->name_en);
    }

    public function test_a_fitment_row_narrows_the_years_within_a_variant(): void
    {
        // The car is built 2022-2026; this part is only recorded for 2022-2023.
        $product = $this->part('Rexton Early Batch Filter', $this->rexton2022, yearFrom: 2022, yearTo: 2023);

        $this->get(route('shop.index', ['search' => 'rexton 2023']))
            ->assertOk()
            ->assertSee($product->name_en);

        $this->get(route('shop.index', ['search' => 'rexton 2026']))
            ->assertOk()
            ->assertDontSee($product->name_en);
    }

    public function test_four_words_from_four_different_places_match_one_product(): void
    {
        $product = $this->part('Engine Oil Filter', $this->rexton2022);
        $decoy = $this->part('Engine Air Filter', $this->rexton2022);

        // brand + vehicle + year + product name, none of them in one column.
        $this->get(route('shop.index', ['search' => 'ssangyong rexton 2024 oil']))
            ->assertOk()
            ->assertSee($product->name_en)
            ->assertDontSee($decoy->name_en);
    }

    public function test_a_model_an_engine_size_and_a_fuel_match_together(): void
    {
        $product = $this->part('Cabin Filter', $this->tivoli);
        $decoy = $this->part('Rexton Cabin Filter', $this->rexton2022);

        $this->get(route('shop.index', ['search' => 'tivoli 1.6 petrol']))
            ->assertOk()
            ->assertSee($product->name_en)
            ->assertDontSee($decoy->name_en);
    }

    public function test_a_part_name_and_a_vehicle_match_together(): void
    {
        $product = $this->part('Engine Oil Filter', $this->rexton2022);
        $decoy = $this->part('Tivoli Only Oil Filter', $this->tivoli);

        $this->get(route('shop.index', ['search' => 'rexton oil filter']))
            ->assertOk()
            ->assertSee($product->name_en)
            ->assertDontSee($decoy->name_en);
    }

    public function test_a_word_that_matches_nothing_removes_the_product(): void
    {
        $product = $this->part('Engine Oil Filter', $this->rexton2022);

        // Rexton is right, Korando is not: every word has to land.
        $this->get(route('shop.index', ['search' => 'rexton korando']))
            ->assertOk()
            ->assertDontSee($product->name_en);
    }

    public function test_an_exact_sku_comes_first(): void
    {
        $decoy = $this->part('Filter Kit Mentioning SY-99', $this->rexton2022);
        $decoy->forceFill(['description_en' => 'Includes SY-99'])->save();

        $exact = $this->part('Some Other Filter', $this->rexton2022);
        $exact->forceFill(['sku' => 'SY-99'])->save();

        $content = $this->get(route('shop.index', ['search' => 'SY-99']))->assertOk()->getContent();

        $this->assertLessThan(
            strpos($content, (string) $decoy->name_en),
            strpos($content, (string) $exact->name_en),
            'The product whose SKU is exactly the query was not first.'
        );
    }

    public function test_an_exact_oem_number_comes_first(): void
    {
        $decoy = $this->part('Kit Listing 1721840025', $this->rexton2022);
        $decoy->forceFill(['description_en' => 'Replaces 1721840025'])->save();

        $exact = $this->part('Genuine Oil Filter', $this->rexton2022);
        $exact->forceFill(['oem_number' => '1721840025'])->save();

        $content = $this->get(route('shop.index', ['search' => '1721840025']))->assertOk()->getContent();

        $this->assertLessThan(
            strpos($content, (string) $decoy->name_en),
            strpos($content, (string) $exact->name_en),
            'The product whose OEM number is exactly the query was not first.'
        );
    }

    public function test_a_part_number_typed_with_its_dash_finds_one_stored_without(): void
    {
        $product = $this->part('Genuine Oil Filter', $this->rexton2022);
        $product->forceFill(['sku' => 'SY1721840025'])->save();

        $this->get(route('shop.index', ['search' => 'SY-1721840025']))
            ->assertOk()
            ->assertSee($product->name_en);
    }

    public function test_a_product_matching_several_ways_appears_once(): void
    {
        $product = $this->part('Rexton Oil Filter', $this->rexton2022);
        // A second and third fitment row that also answer the query.
        $this->fitment($product, $this->rexton2022, 2022, 2026);
        $this->fitment($product, $this->rexton2013, 2013, 2017);

        $content = $this->get(route('shop.index', ['search' => 'ssangyong rexton']))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            substr_count($content, 'data-product-card-id="'.$product->id.'"'),
            'The product was rendered more than once.'
        );
    }

    public function test_an_inactive_product_stays_hidden(): void
    {
        $product = $this->part('Engine Oil Filter', $this->rexton2022);
        $product->forceFill(['is_active' => false])->save();

        $this->get(route('shop.index', ['search' => 'ssangyong rexton']))
            ->assertOk()
            ->assertDontSee($product->name_en);
    }

    public function test_search_still_narrows_alongside_the_vehicle_filters(): void
    {
        $wanted = $this->part('Engine Oil Filter', $this->rexton2022);
        $otherCar = $this->part('Engine Oil Filter For Tivoli', $this->tivoli);

        $this->get(route('shop.index', [
            'search' => 'oil filter',
            'brand' => 'SsangYong',
            'model' => (string) $this->rexton2022->id,
        ]))
            ->assertOk()
            ->assertSee($wanted->name_en)
            ->assertDontSee($otherCar->name_en);
    }

    public function test_pagination_keeps_a_multi_word_query(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->part("Rexton Oil Filter {$i}", $this->rexton2022);
        }

        $content = $this->get(route('shop.index', ['search' => 'ssangyong rexton']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('page=2', $content);
        $this->assertStringContainsString('search=ssangyong%20rexton', $content);
    }

    public function test_a_multi_word_search_does_not_query_per_product(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->part("Rexton Oil Filter {$i}", $this->rexton2022);
        }

        $perProduct = 0;
        DB::listen(function ($query) use (&$perProduct): void {
            if (str_contains($query->sql, 'product_vehicle_fitments')
                && str_contains($query->sql, '"product_id" = ')) {
                $perProduct++;
            }
        });

        $this->get(route('shop.index', ['search' => 'ssangyong rexton 2024']))->assertOk();

        $this->assertSame(0, $perProduct, 'Fitments were read one product at a time.');
    }

    /**
     * @param  list<array{0: string, 1: string, 2: float}>  $engines
     */
    private function variant(VehicleModelFamily $family, string $name, int $from, int $to, array $engines): VehicleModel
    {
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $this->brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => $name,
            'name_en' => $name,
            'slug' => strtolower($name).'-'.$from,
            'production_start_year' => $from,
            'production_end_year' => $to,
        ]);

        foreach ($engines as [$engineName, $fuel, $size]) {
            VehicleModelEngineType::query()->create([
                'vehicle_model_id' => $model->id,
                'name' => $engineName,
                'fuel_type' => $fuel,
                'engine_size' => $size,
            ]);
        }

        return $model->fresh(['engineTypes']);
    }

    private function part(string $name, VehicleModel $model, ?int $yearFrom = null, ?int $yearTo = null): Product
    {
        $product = Product::factory()->create(['name_en' => $name, 'name_ar' => $name, 'name_ku' => $name]);
        $this->fitment($product, $model, $yearFrom, $yearTo);

        return $product;
    }

    private function fitment(Product $product, VehicleModel $model, ?int $yearFrom = null, ?int $yearTo = null): void
    {
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $this->brand->id,
            'vehicle_model_id' => $model->id,
            'year_from' => $yearFrom,
            'year_to' => $yearTo,
        ]);
    }
}
