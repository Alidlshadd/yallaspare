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
 * The hero finder used bare <select> elements, so opening one handed the visitor
 * the operating system's own popup — white, browser-sized, drawn outside the
 * panel. These cover what the server has to put on the page for the listbox that
 * replaces it, and that the form still submits variant ids.
 */
class VehicleFinderDropdownTest extends TestCase
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

    public function test_every_finder_field_is_marked_for_enhancement(): void
    {
        $this->twoTivolis();

        $content = $this->get(route('user.shop.home'))->assertOk()->getContent();

        foreach (['data-vehicle-brand', 'data-vehicle-model', 'data-vehicle-engine', 'data-vehicle-year'] as $field) {
            $this->assertMatchesRegularExpression(
                '/<select[^>]*'.preg_quote($field, '/').'[^>]*data-fancy-select/s',
                $content,
                "The {$field} control is still a plain native select."
            );
        }
    }

    public function test_each_field_says_what_it_is_waiting_for(): void
    {
        $this->twoTivolis();

        $content = $this->get(route('user.shop.home'))->assertOk()->getContent();

        // Not four copies of the same sentence.
        $this->assertStringContainsString('Select brand', $content);
        $this->assertStringContainsString('Select a brand first', $content);
        $this->assertStringContainsString('Select a model first', $content);
        $this->assertStringNotContainsString('Select model first', $content);
    }

    public function test_the_two_tivolis_are_told_apart_by_their_years(): void
    {
        [$older, $newer] = $this->twoTivolis();

        $content = $this->get(route('user.shop.home'))->assertOk()->getContent();

        // The flat label the closed control shows carries the years.
        $this->assertStringContainsString($older->listLabel(), $content);
        $this->assertStringContainsString($newer->listLabel(), $content);
        $this->assertStringContainsString('2015–2019', $content);
        $this->assertStringContainsString('2020–2026', $content);
    }

    public function test_an_option_carries_its_two_lines_and_its_search_text(): void
    {
        [$older] = $this->twoTivolis();

        $content = $this->get(route('user.shop.home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/value="'.$older->id.'"\s+data-primary="Tivoli"\s+data-secondary="([^"]+)"\s+data-search="([^"]+)"/s',
            $content
        );

        preg_match('/value="'.$older->id.'"\s+data-primary="Tivoli"\s+data-secondary="([^"]+)"/s', $content, $matches);
        $secondary = html_entity_decode($matches[1], ENT_QUOTES);

        $this->assertStringContainsString('2015–2019', $secondary);
        $this->assertStringContainsString('1.6 Petrol', $secondary);
    }

    public function test_a_variant_with_several_engines_counts_the_rest(): void
    {
        [$older] = $this->twoTivolis();

        // A second storefront-offered engine on the older car.
        VehicleModelEngineType::query()->create([
            'vehicle_model_id' => $older->id,
            'name' => '1.8 Petrol',
            'fuel_type' => 'petrol',
            'engine_size' => 1.8,
        ]);

        $option = $older->fresh(['engineTypes'])->finderOption(
            $older->fresh(['engineTypes'])->engineTypes->filter(fn ($engine) => $engine->isOfferedInStorefront())->values()
        );

        $this->assertStringContainsString('2015–2019', $option['secondary']);
        $this->assertStringContainsString('+1 engine', $option['secondary']);
    }

    public function test_the_option_never_advertises_an_engine_the_storefront_hides(): void
    {
        [$older] = $this->twoTivolis();

        $option = $older->finderOption(
            $older->engineTypes->filter(fn ($engine) => $engine->isOfferedInStorefront())->values()
        );

        // The diesel row stays in the database; it is simply not offered here.
        $this->assertDatabaseHas('vehicle_model_engine_types', [
            'vehicle_model_id' => $older->id,
            'fuel_type' => 'diesel',
        ]);
        $this->assertStringNotContainsString('Diesel', $option['secondary']);
        $this->assertStringNotContainsString('diesel', $option['search']);
    }

    public function test_the_search_text_covers_the_name_the_years_and_the_engine(): void
    {
        [$older] = $this->twoTivolis();

        $option = $older->finderOption(
            $older->engineTypes->filter(fn ($engine) => $engine->isOfferedInStorefront())->values()
        );

        foreach (['tivoli', '2015', '2019', 'petrol', '1.6'] as $needle) {
            $this->assertStringContainsString($needle, $option['search'], "The filter cannot find '{$needle}'.");
        }
    }

    public function test_the_native_control_keeps_its_name_so_the_form_still_submits(): void
    {
        $this->twoTivolis();

        $content = $this->get(route('user.shop.home'))->assertOk()->getContent();

        foreach (['brand', 'model', 'engine', 'year'] as $name) {
            $this->assertMatchesRegularExpression(
                '/<select[^>]*name="'.$name.'"/',
                $content,
                "The {$name} field lost its name attribute."
            );
        }
    }

    public function test_submitting_a_variant_id_reaches_the_right_products(): void
    {
        [$older, $newer] = $this->twoTivolis();
        $brand = VehicleBrand::query()->where('slug', 'ssangyong')->firstOrFail();

        $olderPart = Product::factory()->create(['name_en' => 'Older Tivoli Oil Filter']);
        $newerPart = Product::factory()->create(['name_en' => 'Newer Tivoli Oil Filter']);

        $this->fitment($olderPart, $brand, $older, '1.6 Petrol', 2015, 2019);
        $this->fitment($newerPart, $brand, $newer, '1.5 Turbo Petrol', 2020, 2026);

        // Exactly what the enhanced control writes back into the select.
        $this->get(route('shop.index', ['brand' => 'SsangYong', 'model' => (string) $older->id]))
            ->assertOk()
            ->assertSee('Older Tivoli Oil Filter')
            ->assertDontSee('Newer Tivoli Oil Filter');
    }

    public function test_a_reloaded_page_shows_the_selection_from_the_query_string(): void
    {
        [, $newer] = $this->twoTivolis();

        $content = $this->get(route('shop.index', [
            'brand' => 'SsangYong',
            'model' => (string) $newer->id,
        ]))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<option[^>]*value="'.$newer->id.'"[^>]*selected/',
            $content,
            'The variant from the query string is not selected after a reload.'
        );
    }

    public function test_the_finder_does_not_read_engines_once_per_variant(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'SsangYong', 'slug' => 'ssangyong']);
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Tivoli',
            'slug' => 'tivoli-family',
        ]);

        for ($i = 0; $i < 12; $i++) {
            $model = VehicleModel::query()->create([
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_family_id' => $family->id,
                'name' => 'Tivoli',
                'name_en' => 'Tivoli',
                'slug' => 'tivoli-'.$i,
                'production_start_year' => 2000 + $i,
                'production_end_year' => 2003 + $i,
            ]);
            VehicleModelEngineType::query()->create([
                'vehicle_model_id' => $model->id,
                'name' => '1.6 Petrol',
                'fuel_type' => 'petrol',
                'engine_size' => 1.6,
            ]);
        }

        $perVariantReads = 0;
        DB::listen(function ($query) use (&$perVariantReads): void {
            if (str_contains($query->sql, 'vehicle_model_engine_types')
                && str_contains($query->sql, '"vehicle_model_id" = ')) {
                $perVariantReads++;
            }
        });

        $this->get(route('user.shop.home'))->assertOk();

        $this->assertSame(0, $perVariantReads, 'Engines were read one variant at a time while building the finder.');
    }

    /** @return array{VehicleModel, VehicleModel} */
    private function twoTivolis(): array
    {
        $brand = VehicleBrand::query()->firstOrCreate(
            ['slug' => 'ssangyong'],
            ['name' => 'SsangYong']
        );
        $family = VehicleModelFamily::query()->firstOrCreate(
            ['vehicle_brand_id' => $brand->id, 'slug' => 'tivoli-family'],
            ['name' => 'Tivoli']
        );

        $older = $this->variant($brand, $family, 2015, 2019, [
            ['1.6 Petrol', 'petrol', 1.6, null],
            ['1.6 Turbo Diesel', 'diesel', 1.6, 'turbo'],
        ]);

        $newer = $this->variant($brand, $family, 2020, 2026, [
            ['1.5 Turbo Petrol', 'petrol', 1.5, 'turbo'],
            ['1.6 Turbo Diesel', 'diesel', 1.6, 'turbo'],
        ]);

        return [$older, $newer];
    }

    /**
     * @param  list<array{0: string, 1: string, 2: float, 3: string|null}>  $engines
     */
    private function variant(
        VehicleBrand $brand,
        VehicleModelFamily $family,
        int $from,
        int $to,
        array $engines,
    ): VehicleModel {
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'Tivoli',
            'name_en' => 'Tivoli',
            'slug' => 'tivoli-'.$from.'-'.$to,
            'production_start_year' => $from,
            'production_end_year' => $to,
        ]);

        foreach ($engines as [$name, $fuel, $size, $aspiration]) {
            VehicleModelEngineType::query()->create([
                'vehicle_model_id' => $model->id,
                'name' => $name,
                'fuel_type' => $fuel,
                'engine_size' => $size,
                'aspiration' => $aspiration,
            ]);
        }

        return $model->fresh(['engineTypes']);
    }

    private function fitment(
        Product $product,
        VehicleBrand $brand,
        VehicleModel $model,
        string $engine,
        int $from,
        int $to,
    ): void {
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'engine' => $engine,
            'year_from' => $from,
            'year_to' => $to,
        ]);
    }
}
