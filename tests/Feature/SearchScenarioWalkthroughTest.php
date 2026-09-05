<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Models\VehicleModelFamily;
use App\Support\Search\SearchQuery;
use App\Support\Search\SearchSuggestions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The queries the shop was reported as failing, run end to end against a
 * catalogue shaped like the real one.
 */
class SearchScenarioWalkthroughTest extends TestCase
{
    use RefreshDatabase;

    private VehicleBrand $ssangYong;

    protected function setUp(): void
    {
        parent::setUp();

        SearchSuggestions::flush();

        Category::factory()->create([
            'id' => 1,
            'name_en' => 'Filters',
            'name_ar' => 'Filters',
            'name_ku' => 'Filters',
            'slug' => 'filters',
        ]);

        $this->ssangYong = VehicleBrand::query()->create(['name' => 'SsangYong', 'slug' => 'ssangyong']);

        $rexton = $this->variant('Rexton', 2022, 2026, [['2.0 Petrol', 'petrol', 2.0]]);
        $oldRexton = $this->variant('Rexton', 2013, 2017, [['2.0 Petrol', 'petrol', 2.0]]);
        $tivoli = $this->variant('Tivoli', 2015, 2019, [['1.6 Petrol', 'petrol', 1.6]]);

        $this->part('SsangYong Engine Oil Filter', $rexton, 'SY-1721840025', '1721840025');
        $this->part('SsangYong Engine Air Filter', $rexton, 'SY-2314034101', '2314034101');
        $this->part('Rexton Brake Pad Set', $oldRexton, 'SY-BRK-13', 'BRK13');
        $this->part('Tivoli Cabin Filter', $tivoli, 'SY-CAB-16', 'CAB16');
    }

    /**
     * @return array<string, array{0: string, 1: list<string>, 2: list<string>}>
     */
    public static function scenarios(): array
    {
        return [
            'brand and model' => [
                'ssangyong rexton',
                ['SsangYong Engine Oil Filter', 'SsangYong Engine Air Filter', 'Rexton Brake Pad Set'],
                ['Tivoli Cabin Filter'],
            ],
            'the marque under its other name' => [
                'kgm rexton',
                ['SsangYong Engine Oil Filter', 'Rexton Brake Pad Set'],
                ['Tivoli Cabin Filter'],
            ],
            'model and a year the newer car covers' => [
                'rexton 2024',
                ['SsangYong Engine Oil Filter', 'SsangYong Engine Air Filter'],
                ['Rexton Brake Pad Set', 'Tivoli Cabin Filter'],
            ],
            'model and part type' => [
                'rexton oil filter',
                ['SsangYong Engine Oil Filter'],
                ['Tivoli Cabin Filter', 'Rexton Brake Pad Set'],
            ],
            'model, engine size and fuel' => [
                'tivoli 1.6 petrol',
                ['Tivoli Cabin Filter'],
                ['SsangYong Engine Oil Filter', 'Rexton Brake Pad Set'],
            ],
            'an OEM number on its own' => [
                '1721840025',
                ['SsangYong Engine Oil Filter'],
                ['SsangYong Engine Air Filter', 'Tivoli Cabin Filter'],
            ],
            'a SKU typed without its dash' => [
                'SY1721840025',
                ['SsangYong Engine Oil Filter'],
                ['Tivoli Cabin Filter'],
            ],
            'everything at once' => [
                'ssangyong rexton 2024 oil filter',
                ['SsangYong Engine Oil Filter'],
                ['SsangYong Engine Air Filter', 'Rexton Brake Pad Set', 'Tivoli Cabin Filter'],
            ],
        ];
    }

    /**
     * @param  list<string>  $expected
     * @param  list<string>  $notExpected
     */
    #[DataProvider('scenarios')]
    public function test_a_shopper_query_finds_what_it_should(string $query, array $expected, array $notExpected): void
    {
        $response = $this->get(route('shop.index', ['search' => $query]))->assertOk();

        foreach ($expected as $name) {
            $response->assertSee($name);
        }

        foreach ($notExpected as $name) {
            $response->assertDontSee($name);
        }
    }

    public function test_a_typed_model_name_with_a_slip_is_offered_a_correction(): void
    {
        $response = $this->get(route('shop.index', ['search' => 'rextn']))->assertOk();

        $response->assertSee('Did you mean');
        $response->assertSee('Rexton');
        // And no products, because nothing actually matched.
        $response->assertDontSee('SsangYong Engine Oil Filter');

        $suggestion = SearchSuggestions::forQuery(SearchQuery::parse('rextn'));
        $this->assertNotNull($suggestion);

        // Following the offer finds the parts.
        $this->get(route('shop.index', ['search' => $suggestion['query']]))
            ->assertOk()
            ->assertSee('SsangYong Engine Oil Filter');
    }

    /**
     * @param  list<array{0: string, 1: string, 2: float}>  $engines
     */
    private function variant(string $name, int $from, int $to, array $engines): VehicleModel
    {
        $family = VehicleModelFamily::query()->firstOrCreate(
            ['vehicle_brand_id' => $this->ssangYong->id, 'slug' => strtolower($name)],
            ['name' => $name]
        );

        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $this->ssangYong->id,
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

        return $model;
    }

    private function part(string $name, VehicleModel $model, string $sku, string $oem): Product
    {
        $product = Product::factory()->create([
            'name_en' => $name,
            'name_ar' => $name,
            'name_ku' => $name,
            'sku' => $sku,
            'oem_number' => $oem,
            'brand' => 'KGM',
        ]);

        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $this->ssangYong->id,
            'vehicle_model_id' => $model->id,
        ]);

        return $product;
    }
}
