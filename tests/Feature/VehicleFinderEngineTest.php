<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use App\Support\VehicleFuelType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the vehicle finder offers, and what it does with the answer.
 *
 * A car is picked once and its engines follow from it. Diesel cars stay in the
 * catalogue but are not offered to a customer, because the shop sells petrol
 * parts — a rule read off the engine's fuel type, never off its name.
 */
class VehicleFinderEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create(['id' => 1, 'name_en' => 'Suspension', 'slug' => 'suspension']);
    }

    public function test_a_car_with_two_engines_is_offered_once(): void
    {
        $tivoli = $this->tivoliWithBothEngines();

        $html = (string) $this->get(route('shop.index'))->assertOk()->getContent();
        $options = $this->modelOptions($html);

        $this->assertSame(
            1,
            collect($options)->filter(fn (array $option) => str_contains($option['label'], 'Tivoli'))->count(),
            'The finder offers the same car more than once.'
        );
        $this->assertSame((string) $tivoli->id, $options[0]['value']);
        $this->assertStringContainsString('2015', $options[0]['label']);
    }

    public function test_every_engine_the_car_is_recorded_with_is_offered(): void
    {
        $tivoli = $this->tivoliWithBothEngines();

        $html = (string) $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertEqualsCanonicalizing(
            ['1.6 Petrol', '1.6 Turbo Diesel'],
            $this->enginesFor($html, $tivoli->id)
        );
    }

    public function test_the_storefront_fuel_setting_does_not_narrow_the_finder(): void
    {
        $tivoli = $this->tivoliWithBothEngines();

        // The setting still governs what the shop presents itself as selling
        // elsewhere. It is not what decides which car a shopper can say they
        // drive, so narrowing it changes nothing in this list.
        config(['vehicles.storefront_fuel_types' => [VehicleFuelType::PETROL]]);

        $html = (string) $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertEqualsCanonicalizing(
            ['1.6 Petrol', '1.6 Turbo Diesel'],
            $this->enginesFor($html, $tivoli->id)
        );
    }

    public function test_an_engine_with_no_fuel_type_is_still_offered(): void
    {
        $brand = $this->brand();
        $tivoli = $this->variant($brand, $this->family($brand, 'Tivoli'), 'Tivoli', 2015, 2019);
        // Entered before the structured columns existed. It cannot be ruled out.
        $tivoli->engineTypes()->create(['name' => '1.6 Something']);

        $html = (string) $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertSame(['1.6 Something'], $this->enginesFor($html, $tivoli->id));
    }

    public function test_choosing_an_engine_narrows_the_products(): void
    {
        $brand = $this->brand();
        $tivoli = $this->variant($brand, $this->family($brand, 'Tivoli'), 'Tivoli', 2015, 2019);
        $this->engine($tivoli, 'petrol', 1.6);
        $this->engine($tivoli, 'petrol', 1.5, 'turbo');

        $forSixteen = Product::factory()->create(['name_en' => 'Sixteen Only Filter', 'is_active' => true]);
        $forFifteen = Product::factory()->create(['name_en' => 'Fifteen Only Filter', 'is_active' => true]);

        $this->fit($forSixteen, $brand, $tivoli, '1.6 Petrol');
        $this->fit($forFifteen, $brand, $tivoli, '1.5 Turbo Petrol');

        $this->get(route('shop.index', ['vehicle_model' => $tivoli->id, 'engine' => '1.6 Petrol']))
            ->assertOk()
            ->assertSee('Sixteen Only Filter')
            ->assertDontSee('Fifteen Only Filter');
    }

    public function test_a_part_recorded_for_no_particular_engine_survives_the_narrowing(): void
    {
        $brand = $this->brand();
        $tivoli = $this->variant($brand, $this->family($brand, 'Tivoli'), 'Tivoli', 2015, 2019);
        $this->engine($tivoli, 'petrol', 1.6);

        $anyEngine = Product::factory()->create(['name_en' => 'Fits Every Tivoli', 'is_active' => true]);
        ProductVehicleFitment::query()->create([
            'product_id' => $anyEngine->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $tivoli->id,
        ]);

        $this->get(route('shop.index', ['vehicle_model' => $tivoli->id, 'engine' => '1.6 Petrol']))
            ->assertOk()
            ->assertSee('Fits Every Tivoli');
    }

    public function test_two_year_ranges_of_one_car_are_offered_separately(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');

        foreach ([['rexton-g4-2018', 2018, 2021], ['rexton-g4-2022', 2022, 2026]] as [$slug, $from, $to]) {
            $variant = $this->variant($brand, $family, 'Rexton G4', $from, $to, $slug);
            $this->engine($variant, 'petrol', 2.0, 'turbo');
        }

        $options = $this->modelOptions((string) $this->get(route('shop.index'))->assertOk()->getContent());
        $labels = collect($options)->pluck('label')->filter(fn ($label) => str_contains($label, 'Rexton G4'))->values();

        $this->assertCount(2, $labels);
        $this->assertNotSame($labels[0], $labels[1]);
    }

    public function test_each_variant_offers_only_its_own_engines(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Tivoli');

        $older = $this->variant($brand, $family, 'Tivoli', 2015, 2019, 'tivoli-2015');
        $this->engine($older, 'petrol', 1.6);
        $this->engine($older, 'diesel', 1.6, 'turbo');

        $newer = $this->variant($brand, $family, 'Tivoli', 2020, 2026, 'tivoli-2020');
        $this->engine($newer, 'petrol', 1.5, 'turbo');
        $this->engine($newer, 'petrol', 1.6);
        $this->engine($newer, 'diesel', 1.6, 'turbo');

        $html = (string) $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertSame(['1.6 Petrol', '1.6 Turbo Diesel'], $this->enginesFor($html, $older->id));
        $this->assertSame(
            ['1.5 Turbo Petrol', '1.6 Petrol', '1.6 Turbo Diesel'],
            $this->enginesFor($html, $newer->id),
            'The 2020 car was offered engines that belong to the 2015 one.'
        );
    }

    public function test_an_engine_named_by_several_fitments_is_offered_once(): void
    {
        $brand = $this->brand();
        $tivoli = $this->variant($brand, $this->family($brand, 'Tivoli'), 'Tivoli', 2015, 2019);
        $this->engine($tivoli, 'petrol', 1.6);

        // Two parts recorded against the engine the variant already has. The
        // dropdown lists an engine, not a fitment.
        foreach (['Filter', 'Belt'] as $name) {
            $this->fit(
                Product::factory()->create(['name_en' => $name, 'is_active' => true]),
                $brand,
                $tivoli,
                '1.6 Petrol'
            );
        }

        $html = (string) $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertSame(['1.6 Petrol'], $this->enginesFor($html, $tivoli->id));
    }

    private function tivoliWithBothEngines(): VehicleModel
    {
        $brand = $this->brand();
        $tivoli = $this->variant($brand, $this->family($brand, 'Tivoli'), 'Tivoli', 2015, 2019);
        $this->engine($tivoli, 'petrol', 1.6);
        $this->engine($tivoli, 'diesel', 1.6, 'turbo');

        return $tivoli;
    }

    /** @return array<int, array{value: string, label: string}> */
    private function modelOptions(string $html): array
    {
        preg_match('/data-model-map=\'(.*?)\'/s', $html, $match);
        $map = json_decode(html_entity_decode($match[1] ?? '{}', ENT_QUOTES), true) ?: [];

        return array_values(array_merge(...array_values($map) ?: [[]]));
    }

    /** @return array<int, string> */
    private function enginesFor(string $html, int $modelId): array
    {
        preg_match('/data-vehicle-option-map=\'(.*?)\'/s', $html, $match);
        $map = json_decode(html_entity_decode($match[1] ?? '{}', ENT_QUOTES), true) ?: [];

        foreach ($map as $models) {
            if (isset($models[(string) $modelId])) {
                return collect($models[(string) $modelId]['engines'] ?? [])
                    ->map(fn ($engine) => is_array($engine) ? (string) $engine['value'] : (string) $engine)
                    ->sort()
                    ->values()
                    ->all();
            }
        }

        return [];
    }

    private function brand(): VehicleBrand
    {
        return VehicleBrand::query()->firstOrCreate(['name' => 'SSANGYONG / KGM'], ['slug' => 'ssangyong-kgm']);
    }

    private function family(VehicleBrand $brand, string $name): VehicleModelFamily
    {
        return VehicleModelFamily::query()->firstOrCreate(
            ['vehicle_brand_id' => $brand->id, 'name' => $name],
            ['slug' => str($name)->slug()->value()],
        );
    }

    private function variant(
        VehicleBrand $brand,
        VehicleModelFamily $family,
        string $name,
        ?int $from = null,
        ?int $to = null,
        ?string $slug = null,
    ): VehicleModel {
        return VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => $name,
            'name_en' => $name,
            'slug' => $slug ?: str($name)->slug()->value(),
            'production_start_year' => $from,
            'production_end_year' => $to,
        ]);
    }

    private function engine(VehicleModel $variant, string $fuel, ?float $size, ?string $aspiration = null): void
    {
        $variant->engineTypes()->create([
            'name' => VehicleFuelType::displayName($fuel, $size, $aspiration, 'en'),
            'fuel_type' => $fuel,
            'engine_size' => $size,
            'aspiration' => $aspiration,
        ]);
    }

    private function fit(Product $product, VehicleBrand $brand, VehicleModel $variant, string $engine): void
    {
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $variant->id,
            'engine' => $engine,
        ]);
    }
}
