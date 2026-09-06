<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Models\VehicleModelFamily;
use App\Support\VehicleFilterCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The order the finder lists cars in.
 *
 * Nothing ordered the variants at all, so the dropdown read in whatever order
 * the rows had been entered — Rexton G4, Tivoli, Kyron — and a shopper looking
 * for their car had to read the whole list to find out it was there. These fix
 * the order as alphabetical by the name actually on screen, and fix what that
 * has to leave alone: two variants sharing a name are still two options, still
 * carrying their own ids, and the newer car still comes after the older one.
 */
class VehicleFinderOrderingTest extends TestCase
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

    public function test_the_finder_lists_cars_alphabetically(): void
    {
        $this->catalogue();

        $names = $this->finderPrimaries();

        $this->assertSame(['Kyron', 'Rexton G4', 'Tivoli', 'Tivoli'], $names);
    }

    public function test_the_shop_page_lists_them_in_the_same_order(): void
    {
        $this->catalogue();

        $content = $this->get(route('user.shop.index'))->assertOk()->getContent();

        // The filter select prints one label per option rather than the two
        // lines the hero draws, so the years are read off the label itself.
        $this->assertSame(
            ['Kyron — 2005–2014', 'Rexton G4 — 2017–2023', 'Tivoli — 2015–2019', 'Tivoli — 2020–2026'],
            $this->modelFilterLabels($content),
            'The shop filter and the hero finder disagree about the order.'
        );
    }

    public function test_two_variants_of_one_car_stay_together_and_oldest_first(): void
    {
        [, $older, $newer] = $this->catalogue();

        $ids = $this->finderValues();
        $olderAt = array_search((string) $older->id, $ids, true);
        $newerAt = array_search((string) $newer->id, $ids, true);

        $this->assertNotFalse($olderAt);
        $this->assertNotFalse($newerAt);
        $this->assertSame(1, $newerAt - $olderAt, 'Another car was listed between the two Tivolis.');
        $this->assertLessThan($newerAt, $olderAt, 'The 2020 Tivoli was listed before the 2015 one.');
    }

    public function test_variants_sharing_a_name_are_never_merged(): void
    {
        [, $older, $newer] = $this->catalogue();

        $ids = $this->finderValues();

        $this->assertContains((string) $older->id, $ids);
        $this->assertContains((string) $newer->id, $ids);
        $this->assertSame(count($ids), count(array_unique($ids)), 'A variant was listed twice.');
        $this->assertCount(4, $ids, 'A variant went missing from the finder.');
    }

    public function test_each_option_still_carries_its_own_variant_id(): void
    {
        [, $older, $newer] = $this->catalogue();

        $content = $this->get(route('user.shop.home'))->assertOk()->getContent();

        foreach ([$older, $newer] as $variant) {
            $this->assertMatchesRegularExpression(
                '/value="'.$variant->id.'"[^>]*data-secondary="[^"]*'.preg_quote((string) $variant->production_start_year, '/').'/s',
                $content,
                "Variant {$variant->id} lost the years that tell it apart."
            );
        }
    }

    public function test_case_and_stray_spacing_do_not_decide_the_order(): void
    {
        [$brand] = $this->catalogue();

        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Musso',
            'slug' => 'musso-family',
        ]);

        // Entered by two different hands: lower case, and padded.
        $this->variant($brand, $family, ' musso ', 'musso-lower', 2018, 2024);

        $this->assertSame(['Kyron', 'musso', 'Rexton G4', 'Tivoli', 'Tivoli'], $this->finderPrimaries());
    }

    public function test_a_number_in_a_name_sorts_as_a_number(): void
    {
        [$brand] = $this->catalogue();

        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Rexton',
            'slug' => 'rexton-g10-family',
        ]);

        $this->variant($brand, $family, 'Rexton G10', 'rexton-g10', 2021, 2026);

        $names = $this->finderPrimaries();

        $this->assertSame(
            array_search('Rexton G4', $names, true) + 1,
            array_search('Rexton G10', $names, true),
            'G10 was ordered by its first digit rather than as ten.'
        );
    }

    public function test_the_arabic_list_is_ordered_by_the_arabic_names(): void
    {
        [$brand] = $this->catalogue();

        // Arabic names deliberately in the opposite order to the English ones.
        VehicleModel::query()->where('name_en', 'Kyron')->update(['name_ar' => 'كيرون']);
        VehicleModel::query()->where('name_en', 'Rexton G4')->update(['name_ar' => 'ريكستون']);
        VehicleModel::query()->where('name_en', 'Tivoli')->update(['name_ar' => 'تيفولي']);
        VehicleFilterCache::flush();

        $this->app->setLocale('ar');

        $names = $this->finderPrimaries();

        $this->assertSame(['تيفولي', 'تيفولي', 'ريكستون', 'كيرون'], $names);
        $this->assertSame($brand->name, VehicleBrand::query()->find($brand->id)?->name);
    }

    public function test_each_tivoli_names_every_engine_it_is_recorded_with(): void
    {
        $this->catalogue();

        $engines = $this->tivoliEngineLines();

        $this->assertSame('1.6 Petrol · 1.6 Turbo Diesel', $engines['2015–2019'] ?? null);
        $this->assertSame('1.5 Turbo Petrol · 1.6 Petrol · 1.6 Turbo Diesel', $engines['2020–2026'] ?? null);
    }

    public function test_no_engine_is_ever_replaced_by_a_count(): void
    {
        $this->catalogue();

        $content = $this->get(route('user.shop.home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('+1 engine', $content);
        $this->assertStringNotContainsString('+2 engines', $content);
    }

    public function test_a_diesel_engine_is_offered_and_kept_on_record(): void
    {
        $this->catalogue();

        $engines = $this->tivoliEngineLines();

        $this->assertStringContainsString('1.6 Turbo Diesel', $engines['2015–2019'] ?? '');
        $this->assertDatabaseHas('vehicle_model_engine_types', ['fuel_type' => 'diesel']);
    }

    public function test_a_catalogue_past_thirty_variants_still_lists_every_one(): void
    {
        [$brand, , $newer] = $this->catalogue();

        // Enough cars that the old thirty-row cap would cut the alphabet off
        // partway — and the letters that get cut are the late ones, which is
        // where Tivoli lives.
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Filler',
            'slug' => 'filler-family',
        ]);

        foreach (range(1, 34) as $index) {
            $this->variant($brand, $family, 'Car '.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'filler-'.$index, 2000, 2010);
        }

        VehicleFilterCache::flush();

        $content = $this->get(route('user.shop.index', ['brand' => $brand->name, 'model' => $newer->id]))
            ->assertOk()
            ->getContent();

        $labels = $this->modelFilterLabels($content);

        $this->assertCount(38, $labels, 'The model filter dropped variants it should have listed.');
        $this->assertContains('Tivoli — 2020–2026', $labels);
        // The filter reads its current value off the options: a variant that is
        // not listed cannot be shown as selected, and the next submit silently
        // drops the model from the URL.
        $this->assertMatchesRegularExpression(
            '/<option value="'.$newer->id.'"[^>]*selected/',
            $content,
            'The model in the URL is not selected in the filter.'
        );
    }

    /**
     * Three cars, one of them recorded twice, entered in an order no shopper
     * would guess: the finder has to put them right on its own.
     *
     * @return array{VehicleBrand, VehicleModel, VehicleModel}
     */
    private function catalogue(): array
    {
        $brand = VehicleBrand::query()->create(['name' => 'SsangYong', 'slug' => 'ssangyong']);

        $rextonFamily = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Rexton',
            'slug' => 'rexton-family',
        ]);
        $tivoliFamily = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Tivoli',
            'slug' => 'tivoli-family',
        ]);
        $kyronFamily = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Kyron',
            'slug' => 'kyron-family',
        ]);

        $this->variant($brand, $rextonFamily, 'Rexton G4', 'rexton-g4', 2017, 2023);

        $older = $this->variant($brand, $tivoliFamily, 'Tivoli', 'tivoli-2015', 2015, 2019, [
            ['1.6 Petrol', 'petrol', 1.6, null],
            ['1.6 Turbo Diesel', 'diesel', 1.6, 'turbo'],
        ]);
        $newer = $this->variant($brand, $tivoliFamily, 'Tivoli', 'tivoli-2020', 2020, 2026, [
            ['1.5 Turbo Petrol', 'petrol', 1.5, 'turbo'],
            ['1.6 Petrol', 'petrol', 1.6, null],
            ['1.6 Turbo Diesel', 'diesel', 1.6, 'turbo'],
        ]);

        $this->variant($brand, $kyronFamily, 'Kyron', 'kyron', 2005, 2014);

        VehicleFilterCache::flush();

        return [$brand, $older, $newer];
    }

    /**
     * @param  list<array{0: string, 1: string, 2: float, 3: string|null}>  $engines
     */
    private function variant(
        VehicleBrand $brand,
        VehicleModelFamily $family,
        string $name,
        string $slug,
        int $from,
        int $to,
        array $engines = [],
    ): VehicleModel {
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => $name,
            'name_en' => $name,
            'slug' => $slug,
            'production_start_year' => $from,
            'production_end_year' => $to,
        ]);

        foreach ($engines as [$engineName, $fuel, $size, $aspiration]) {
            VehicleModelEngineType::query()->create([
                'vehicle_model_id' => $model->id,
                'name' => $engineName,
                'fuel_type' => $fuel,
                'engine_size' => $size,
                'aspiration' => $aspiration,
            ]);
        }

        return $model->fresh(['engineTypes']);
    }

    /** @return list<string> */
    private function finderPrimaries(): array
    {
        return $this->primariesIn($this->get(route('user.shop.home'))->assertOk()->getContent());
    }

    /** @return list<string> */
    private function primariesIn(string $content): array
    {
        preg_match_all('/data-primary="([^"]*)"/', $content, $matches);

        return array_map(fn (string $value) => trim(html_entity_decode($value, ENT_QUOTES)), $matches[1]);
    }

    /**
     * The engine line of each Tivoli option, keyed by the years above it.
     *
     * @return array<string, string>
     */
    private function tivoliEngineLines(): array
    {
        $content = $this->get(route('user.shop.home'))->assertOk()->getContent();
        preg_match_all(
            '/data-primary="Tivoli"\s+data-secondary="([^"]*)"\s+data-engines="([^"]*)"/s',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $lines = [];
        foreach ($matches as $match) {
            $lines[html_entity_decode($match[1], ENT_QUOTES)] = html_entity_decode($match[2], ENT_QUOTES);
        }

        return $lines;
    }

    /** @return list<string> */
    private function modelFilterLabels(string $content): array
    {
        preg_match('/<select[^>]*data-vehicle-model.*?<\/select>/s', $content, $select);
        preg_match_all('/<option[^>]*value="\d+"[^>]*>(.*?)<\/option>/s', $select[0] ?? '', $matches);

        return array_map(fn (string $value) => trim(html_entity_decode($value, ENT_QUOTES)), $matches[1]);
    }

    /** @return list<string> */
    private function finderValues(): array
    {
        $content = $this->get(route('user.shop.home'))->assertOk()->getContent();
        preg_match_all('/value="(\d+)"\s+data-primary="/', $content, $matches);

        return $matches[1];
    }
}
