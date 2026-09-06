<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Models\VehicleModelFamily;
use App\Support\Search\AutocompletePanel;
use App\Support\Search\MatchReason;
use App\Support\Search\SearchSuggestions;
use App\Support\VehicleFilterCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The suggestion panel, answered against a catalogue shaped like the real one.
 *
 * Two things are being defended here at once. The first is the panel itself:
 * the groups, the reasons, the "fits" line and the links. The second is what
 * the panel is built on — the year rule that was got wrong once already, and
 * the response keys a mobile build or a cached page may still be reading.
 */
class SearchAutocompletePanelTest extends TestCase
{
    use RefreshDatabase;

    private VehicleBrand $ssangYong;

    private VehicleModel $rexton;

    private VehicleModel $oldRexton;

    private VehicleModel $tivoli;

    protected function setUp(): void
    {
        parent::setUp();

        SearchSuggestions::flush();
        VehicleFilterCache::flush();

        Category::factory()->create([
            'id' => 1,
            'name_en' => 'Filters',
            'name_ar' => 'Filters',
            'name_ku' => 'Filters',
            'slug' => 'filters',
        ]);

        $this->ssangYong = VehicleBrand::query()->create(['name' => 'SsangYong', 'slug' => 'ssangyong']);

        $this->rexton = $this->variant('Rexton', 2022, 2026, [['2.0 Petrol', 'petrol', 2.0]]);
        $this->oldRexton = $this->variant('Rexton', 2013, 2017, [['2.0 Petrol', 'petrol', 2.0]]);
        $this->tivoli = $this->variant('Tivoli', 2015, 2019, [['1.6 Petrol', 'petrol', 1.6]]);

        $this->part('SsangYong Engine Oil Filter', $this->rexton, 'SY-1721840025', '1721840025');
        $this->part('SsangYong Engine Air Filter', $this->rexton, 'SY-2314034101', '2314034101');
        $this->part('Rexton Brake Pad Set', $this->oldRexton, 'SY-BRK-13', 'BRK13');
        $this->part('Tivoli Cabin Filter', $this->tivoli, 'SY-CAB-16', 'CAB16');
    }

    // ---------------------------------------------------------------- contract

    public function test_the_response_carries_every_structured_key(): void
    {
        $data = $this->panel('ssangyong rexton 2024');

        $this->assertArrayHasKey('query', $data);
        $this->assertArrayHasKey('interpreted', $data);
        $this->assertArrayHasKey('correction', $data);
        $this->assertArrayHasKey('groups', $data);
        $this->assertArrayHasKey('meta', $data);

        foreach (['brand', 'vehicle', 'variant', 'year', 'engine', 'fuel'] as $key) {
            $this->assertArrayHasKey($key, $data['interpreted']);
        }

        foreach (['total_products', 'view_all_url', 'has_exact_matches'] as $key) {
            $this->assertArrayHasKey($key, $data['meta']);
        }
    }

    public function test_every_group_is_present_even_when_it_has_nothing_in_it(): void
    {
        foreach (['ssangyong rexton 2024', 'zzzzqq', '1721840025'] as $query) {
            $groups = $this->panel($query)['groups'];

            foreach (['products', 'vehicles', 'brands', 'categories'] as $group) {
                $this->assertArrayHasKey($group, $groups, $query);
                $this->assertIsArray($groups[$group], $query);
            }
        }

        $this->assertSame([], $this->panel('zzzzqq')['groups']['products']);
        $this->assertSame([], $this->panel('zzzzqq')['groups']['vehicles']);
    }

    public function test_a_query_too_short_to_answer_still_returns_the_full_shape(): void
    {
        $data = $this->panel('a');

        $this->assertSame([], $data['products']);
        $this->assertNull($data['correction']);
        $this->assertNull($data['interpreted']['vehicle']);

        foreach (['products', 'vehicles', 'brands', 'categories'] as $group) {
            $this->assertSame([], $data['groups'][$group]);
        }
    }

    public function test_the_correction_is_null_when_the_catalogue_has_nothing_to_correct(): void
    {
        $this->assertNull($this->panel('rexton')['correction']);
        $this->assertNull($this->panel('1721840025')['correction']);
    }

    public function test_the_keys_the_old_panel_read_are_all_still_there(): void
    {
        $data = $this->panel('rexton');

        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('brands', $data);

        foreach (['id', 'label', 'sku', 'brand', 'price', 'price_formatted', 'stock_quantity', 'image_url', 'url'] as $key) {
            $this->assertArrayHasKey($key, $data['products'][0], $key);
        }
    }

    public function test_every_link_is_a_real_route_carrying_only_filters_that_have_a_value(): void
    {
        $data = $this->panel('ssangyong rexton 2024');

        $urls = array_merge(
            [$data['meta']['view_all_url']],
            array_column($data['groups']['products'], 'url'),
            array_column($data['groups']['vehicles'], 'url'),
            array_column($data['groups']['brands'], 'url'),
            array_filter([
                $data['interpreted']['brand']['url'] ?? null,
                $data['interpreted']['vehicle']['url'] ?? null,
            ]),
        );

        $this->assertNotEmpty($urls);

        foreach ($urls as $url) {
            $this->assertStringStartsWith(config('app.url'), $url, $url);
            $this->assertDoesNotMatchRegularExpression('/[?&][a-z_]+=(&|$)/', $url, 'empty parameter in '.$url);
        }

        // The vehicle rows filter by marque and variant id, not by re-running
        // the typed words through the search a second time.
        $vehicleUrl = $data['groups']['vehicles'][0]['url'];
        $this->assertStringContainsString('brand=SsangYong', urldecode($vehicleUrl));
        $this->assertStringContainsString('model='.$this->rexton->id, $vehicleUrl);
        $this->assertStringNotContainsString('search=', $vehicleUrl);
    }

    // ------------------------------------------------------ search behaviour

    public function test_an_oem_number_comes_back_first_and_says_why(): void
    {
        $rows = $this->panel('1721840025')['groups']['products'];

        $this->assertSame('SsangYong Engine Oil Filter', $rows[0]['name']);
        $this->assertSame(MatchReason::OEM_EXACT, $rows[0]['match']['type']);
        $this->assertSame('Exact OEM match', $rows[0]['match']['label']);
        $this->assertTrue($this->panel('1721840025')['meta']['has_exact_matches']);
    }

    public function test_a_sku_comes_back_first_and_says_why(): void
    {
        $rows = $this->panel('SY-1721840025')['groups']['products'];

        $this->assertSame('SsangYong Engine Oil Filter', $rows[0]['name']);
        $this->assertSame(MatchReason::SKU_EXACT, $rows[0]['match']['type']);
        $this->assertSame('Exact SKU match', $rows[0]['match']['label']);
    }

    public function test_a_marque_a_car_and_a_year_are_all_recognised(): void
    {
        $interpreted = $this->panel('ssangyong rexton 2024')['interpreted'];

        $this->assertSame('SsangYong', $interpreted['brand']['label']);
        $this->assertSame('Rexton', $interpreted['vehicle']['label']);
        $this->assertSame(2024, $interpreted['year']);
        $this->assertNull($interpreted['fuel']);

        // Only the generation built in 2024 is offered.
        $variantIds = array_column($this->panel('ssangyong rexton 2024')['groups']['vehicles'], 'variant_id');
        $this->assertContains($this->rexton->id, $variantIds);
        $this->assertNotContains($this->oldRexton->id, $variantIds);
    }

    public function test_the_marque_under_its_other_name_is_recognised_too(): void
    {
        $interpreted = $this->panel('kgm rexton')['interpreted'];

        $this->assertSame('SsangYong', $interpreted['brand']['label']);
        $this->assertSame('Rexton', $interpreted['vehicle']['label']);
    }

    public function test_an_engine_size_and_a_fuel_are_recognised(): void
    {
        $interpreted = $this->panel('tivoli 1.6 petrol')['interpreted'];

        $this->assertSame('Tivoli', $interpreted['vehicle']['label']);
        $this->assertSame('1.6', $interpreted['engine']);
        $this->assertSame('Petrol', $interpreted['fuel']);
    }

    public function test_a_word_of_letters_is_never_treated_as_a_part_number(): void
    {
        $rows = $this->panel('rexton')['groups']['products'];

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $this->assertNotContains($row['match']['type'], [
                MatchReason::SKU_EXACT,
                MatchReason::OEM_EXACT,
                MatchReason::PART_NUMBER_EXACT,
                MatchReason::SKU_PREFIX,
                MatchReason::OEM_PREFIX,
            ], $row['name']);
        }
    }

    public function test_a_product_fitting_several_cars_is_returned_once(): void
    {
        $product = Product::query()->where('name_en', 'SsangYong Engine Oil Filter')->firstOrFail();

        // The same part, recorded against a second Rexton generation.
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $this->ssangYong->id,
            'vehicle_model_id' => $this->oldRexton->id,
        ]);

        $names = array_column($this->panel('rexton')['groups']['products'], 'name');

        $this->assertSame(count($names), count(array_unique($names)));
        $this->assertContains('SsangYong Engine Oil Filter', $names);
    }

    public function test_a_hidden_product_never_appears(): void
    {
        Product::query()->where('name_en', 'SsangYong Engine Oil Filter')->update(['is_active' => false]);

        $data = $this->panel('oil filter');

        $this->assertNotContains('SsangYong Engine Oil Filter', array_column($data['groups']['products'], 'name'));
        $this->assertNotContains('SsangYong Engine Oil Filter', array_column($data['products'], 'label'));
    }

    public function test_the_group_limits_hold(): void
    {
        foreach (range(1, 9) as $index) {
            $this->part('Rexton Spare Number '.$index, $this->rexton, 'SY-SPARE-'.$index, 'SPARE'.$index);
        }

        $groups = $this->panel('rexton')['groups'];

        $this->assertLessThanOrEqual(AutocompletePanel::PRODUCT_LIMIT, count($groups['products']));
        $this->assertLessThanOrEqual(AutocompletePanel::VEHICLE_LIMIT, count($groups['vehicles']));
        $this->assertLessThanOrEqual(AutocompletePanel::BRAND_LIMIT, count($groups['brands']));
        $this->assertLessThanOrEqual(AutocompletePanel::CATEGORY_LIMIT, count($groups['categories']));

        // And it says nothing it did not count. There are more products behind
        // the five, and the page declines to run the whole search a second time
        // just to put a number on them.
        $meta = $this->panel('rexton')['meta'];
        $this->assertArrayHasKey('total_products', $meta);
        $this->assertNull($meta['total_products']);
    }

    public function test_a_short_result_set_is_counted_exactly(): void
    {
        // Few enough to fit inside one page, so the count is free and honest.
        $meta = $this->panel('tivoli cabin')['meta'];

        $this->assertSame(1, $meta['total_products']);
    }

    // ------------------------------------------------------------ stock privacy

    public function test_the_panel_never_publishes_how_many_are_on_the_shelf(): void
    {
        Product::query()->where('name_en', 'SsangYong Engine Oil Filter')->update([
            'stock_quantity' => 137,
            'low_stock_threshold' => 9,
        ]);

        $data = $this->panel('ssangyong filter');
        $row = collect($data['groups']['products'])->firstWhere('name', 'SsangYong Engine Oil Filter');

        $this->assertSame(['state', 'label'], array_keys($row['stock']));
        $this->assertArrayNotHasKey('quantity', $row['stock']);
        $this->assertSame('in_stock', $row['stock']['state']);

        // Not under another name, and not in the group rows at all: neither the
        // level nor the threshold that would let it be inferred.
        $encoded = json_encode($data['groups'], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('137', $encoded);
        $this->assertStringNotContainsString('low_stock_threshold', $encoded);
        $this->assertStringNotContainsString('"quantity"', $encoded);
    }

    public function test_the_stock_state_reads_off_the_low_stock_threshold(): void
    {
        Product::query()->where('name_en', 'SsangYong Engine Oil Filter')->update([
            'stock_quantity' => 2,
            'low_stock_threshold' => 5,
        ]);
        Product::query()->where('name_en', 'SsangYong Engine Air Filter')->update([
            'stock_quantity' => 0,
        ]);

        $rows = collect($this->panel('ssangyong filter')['groups']['products'])->keyBy('name');

        $this->assertSame('low_stock', $rows['SsangYong Engine Oil Filter']['stock']['state']);
        $this->assertSame('Low stock', $rows['SsangYong Engine Oil Filter']['stock']['label']);
        $this->assertSame('out_of_stock', $rows['SsangYong Engine Air Filter']['stock']['state']);
    }

    // --------------------------------------------------- naming one car, or none

    public function test_a_bare_model_name_with_several_generations_claims_no_variant(): void
    {
        // Two Rextons on record and nothing in the query to choose between
        // them. Picking one would quietly hide the other generation's parts.
        $interpreted = $this->panel('rexton oil filter')['interpreted'];

        $this->assertSame('Rexton', $interpreted['vehicle']['label']);
        $this->assertNull($interpreted['variant']);
    }

    public function test_a_year_that_leaves_one_generation_standing_names_it(): void
    {
        $interpreted = $this->panel('rexton 2024')['interpreted'];

        $this->assertNotNull($interpreted['variant']);
        $this->assertSame($this->rexton->id, $interpreted['variant']['id']);
        $this->assertStringContainsString('model='.$this->rexton->id, $interpreted['variant']['url']);
    }

    public function test_a_year_two_generations_both_cover_claims_neither(): void
    {
        // A second car built across the same years as the first. 2024 no longer
        // separates them, so nothing may be claimed.
        $this->variant('Rexton', 2022, 2026, [['2.0 Petrol', 'petrol', 2.0]]);

        $interpreted = $this->panel('rexton 2024')['interpreted'];

        $this->assertNotNull($interpreted['vehicle']);
        $this->assertNull($interpreted['variant'], 'two cars answer 2024, so neither is the answer');
    }

    public function test_a_fuel_that_leaves_one_generation_standing_names_it(): void
    {
        $diesel = $this->variant('Musso', 2018, 2024, [['2.2 Diesel', 'diesel', 2.2]]);
        $petrol = $this->variant('Musso', 2015, 2017, [['2.0 Petrol', 'petrol', 2.0]]);

        $this->assertNull($this->panel('musso')['interpreted']['variant'], 'two Mussos, no reason to pick one');

        $interpreted = $this->panel('musso petrol')['interpreted'];
        $this->assertSame($petrol->id, $interpreted['variant']['id']);
        $this->assertNotSame($diesel->id, $interpreted['variant']['id']);
    }

    public function test_the_generic_vehicle_link_covers_the_family_rather_than_one_generation(): void
    {
        $url = $this->panel('rexton oil filter')['interpreted']['vehicle']['url'];

        // Several generations answer, so the link browses the family by name.
        // `model=<id>` here would be three quarters of the family thrown away.
        $this->assertStringNotContainsString('model='.$this->rexton->id, $url);
        $this->assertStringNotContainsString('model='.$this->oldRexton->id, $url);
        $this->assertStringContainsString('search=Rexton', urldecode($url));

        // And when only one car answers, the link is the exact filter.
        $exact = $this->panel('tivoli')['interpreted']['vehicle']['url'];
        $this->assertStringContainsString('model='.$this->tivoli->id, $exact);
    }

    public function test_view_all_never_narrows_to_a_variant(): void
    {
        foreach (['rexton oil filter', 'rexton 2024', 'tivoli'] as $query) {
            $url = $this->panel($query)['meta']['view_all_url'];

            $this->assertStringNotContainsString('model=', $url, $query);
            $this->assertStringContainsString('search=', $url, $query);
        }
    }

    public function test_the_vehicle_and_the_variant_never_read_as_the_same_chip(): void
    {
        // The panel builds its chips from vehicle, year, brand, engine and fuel
        // — the variant is deliberately not among them. A "Variant: Tivoli"
        // beside a "Vehicle: Tivoli" would be the same word twice, and the
        // vehicle group below already lists the cars themselves.
        $interpreted = $this->panel('tivoli')['interpreted'];

        $this->assertNotNull($interpreted['vehicle']);
        $this->assertNotNull($interpreted['variant']);

        $chips = array_values(array_filter([
            $interpreted['vehicle']['label'] ?? null,
            $interpreted['year'],
            $interpreted['brand']['label'] ?? null,
            $interpreted['engine'],
            $interpreted['fuel'],
        ]));

        $this->assertSame($chips, array_values(array_unique($chips)));
        $this->assertNotContains($interpreted['variant']['label'], $chips);

        // The variant still carries the years, so that where it *is* shown it
        // can never be mistaken for the family it belongs to.
        $this->assertNotSame($interpreted['vehicle']['label'], $interpreted['variant']['label']);
    }

    public function test_vehicle_rows_are_told_apart_by_their_years_and_engines(): void
    {
        $rows = $this->panel('rexton')['groups']['vehicles'];

        $this->assertGreaterThan(1, count($rows));

        $signatures = array_map(
            static fn (array $row): string => $row['name'].'|'.$row['detail'],
            $rows
        );

        $this->assertSame($signatures, array_values(array_unique($signatures)));

        foreach ($rows as $row) {
            $this->assertNotSame('', $row['detail'], $row['name'].' has nothing to tell it apart');
            $this->assertStringContainsString('model='.$row['variant_id'], $row['url']);
        }
    }

    // ------------------------------------------------------------- year rules

    public function test_a_recorded_range_covers_its_ends_and_nothing_outside_them(): void
    {
        $product = $this->part('Rexton Ranged Part', $this->rexton, 'SY-RANGE-1', 'RANGE1', 2022, 2026);

        foreach ([2022, 2024, 2026] as $year) {
            $this->assertContains(
                'Rexton Ranged Part',
                array_column($this->panel('rexton '.$year)['groups']['products'], 'name'),
                'expected the part in '.$year
            );
        }

        foreach ([2021, 2027] as $year) {
            $this->assertNotContains(
                'Rexton Ranged Part',
                array_column($this->panel('rexton '.$year)['groups']['products'], 'name'),
                'did not expect the part in '.$year
            );
        }

        $this->assertNotNull($product->fresh());
    }

    public function test_an_open_ended_range_covers_every_later_year(): void
    {
        $this->part('Rexton Open Ended Part', $this->rexton, 'SY-OPEN-1', 'OPEN1', 2022, null);

        $this->assertContains(
            'Rexton Open Ended Part',
            array_column($this->panel('rexton 2024')['groups']['products'], 'name')
        );
    }

    public function test_a_row_with_no_years_falls_back_to_the_cars_own_build_span(): void
    {
        // 'Rexton Brake Pad Set' is recorded against the 2013-2017 car with no
        // years of its own, so the car's span is what answers.
        $this->assertContains(
            'Rexton Brake Pad Set',
            array_column($this->panel('rexton 2015')['groups']['products'], 'name')
        );

        $this->assertNotContains(
            'Rexton Brake Pad Set',
            array_column($this->panel('rexton 2024')['groups']['products'], 'name')
        );
    }

    public function test_the_rows_own_years_beat_the_cars_build_span(): void
    {
        // The car is built 2022-2026; this part was only ever made for 2022-2023.
        $this->part('Rexton Early Only Part', $this->rexton, 'SY-EARLY-1', 'EARLY1', 2022, 2023);

        $this->assertContains(
            'Rexton Early Only Part',
            array_column($this->panel('rexton 2023')['groups']['products'], 'name')
        );

        $this->assertNotContains(
            'Rexton Early Only Part',
            array_column($this->panel('rexton 2024')['groups']['products'], 'name'),
            'a 2022-2023 part must not answer 2024 just because the car is built that long'
        );
    }

    // --------------------------------------------------------- the fits line

    public function test_the_fits_line_names_the_car_the_query_pointed_at(): void
    {
        $rows = collect($this->panel('rexton 2024 oil filter')['groups']['products'])->keyBy('name');
        $row = $rows['SsangYong Engine Oil Filter'];

        // Half the query is the car and half is the part, and the caption says
        // so rather than crediting the car alone.
        $this->assertSame(MatchReason::VEHICLE_PART, $row['match']['type']);
        $this->assertSame('Vehicle and part match', $row['match']['label']);
        $this->assertStringContainsString('Rexton', $row['fits']['label']);
        $this->assertStringContainsString('2022', $row['fits']['label']);
    }

    public function test_a_query_that_is_only_a_car_is_still_a_plain_vehicle_match(): void
    {
        // Nothing in "rexton 2024" names a part, so there is no second half to
        // claim and the caption stays what it always was.
        $rows = collect($this->panel('rexton 2024')['groups']['products'])->keyBy('name');
        $row = $rows['SsangYong Engine Oil Filter'];

        $this->assertSame(MatchReason::FITMENT, $row['match']['type']);
        $this->assertSame('Compatible vehicle match', $row['match']['label']);
    }

    public function test_a_part_word_alone_never_claims_a_vehicle(): void
    {
        foreach ($this->panel('oil filter')['groups']['products'] as $row) {
            $this->assertNotSame(MatchReason::VEHICLE_PART, $row['match']['type'], $row['name']);
        }
    }

    public function test_an_exact_identifier_outranks_the_combined_caption(): void
    {
        // Even where the query would split into a car and a part, a number that
        // names one product exactly keeps its place at the top of the ladder.
        $row = $this->panel('SY-1721840025')['groups']['products'][0];

        $this->assertSame(MatchReason::SKU_EXACT, $row['match']['type']);
    }

    public function test_a_part_filed_under_a_named_category_counts_as_the_part_half(): void
    {
        // "rexton filters" names the shelf rather than the part, and a product
        // sitting on that shelf has had that word answered just as squarely as
        // one carrying it in its name.
        $rows = collect($this->panel('rexton filters')['groups']['products'])->keyBy('name');

        $this->assertArrayHasKey('Rexton Brake Pad Set', $rows);
        $this->assertSame(MatchReason::VEHICLE_PART, $rows['Rexton Brake Pad Set']['match']['type']);
    }

    public function test_a_row_the_searched_year_rules_out_is_never_used_as_the_caption(): void
    {
        $product = $this->part('Rexton Two Sided Part', $this->rexton, 'SY-TWO-1', 'TWO1', 2022, 2023);

        // The same part, for the older car, over a range that does cover 2015.
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $this->ssangYong->id,
            'vehicle_model_id' => $this->oldRexton->id,
            'year_from' => 2013,
            'year_to' => 2017,
        ]);

        $rows = collect($this->panel('rexton 2015')['groups']['products'])->keyBy('name');

        $this->assertArrayHasKey('Rexton Two Sided Part', $rows);
        $this->assertStringContainsString('2013', $rows['Rexton Two Sided Part']['fits']['label']);
        $this->assertStringNotContainsString('2022', $rows['Rexton Two Sided Part']['fits']['label']);
    }

    public function test_a_diesel_row_is_not_captioned_onto_a_petrol_search(): void
    {
        $diesel = $this->variant('Korando', 2019, 2024, [['1.6 Diesel', 'diesel', 1.6]]);
        $product = $this->part('Korando Fuel Filter', $diesel, 'SY-KOR-1', 'KOR1');

        $product->vehicleFitments()->update(['engine' => '1.6 Diesel']);

        $rows = collect($this->panel('korando petrol')['groups']['products'])->keyBy('name');

        if (isset($rows['Korando Fuel Filter'])) {
            $this->assertStringNotContainsStringIgnoringCase(
                'diesel',
                (string) ($rows['Korando Fuel Filter']['fits']['label'] ?? ''),
            );
        }

        // And the same part searched without a fuel does still say what it is.
        $plain = collect($this->panel('korando fuel filter')['groups']['products'])->keyBy('name');
        $this->assertStringContainsString('Korando', (string) $plain['Korando Fuel Filter']['fits']['label']);
    }

    public function test_a_part_on_many_cars_and_no_car_in_the_query_counts_rather_than_guesses(): void
    {
        $product = $this->part('Universal Wiper Blade', $this->rexton, 'SY-WIPE-1', 'WIPE1');

        foreach ([$this->oldRexton, $this->tivoli] as $model) {
            ProductVehicleFitment::query()->create([
                'product_id' => $product->id,
                'vehicle_brand_id' => $this->ssangYong->id,
                'vehicle_model_id' => $model->id,
            ]);
        }

        $rows = collect($this->panel('wiper blade')['groups']['products'])->keyBy('name');
        $row = $rows['Universal Wiper Blade'];

        $this->assertNull($row['fits']['label'], 'no car was named, so none should be claimed');
        $this->assertSame(3, $row['fits']['vehicle_count']);
    }

    // -------------------------------------------------------------- did-you-mean

    public function test_a_mistyped_model_is_offered_the_real_one(): void
    {
        $data = $this->panel('rextn');

        $this->assertNotNull($data['correction']);
        $this->assertSame('rextn', $data['correction']['from']);
        $this->assertSame('Rexton', $data['correction']['to']);
        $this->assertSame('Rexton', $data['correction']['suggested_query']);

        // And the correction stays out of the results: it is offered, not applied.
        $this->assertSame([], $data['groups']['products']);
    }

    public function test_no_correction_is_invented_for_a_word_the_catalogue_has_never_heard(): void
    {
        $this->assertNull($this->panel('zzzzqqqq')['correction']);
    }

    public function test_a_part_number_is_never_corrected_into_a_car_name(): void
    {
        // A digit out in an OEM number is a different part, not a slip of the
        // finger, and offering a car name in its place would be nonsense.
        foreach (['1721840024', '23140341', 'BRK14'] as $query) {
            $correction = $this->panel($query)['correction'];

            $this->assertNull($correction, $query.' should not be corrected');
        }
    }

    // --------------------------------------------------------- no-result help

    public function test_dropping_the_year_is_only_offered_when_a_year_was_typed(): void
    {
        $withYear = $this->panel('rexton 2024')['meta'];
        $this->assertSame('rexton', $withYear['without_year_query']);
        $this->assertStringContainsString('search=rexton', $withYear['without_year_url']);

        $withoutYear = $this->panel('rexton')['meta'];
        $this->assertNull($withoutYear['without_year_query']);
        $this->assertNull($withoutYear['without_year_url']);
    }

    // ------------------------------------------------------------ localisation

    public function test_labels_are_answered_in_the_locale_of_the_request(): void
    {
        $english = $this->panel('1721840025', 'en')['groups']['products'][0];
        $this->assertSame('Exact OEM match', $english['match']['label']);
        $this->assertSame('In stock', $english['stock']['label']);

        $arabic = $this->panel('1721840025', 'ar')['groups']['products'][0];
        $kurdish = $this->panel('1721840025', 'ku')['groups']['products'][0];

        $this->assertNotSame($english['match']['label'], $arabic['match']['label']);
        $this->assertNotSame($english['match']['label'], $kurdish['match']['label']);
        $this->assertNotSame($arabic['match']['label'], $kurdish['match']['label']);
    }

    public function test_one_locales_words_never_come_back_for_another(): void
    {
        // The suggestion dictionary is cached for ten minutes and holds names,
        // not sentences — so warming it in Arabic must not leave English
        // captions speaking Arabic on the next request.
        $arabic = $this->panel('1721840025', 'ar')['groups']['products'][0];
        $english = $this->panel('1721840025', 'en')['groups']['products'][0];

        $this->assertSame('Exact OEM match', $english['match']['label']);
        $this->assertSame('In stock', $english['stock']['label']);
        $this->assertNotSame($english['match']['label'], $arabic['match']['label']);
    }

    // ------------------------------------------------- engine-independent rules

    public function test_a_wildcard_typed_into_the_box_is_a_character_not_a_pattern(): void
    {
        // "%" and "_" mean something to LIKE and nothing to a shopper. Escaped
        // by SqlSafe with ESCAPE '!' — which SQLite and MySQL both honour, and
        // which is the difference between matching one product and every one.
        $this->part('Rexton 50% Coolant', $this->rexton, 'SY-COOL-1', 'COOL1');

        $wildcard = array_column($this->panel('50%')['groups']['products'], 'name');
        $this->assertSame(['Rexton 50% Coolant'], $wildcard);

        $this->assertSame([], $this->panel('zz_zz')['groups']['products']);
        $this->assertSame([], $this->panel('%%%%')['groups']['products']);
    }

    public function test_a_query_matches_whatever_case_it_was_typed_in(): void
    {
        foreach (['ssangyong rexton', 'SSANGYONG REXTON', 'SsAnGyOnG ReXtOn'] as $query) {
            $names = array_column($this->panel($query)['groups']['products'], 'name');

            $this->assertContains('SsangYong Engine Oil Filter', $names, $query);
        }

        // And the exact-identifier rung of the ladder is case-blind too, on an
        // engine whose `=` is case-sensitive as much as on one where it is not.
        foreach (['SY-1721840025', 'sy-1721840025'] as $query) {
            $row = $this->panel($query)['groups']['products'][0];

            $this->assertSame(MatchReason::SKU_EXACT, $row['match']['type'], $query);
        }
    }

    public function test_a_non_latin_name_is_found_and_returned_intact(): void
    {
        $product = Product::factory()->create([
            'name_en' => 'Rexton Arabic Cabin Filter',
            'name_ar' => 'فلتر مكيف ريكستون',
            'name_ku' => 'فلتەری کۆنتیشن ڕێکستۆن',
            'sku' => 'SY-AR-1',
            'oem_number' => 'AR1',
            'stock_quantity' => 4,
        ]);

        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $this->ssangYong->id,
            'vehicle_model_id' => $this->rexton->id,
        ]);

        $names = array_column($this->panel('مكيف', 'ar')['groups']['products'], 'name');

        $this->assertContains('فلتر مكيف ريكستون', $names);
    }

    // -------------------------------------------------------------- performance

    public function test_the_panel_is_answered_without_an_n_plus_one(): void
    {
        // Ten more parts on the same car, each with two fitment rows: if
        // anything here were per-row, the count would climb with the catalogue.
        foreach (range(1, 10) as $index) {
            $product = $this->part('Rexton Bulk Part '.$index, $this->rexton, 'SY-BULK-'.$index, 'BULK'.$index);

            ProductVehicleFitment::query()->create([
                'product_id' => $product->id,
                'vehicle_brand_id' => $this->ssangYong->id,
                'vehicle_model_id' => $this->tivoli->id,
            ]);
        }

        // One pass first, so what is being counted is the work of answering
        // rather than the schema lookups and the suggestion dictionary, both of
        // which are cached and happen once per process.
        $this->panel('rexton 2024');

        $small = $this->countQueries(fn () => $this->panel('rexton 2024'));

        foreach (range(11, 30) as $index) {
            $product = $this->part('Rexton Bulk Part '.$index, $this->rexton, 'SY-BULK-'.$index, 'BULK'.$index);

            ProductVehicleFitment::query()->create([
                'product_id' => $product->id,
                'vehicle_brand_id' => $this->ssangYong->id,
                'vehicle_model_id' => $this->tivoli->id,
            ]);
        }

        $large = $this->countQueries(fn () => $this->panel('rexton 2024'));

        $this->assertSame($small, $large, 'the query count must not follow the size of the catalogue');

        // A fixed ceiling, so a future eager load that quietly becomes a
        // per-row lookup is caught here rather than in production. Sixteen is
        // what the four groups, the interpretation and the fitment captions
        // cost today; raise it only with a reason worth writing down.
        $this->assertLessThanOrEqual(13, $large, 'query count regressed: '.$large);
    }

    public function test_the_response_stays_small_enough_to_type_against(): void
    {
        foreach (range(1, 20) as $index) {
            $this->part('Rexton Weight Part '.$index, $this->rexton, 'SY-WEIGHT-'.$index, 'WEIGHT'.$index);
        }

        $body = $this->get(route('shop.autocomplete', ['q' => 'rexton 2024']))->assertOk()->getContent();

        $this->assertLessThan(30 * 1024, strlen((string) $body), 'response grew past 30KB');
    }

    public function test_a_count_the_panel_cannot_afford_does_not_break_the_shape(): void
    {
        // A category with no products still has to answer with a number rather
        // than a missing key, and the panel must be free to hide it.
        Category::factory()->create([
            'name_en' => 'Filters Rare',
            'name_ar' => 'Filters Rare',
            'name_ku' => 'Filters Rare',
            'slug' => 'filters-rare',
        ]);

        foreach ($this->panel('filters')['groups']['categories'] as $row) {
            $this->assertArrayHasKey('product_count', $row);
            $this->assertTrue($row['product_count'] === null || is_int($row['product_count']));
        }
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @return array<string, mixed>
     */
    private function panel(string $query, string $locale = 'en'): array
    {
        $response = $this->withHeaders(['Accept-Language' => $locale])
            ->get(route('shop.autocomplete', ['q' => $query, 'lang' => $locale]));

        $response->assertOk();

        /** @var array<string, mixed> $data */
        $data = $response->json('data');

        return $data;
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    /**
     * @param  list<array{0: string, 1: string, 2: float}>  $engines
     */
    private function variant(string $name, int $from, int $to, array $engines): VehicleModel
    {
        $family = VehicleModelFamily::query()->firstOrCreate(
            ['vehicle_brand_id' => $this->ssangYong->id, 'slug' => strtolower($name)],
            ['name' => $name, 'name_en' => $name]
        );

        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $this->ssangYong->id,
            'vehicle_model_family_id' => $family->id,
            'name' => $name,
            'name_en' => $name,
            'slug' => Str::slug($name).'-'.$from.'-'.VehicleModel::query()->count(),
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

    private function part(
        string $name,
        VehicleModel $model,
        string $sku,
        string $oem,
        ?int $yearFrom = null,
        ?int $yearTo = null,
    ): Product {
        $product = Product::factory()->create([
            'name_en' => $name,
            'name_ar' => $name,
            'name_ku' => $name,
            'sku' => $sku,
            'oem_number' => $oem,
            'brand' => 'KGM',
            'stock_quantity' => 25,
        ]);

        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $this->ssangYong->id,
            'vehicle_model_id' => $model->id,
            'year_from' => $yearFrom,
            'year_to' => $yearTo,
        ]);

        return $product;
    }
}
