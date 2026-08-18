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

    public function test_every_compatible_model_is_listed_with_its_years(): void
    {
        $product = $this->hubWithFourFitments();

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Fits these vehicles')
            ->assertSeeText('Rexton')
            ->assertSeeText('Actyon')
            ->assertSeeText('Korando Sport')
            ->assertSeeText('Actyon Sport')
            ->assertSeeText('2007–2017')
            ->assertDontSeeText('Compatibility details are available on request.');
    }

    /**
     * The brand is stored as "SSANGYONG / KGM". Repeating it on every row and
     * joining it to the model with another slash is what made the old section
     * read as three levels of hierarchy.
     */
    public function test_the_brand_is_named_once_and_never_joined_to_the_model(): void
    {
        $product = $this->hubWithFourFitments();

        $response = $this->get(route('shop.show', $product))->assertOk();

        $response->assertSeeText('SSANGYONG / KGM');
        $response->assertDontSeeText('SSANGYONG / KGM / Rexton');
        $this->assertSame(1, substr_count($this->fitmentSection($response->getContent()), 'SSANGYONG / KGM'));
    }

    /**
     * A row with no year bounds covers the whole model. Saying "any year"
     * describes a gap in our data; "all years" describes the coverage.
     */
    public function test_an_unbounded_model_states_coverage_rather_than_a_missing_value(): void
    {
        $product = $this->hubWithFourFitments();

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('all years')
            ->assertDontSeeText('Any year')
            ->assertDontSeeText('Any engine');
    }

    public function test_the_model_count_is_shown(): void
    {
        $product = $this->hubWithFourFitments();

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('4 models');
    }

    /**
     * The same model appearing once per engine is one vehicle, not several, so
     * the rows collapse and the engines are counted instead.
     */
    public function test_rows_sharing_a_model_collapse_into_one_line(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = VehicleBrand::query()->create(['name' => 'SSANGYONG / KGM', 'slug' => 'ssangyong-kgm']);

        $rexton = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Rexton',
            'slug' => 'rexton',
        ]);

        foreach ([['3.2 Petrol', 2007, 2017], ['2.7 Diesel', 2009, 2015]] as [$engine, $from, $to]) {
            ProductVehicleFitment::query()->create([
                'product_id' => $product->id,
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_id' => $rexton->id,
                'year_from' => $from,
                'year_to' => $to,
                'engine' => $engine,
            ]);
        }

        $response = $this->get(route('shop.show', $product))->assertOk();
        $section = $this->fitmentSection($response->getContent());

        $this->assertSame(1, substr_count($section, 'Rexton'));
        // Widest span across both rows, and the engines counted rather than listed.
        $response->assertSeeText('2007–2017');
        $response->assertSeeText('1 model');
        $response->assertSeeText('2 configurations');
        $response->assertSeeText('2 engines');
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

    public function test_a_product_without_any_fitment_data_says_so(): void
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

        $this->fitment($product, $brand, 'Rexton', 2007, 2017, '3.2 Petrol');
        $this->fitment($product, $brand, 'Korando Sport');
        $this->fitment($product, $brand, 'Actyon Sport');
        $this->fitment($product, $brand, 'Actyon');

        return $product;
    }

    /**
     * The compatibility section only, so counting an occurrence cannot be
     * confused by the product title or breadcrumbs.
     */
    private function fitmentSection(string $html): string
    {
        $start = strpos($html, 'data-product-compatibility');
        if ($start === false) {
            return '';
        }

        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end === false ? null : $end - $start);
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
