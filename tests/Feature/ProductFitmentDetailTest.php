<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
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

    public function test_compatibility_is_grouped_by_family_and_lists_every_variant(): void
    {
        $product = $this->hubWithFourFitments();

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Fits these vehicles')
            ->assertSeeText('2 Families')
            ->assertSeeText('4 Variants')
            ->assertSeeText('Rexton II')
            ->assertSeeText('Actyon Sports')
            ->assertSeeText('2007–2017')
            ->assertDontSeeText('Compatibility details are available on request.');
    }

    public function test_brand_is_named_once_and_never_joined_to_variant(): void
    {
        $response = $this->get(route('shop.show', $this->hubWithFourFitments()))->assertOk();
        $section = $this->fitmentSection($response->getContent());

        $response->assertDontSeeText('SSANGYONG / KGM / Rexton');
        $this->assertSame(1, substr_count($section, 'SSANGYONG / KGM'));
    }

    public function test_unverified_year_bounds_are_not_invented(): void
    {
        $this->get(route('shop.show', $this->hubWithFourFitments()))
            ->assertOk()
            ->assertSeeText('Any year')
            ->assertDontSeeText('Any engine');
    }

    public function test_rows_for_one_variant_collapse_and_keep_petrol_engines(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = VehicleBrand::query()->create(['name' => 'SSANGYONG / KGM', 'slug' => 'ssangyong-kgm']);
        $family = VehicleModelFamily::query()->create(['vehicle_brand_id' => $brand->id, 'name' => 'Rexton', 'slug' => 'rexton']);
        $variant = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'Rexton W',
            'slug' => 'rexton-w',
        ]);

        foreach ([['3.2 Petrol', 2012, 2017], ['2.0 Turbo Petrol', 2014, 2016]] as [$engine, $from, $to]) {
            ProductVehicleFitment::query()->create([
                'product_id' => $product->id,
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_id' => $variant->id,
                'year_from' => $from,
                'year_to' => $to,
                'engine' => $engine,
            ]);
        }

        $response = $this->get(route('shop.show', $product))->assertOk();
        $section = $this->fitmentSection($response->getContent());

        $this->assertSame(1, substr_count($section, 'Rexton W'));
        $response->assertSeeText('2012–2017');
        $response->assertSeeText('3.2 Petrol · 2.0 Turbo Petrol');
    }

    public function test_legacy_compatible_models_remain_visible_without_structured_fitments(): void
    {
        $product = Product::factory()->create(['compatible_models' => ['Legacy Model A', 'Legacy Model B']]);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Legacy Model A')
            ->assertSeeText('Legacy Model B');
    }

    public function test_product_without_fitment_data_says_so(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Compatibility details are available on request.');
    }

    private function hubWithFourFitments(): Product
    {
        $product = Product::factory()->create([
            'name_en' => 'SsangYong Front Wheel Hub Assembly',
            'compatible_models' => null,
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'SSANGYONG / KGM', 'slug' => 'ssangyong-kgm']);

        $this->fitment($product, $brand, 'Rexton', 'Rexton', 2007, 2017, '3.2 Petrol');
        $this->fitment($product, $brand, 'Rexton', 'Rexton II');
        $this->fitment($product, $brand, 'Actyon', 'Actyon Sports');
        $this->fitment($product, $brand, 'Actyon', 'Actyon');

        return $product;
    }

    private function fitmentSection(string $html): string
    {
        $start = strpos($html, 'data-product-compatibility');
        $end = $start === false ? false : strpos($html, '</section>', $start);

        return $start === false ? '' : substr($html, $start, $end === false ? null : $end - $start);
    }

    private function fitment(
        Product $product,
        VehicleBrand $brand,
        string $familyName,
        string $variantName,
        ?int $yearFrom = null,
        ?int $yearTo = null,
        ?string $engine = null,
    ): void {
        $family = VehicleModelFamily::query()->firstOrCreate(
            ['vehicle_brand_id' => $brand->id, 'name' => $familyName],
            ['slug' => str($familyName)->slug()],
        );
        $variant = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => $variantName,
            'slug' => str($variantName)->slug(),
        ]);

        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $variant->id,
            'year_from' => $yearFrom,
            'year_to' => $yearTo,
            'engine' => $engine,
        ]);
    }
}
