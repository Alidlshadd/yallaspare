<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFitmentDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create([
            'id' => 1,
            'name_en' => 'Suspension & Steering',
            'slug' => 'suspension-steering',
        ]);
    }

    public function test_product_detail_displays_all_structured_vehicle_fitments(): void
    {
        $product = Product::factory()->create([
            'name_en' => 'SsangYong Front Wheel Hub Assembly',
            'compatible_models' => null,
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'SSANGYONG / KGM', 'slug' => 'ssangyong-kgm']);

        $this->fitment($product, $brand, 'Rexton', 2007, 2017, '3.2');
        $this->fitment($product, $brand, 'Korando Sport');
        $this->fitment($product, $brand, 'Actyon Sport');
        $this->fitment($product, $brand, 'Actyon');

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('SSANGYONG / KGM / Rexton')
            ->assertSeeText('SSANGYONG / KGM / Korando Sport')
            ->assertSeeText('SSANGYONG / KGM / Actyon Sport')
            ->assertSeeText('SSANGYONG / KGM / Actyon')
            ->assertSeeText('2007–2017')
            ->assertSeeText('3.2')
            ->assertSeeText('Any year')
            ->assertSeeText('Any engine')
            ->assertDontSeeText('Compatibility details are available on request.');
    }

    public function test_legacy_compatible_models_remain_visible_without_structured_fitments(): void
    {
        $product = Product::factory()->create([
            'compatible_models' => ['Legacy Model A', 'Legacy Model B'],
        ]);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Legacy Model A')
            ->assertSeeText('Legacy Model B');
    }

    private function fitment(
        Product $product,
        VehicleBrand $brand,
        string $modelName,
        ?int $yearFrom = null,
        ?int $yearTo = null,
        ?string $engine = null,
    ): void {
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => $modelName,
            'slug' => str($modelName)->slug(),
        ]);

        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'year_from' => $yearFrom,
            'year_to' => $yearTo,
            'engine' => $engine,
        ]);
    }
}
