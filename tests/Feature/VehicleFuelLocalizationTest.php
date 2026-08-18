<?php

namespace Tests\Feature;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use App\Support\VehicleLocalization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleFuelLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_text_engines_translate_every_fuel_word(): void
    {
        $this->assertSame('2.2 ديزل', VehicleLocalization::engine('2.2 Diesel', 'ar'));
        $this->assertSame('1.6 هجين', VehicleLocalization::engine('1.6 Hybrid', 'ar'));
        $this->assertSame('كهربائي', VehicleLocalization::engine('Electric', 'ar'));
        $this->assertSame('2.0 تۆربۆ بەنزین', VehicleLocalization::engine('2.0 Turbo Petrol', 'ku'));
    }

    public function test_english_free_text_engines_are_left_alone(): void
    {
        $this->assertSame('2.2 Diesel', VehicleLocalization::engine('2.2 Diesel', 'en'));
        $this->assertSame('e-POWER', VehicleLocalization::engine('e-POWER', 'en'));
    }

    public function test_a_structured_engine_uses_its_fuel_type_over_the_stored_text(): void
    {
        $engine = $this->variant()->engineTypes()->create([
            // Stored text stays English; the label follows the locale.
            'name' => '2.0 Turbo Petrol',
            'fuel_type' => 'petrol',
            'engine_size' => 2.0,
            'aspiration' => 'turbo',
        ]);

        $this->assertSame('2 Turbo Petrol', $engine->localizedName('en'));
        $this->assertSame('2 تيربو بنزين', $engine->localizedName('ar'));
        $this->assertSame('بنزين', $engine->localizedFuelLabel('ar'));
    }

    public function test_a_legacy_engine_without_a_fuel_type_still_renders(): void
    {
        $engine = $this->variant()->engineTypes()->create(['name' => 'e-POWER']);

        $this->assertSame('e-POWER', $engine->localizedName('en'));
        $this->assertSame('', $engine->localizedFuelLabel('ar'));
    }

    public function test_the_api_reports_canonical_values_beside_localized_labels(): void
    {
        $variant = $this->variant();
        $variant->engineTypes()->create(['name' => '2.2 Diesel', 'fuel_type' => 'diesel', 'engine_size' => 2.2]);
        $variant->engineTypes()->create(['name' => 'Electric', 'fuel_type' => 'electric']);

        $response = $this->withHeader('Accept-Language', 'ar')
            ->getJson('/api/mobile/vehicle-fitments')
            ->assertOk();

        $engines = $response->json('data.0.families.0.variants.0.engine_details');

        $this->assertSame('diesel', $engines[0]['fuel_type']);
        $this->assertSame('2.2 Diesel', $engines[0]['value']);
        $this->assertSame('ديزل', $engines[0]['fuel_label']);
        $this->assertSame(2.2, $engines[0]['engine_size']);

        $this->assertSame('electric', $engines[1]['fuel_type']);
        $this->assertNull($engines[1]['engine_size']);

        // The fields shipped clients already read must keep their shape.
        $this->assertSame(['2.2 Diesel', 'Electric'], $response->json('data.0.families.0.variants.0.engine_values'));
    }

    public function test_the_shop_engine_filter_offers_a_localized_label_per_canonical_value(): void
    {
        $variant = $this->variant();
        $variant->engineTypes()->create(['name' => '2.2 Diesel', 'fuel_type' => 'diesel', 'engine_size' => 2.2]);

        $this->app->setLocale('ar');
        $response = $this->get(route('shop.index'))->assertOk();

        $map = $this->vehicleOptionMap($response->getContent());
        $engines = $map['SSANGYONG / KGM']['Rexton W']['engines'];

        $this->assertSame('2.2 Diesel', $engines[0]['value']);
        $this->assertSame('2.2 ديزل', $engines[0]['label']);
    }

    public function test_a_diesel_search_is_no_longer_forced_to_return_nothing(): void
    {
        $variant = $this->variant();
        $variant->engineTypes()->create(['name' => '2.2 Diesel', 'fuel_type' => 'diesel', 'engine_size' => 2.2]);

        $this->get(route('shop.index', ['engine' => '2.2 Diesel']))
            ->assertOk()
            ->assertDontSeeText('Diesel engines are not supported.');
    }

    /** @return array<string, mixed> */
    private function vehicleOptionMap(string $html): array
    {
        $matched = preg_match("/data-vehicle-option-map='([^']*)'/", $html, $match);
        $this->assertSame(1, $matched, 'The shop page did not render a vehicle option map.');

        return json_decode(html_entity_decode($match[1], ENT_QUOTES), true, 512, JSON_THROW_ON_ERROR);
    }

    private function variant(): VehicleModel
    {
        $brand = VehicleBrand::query()->create(['name' => 'SSANGYONG / KGM', 'slug' => 'ssangyong-kgm']);
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Rexton',
            'slug' => 'rexton',
        ]);

        return VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'Rexton W',
            'name_en' => 'Rexton W',
            'slug' => 'rexton-w',
        ]);
    }
}
