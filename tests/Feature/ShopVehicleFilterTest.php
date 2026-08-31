<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopVehicleFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create([
            'id' => 1,
            'name_en' => 'Vehicle Parts',
            'slug' => 'vehicle-parts',
        ]);
    }

    public function test_vehicle_option_map_keeps_engines_and_years_scoped_to_each_model(): void
    {
        [$brand, $actyon] = $this->vehicle('KGM', 'Actyon', 2007, 2011, ['2.0 T', '2.3']);
        [, $rexton] = $this->vehicle('KGM', 'Rexton', 2013, 2017, ['3.2'], $brand);

        $response = $this->get(route('shop.index'))->assertOk();
        $response->assertSee('name="engine"', false);
        $response->assertSee('name="year"', false);

        preg_match("/data-vehicle-option-map='([^']+)'/", $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);

        $optionMap = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true, 512, JSON_THROW_ON_ERROR);

        // Keyed by variant id: two variants may share a name, and a map keyed
        // by name would hold only one of them.
        $actyonKey = (string) $actyon->id;
        $rextonKey = (string) $rexton->id;

        $this->assertSame(['2.0 T', '2.3'], array_column($optionMap['KGM'][$actyonKey]['engines'], 'value'));
        $this->assertSame(2007, $optionMap['KGM'][$actyonKey]['year_from']);
        $this->assertSame(2011, $optionMap['KGM'][$actyonKey]['year_to']);
        $this->assertNotContains('3.2', array_column($optionMap['KGM'][$actyonKey]['engines'], 'value'));
        $this->assertSame(['3.2'], array_column($optionMap['KGM'][$rextonKey]['engines'], 'value'));
    }

    public function test_model_and_engine_must_match_the_same_product_fitment(): void
    {
        [$brand, $actyon] = $this->vehicle('KGM', 'Actyon', 2007, 2011, ['2.0 T', '2.3']);
        [, $rexton] = $this->vehicle('KGM', 'Rexton', 2012, 2018, ['2.3'], $brand);
        $correctProduct = Product::factory()->create(['name_en' => 'Correct Actyon Part']);
        $crossMatchProduct = Product::factory()->create(['name_en' => 'Wrong Cross Match Part']);

        $this->fitment($correctProduct, $brand, $actyon, '2.3', 2007, 2011);
        $this->fitment($crossMatchProduct, $brand, $actyon, '2.0 T', 2007, 2011);
        $this->fitment($crossMatchProduct, $brand, $rexton, '2.3', 2012, 2018);

        $this->get(route('shop.index', [
            'brand' => 'KGM',
            'model' => 'Actyon',
            'engine' => '2.3',
        ]))
            ->assertOk()
            ->assertSee('Correct Actyon Part')
            ->assertDontSee('Wrong Cross Match Part');
    }

    public function test_selected_year_only_returns_fitments_covering_that_year(): void
    {
        [$brand, $actyon] = $this->vehicle('KGM', 'Actyon', 2007, 2015, ['2.0 T']);
        $olderProduct = Product::factory()->create(['name_en' => 'Actyon 2009 Part']);
        $newerProduct = Product::factory()->create(['name_en' => 'Actyon 2014 Part']);

        $this->fitment($olderProduct, $brand, $actyon, '2.0 T', 2007, 2011);
        $this->fitment($newerProduct, $brand, $actyon, '2.0 T', 2012, 2015);

        $this->get(route('shop.index', [
            'brand' => 'KGM',
            'model' => 'Actyon',
            'year' => '2009',
        ]))
            ->assertOk()
            ->assertSee('Actyon 2009 Part')
            ->assertDontSee('Actyon 2014 Part');
    }

    /** @return array{VehicleBrand, VehicleModel} */
    private function vehicle(
        string $brandName,
        string $modelName,
        int $yearFrom,
        int $yearTo,
        array $engines,
        ?VehicleBrand $brand = null,
    ): array {
        $brand ??= VehicleBrand::query()->create([
            'name' => $brandName,
            'slug' => str($brandName)->slug(),
        ]);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => $modelName,
            'slug' => str($modelName)->slug(),
            'production_start_year' => $yearFrom,
            'production_end_year' => $yearTo,
        ]);

        foreach ($engines as $engine) {
            VehicleModelEngineType::query()->create([
                'vehicle_model_id' => $model->id,
                'name' => $engine,
            ]);
        }

        return [$brand, $model];
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
