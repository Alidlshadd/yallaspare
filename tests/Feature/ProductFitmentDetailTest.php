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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * What the product page says a part fits.
 *
 * The page used to read only the fitment row's own year and engine columns.
 * Those columns narrow a fit — an operator leaves them blank when the part
 * suits the whole variant — so a blank meant the page printed "Any year" over
 * a car whose build years had been recorded on the variant all along. Rows for
 * one variant were also folded together, which turned a 2018-2021 fit and a
 * 2022-2026 fit into a single 2018-2026 claim nobody had made.
 */
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

    public function test_the_variants_build_years_are_shown_when_the_fitment_does_not_narrow_them(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');
        $variant = $this->variant($brand, $family, 'Rexton G4', 2022, 2026);
        $this->engine($variant, 'petrol', 2.0, 'turbo');
        $this->fit($product, $brand, $variant, null, null, '2.0 Turbo Petrol');

        $response = $this->get(route('shop.show', $product))->assertOk();

        $response->assertSeeText('2022–2026');
        $this->assertStringNotContainsString('Any year', $this->section($response->getContent()));
    }

    public function test_a_fitment_that_narrows_the_years_wins_over_the_variant(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');
        $variant = $this->variant($brand, $family, 'Rexton G4', 2022, 2026);
        $this->fit($product, $brand, $variant, 2023, 2024);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('2023–2024');
    }

    public function test_two_year_ranges_of_one_variant_stay_apart(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');

        $newer = $this->variant($brand, $family, 'Rexton G4', 2022, 2026, 'rexton-g4-2022');
        $older = $this->variant($brand, $family, 'Rexton G4', 2018, 2021, 'rexton-g4-2018');
        foreach ([$newer, $older] as $variant) {
            $this->engine($variant, 'petrol', 2.0, 'turbo');
            $this->fit($product, $brand, $variant, null, null, '2.0 Turbo Petrol');
        }

        $response = $this->get(route('shop.show', $product))->assertOk();
        $section = $this->section($response->getContent());

        $response->assertSeeText('2022–2026');
        $response->assertSeeText('2018–2021');
        // The merged range nobody recorded.
        $this->assertStringNotContainsString('2018–2026', $section);
        $this->assertSame(2, substr_count($section, 'Rexton G4'));
    }

    public function test_one_car_with_two_engines_is_one_card_listing_both(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');
        $variant = $this->variant($brand, $family, 'Rexton W', 2013, 2017);
        $this->engine($variant, 'petrol', 3.2);
        $this->engine($variant, 'petrol', 2.0, 'turbo');

        $this->fit($product, $brand, $variant, null, null, '3.2 Petrol');
        $this->fit($product, $brand, $variant, null, null, '2.0 Turbo Petrol');

        $response = $this->get(route('shop.show', $product))->assertOk();
        $section = $this->section($response->getContent());

        // An engine is an option on a car, not another car.
        $this->assertSame(1, substr_count($section, 'Rexton W'));
        $this->assertSame(1, substr_count($section, '<li class="fitment-card'));
        $response->assertSeeText('3.2L');
        $response->assertSeeText('2.0L');
        $response->assertSeeText('Turbo');
        $response->assertSeeText('1 compatible configuration');
    }

    public function test_a_product_fitting_only_one_engine_does_not_list_the_other(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $variant = $this->variant($brand, $this->family($brand, 'Tivoli'), 'Tivoli', 2015, 2019);
        $this->engine($variant, 'petrol', 1.6);
        $this->engine($variant, 'diesel', 1.6, 'turbo');

        // The part was only ever recorded against the petrol car.
        $this->fit($product, $brand, $variant, null, null, '1.6 Petrol');

        $section = $this->section($this->pageIn('en', $product));

        $this->assertStringContainsString('1.6L', $section);
        $this->assertStringContainsString('Petrol', $section);
        $this->assertStringNotContainsString('Diesel', $section);
    }

    public function test_an_engine_the_shop_does_not_sell_for_is_not_listed(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $variant = $this->variant($brand, $this->family($brand, 'Tivoli'), 'Tivoli', 2015, 2019);
        $this->engine($variant, 'petrol', 1.6);
        $this->engine($variant, 'diesel', 1.6, 'turbo');

        $this->fit($product, $brand, $variant, null, null, '1.6 Petrol');
        $this->fit($product, $brand, $variant, null, null, '1.6 Turbo Diesel');

        $section = $this->section($this->pageIn('en', $product));

        // One car, and only the engine a customer here can buy parts for.
        $this->assertSame(1, substr_count($section, '<li class="fitment-card'));
        $this->assertStringContainsString('1.6L', $section);
        $this->assertStringContainsString('Petrol', $section);
        $this->assertStringNotContainsString('Diesel', $section);
        // The record still holds both.
        $this->assertSame(2, $variant->engineTypes()->count());
    }

    public function test_two_year_ranges_are_never_folded_into_one_card(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');

        foreach ([['rexton-g4-2022', 2022, 2026], ['rexton-g4-2018', 2018, 2021]] as [$slug, $from, $to]) {
            $variant = $this->variant($brand, $family, 'Rexton G4', $from, $to, $slug);
            $this->engine($variant, 'petrol', 2.0, 'turbo');
            $this->fit($product, $brand, $variant, null, null, '2.0 Turbo Petrol');
        }

        $section = $this->section($this->pageIn('en', $product));

        $this->assertSame(2, substr_count($section, '<li class="fitment-card'));
        $this->assertStringContainsString('2022–2026', $section);
        $this->assertStringContainsString('2018–2021', $section);
        $this->assertStringNotContainsString('2018–2026', $section);
    }

    public function test_a_two_litre_engine_is_not_written_as_a_bare_two(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');
        $variant = $this->variant($brand, $family, 'Rexton G4', 2022, 2026);
        $this->engine($variant, 'petrol', 2.0, 'turbo');

        // What the admin form stored before the displacement was written with
        // its decimal. The row behind it still knows the engine is a 2.0.
        $this->fit($product, $brand, $variant, null, null, '2 Turbo Petrol');

        $response = $this->get(route('shop.show', $product))->assertOk();

        $response->assertSeeText('2.0L');
        $response->assertSeeText('Turbo');
        $response->assertSeeText('Petrol');
    }

    public function test_several_variants_of_one_family_sit_under_one_heading(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');

        $this->fit($product, $brand, $this->variant($brand, $family, 'Rexton II', 2007, 2012, 'rexton-ii'));
        $this->fit($product, $brand, $this->variant($brand, $family, 'Rexton W', 2013, 2017, 'rexton-w'));

        $response = $this->get(route('shop.show', $product))->assertOk();
        $section = $this->section($response->getContent());

        $this->assertSame(1, substr_count($section, 'fitment-family-name'));
        $response->assertSeeText('Rexton II');
        $response->assertSeeText('Rexton W');
        $response->assertSeeText('2 compatible configurations');
    }

    public function test_a_variant_with_only_a_start_year_reads_as_that_year_and_newer(): void
    {
        $product = $this->productFittingVariantYears(2019, null);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('2019 and newer');
    }

    public function test_a_variant_with_only_an_end_year_reads_as_that_year_and_older(): void
    {
        $product = $this->productFittingVariantYears(null, 2012);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('2012 and older');
    }

    public function test_a_variant_with_no_years_says_they_are_not_recorded(): void
    {
        $product = $this->productFittingVariantYears(null, null);

        $response = $this->get(route('shop.show', $product))->assertOk();

        $response->assertSeeText('Model years not recorded');
        $this->assertStringNotContainsString('Any year', $this->section($response->getContent()));
    }

    public function test_a_fitment_with_no_engine_says_so_rather_than_guessing(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');
        $variant = $this->variant($brand, $family, 'Rexton G4', 2022, 2026);
        // The variant has an engine on record; the fitment names none, so the
        // card must not borrow it and claim a fit that was never entered.
        $this->engine($variant, 'petrol', 2.0, 'turbo');
        $this->fit($product, $brand, $variant);

        $response = $this->get(route('shop.show', $product))->assertOk();
        $section = $this->section($response->getContent());

        $response->assertSeeText('Engine not recorded');
        $this->assertStringNotContainsString('2.0L', $section);
    }

    public function test_a_product_fitting_two_families_shows_both(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();

        $this->fit($product, $brand, $this->variant($brand, $this->family($brand, 'Rexton'), 'Rexton II', 2007, 2012, 'rexton-ii'));
        $this->fit($product, $brand, $this->variant($brand, $this->family($brand, 'Actyon'), 'Actyon Sports', 2012, 2018, 'actyon-sports'));

        $response = $this->get(route('shop.show', $product))->assertOk();

        $response->assertSeeText('2 vehicle families');
        $response->assertSeeText('Rexton II');
        $response->assertSeeText('Actyon Sports');
    }

    public function test_a_product_fitting_one_variant_counts_in_the_singular(): void
    {
        $product = $this->productFittingVariantYears(2022, 2026);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('1 vehicle family')
            ->assertSeeText('1 compatible configuration');
    }

    public function test_two_identical_fitment_rows_are_shown_once(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');
        $variant = $this->variant($brand, $family, 'Rexton G4', 2022, 2026);

        $this->fit($product, $brand, $variant, 2022, 2026, '3.2 Petrol');
        $this->fit($product, $brand, $variant, 2022, 2026, '3.2 Petrol');

        $section = $this->section($this->get(route('shop.show', $product))->assertOk()->getContent());

        $this->assertSame(1, substr_count($section, 'Rexton G4'));
    }

    public function test_a_product_with_no_fitments_falls_back_to_the_listed_models(): void
    {
        $product = Product::factory()->create(['compatible_models' => ['Legacy Model A', 'Legacy Model B']]);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Legacy Model A')
            ->assertSeeText('Legacy Model B');
    }

    public function test_a_product_with_nothing_on_record_says_so(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Compatibility details are available on request.');
    }

    public function test_two_variants_sharing_a_name_get_separate_related_part_links(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');

        $this->fit($product, $brand, $this->variant($brand, $family, 'Rexton G4', 2022, 2026, 'rexton-g4-2022'));
        $this->fit($product, $brand, $this->variant($brand, $family, 'Rexton G4', 2018, 2021, 'rexton-g4-2018'));

        $html = $this->get(route('shop.show', $product))->assertOk()->getContent();
        $links = $this->relatedPartLinks($html);

        $this->assertCount(2, $links, 'Each vehicle page should be linked exactly once.');
        $this->assertSame($links, array_unique($links), 'The same vehicle page is linked twice.');
        // Two chips reading the same words are what the years are here to fix.
        $this->assertStringContainsString('Rexton G4 2022–2026', $html);
        $this->assertStringContainsString('Rexton G4 2018–2021', $html);
    }

    public function test_the_english_page_reads_left_to_right(): void
    {
        $html = $this->pageIn('en', $this->localizedProduct());

        $this->assertMatchesRegularExpression('/<html[^>]*\bdir="ltr"/', $html);
        $this->assertStringContainsString('Vehicle Compatibility', $html);
        // The full stop belongs at the end of the sentence, which is where the
        // sentence itself puts it; the direction on <html> does the rest.
        $this->assertStringContainsString(
            'This part is compatible with the vehicle configurations listed below.',
            $html
        );
    }

    public function test_the_arabic_page_reads_right_to_left_and_in_arabic(): void
    {
        $html = $this->pageIn('ar', $this->localizedProduct());
        $section = $this->section($html);

        $this->assertMatchesRegularExpression('/<html[^>]*\bdir="rtl"/', $html);

        foreach ([
            "\u{62A}\u{648}\u{627}\u{641}\u{642} \u{627}\u{644}\u{645}\u{631}\u{643}\u{628}\u{627}\u{62A}",
            "\u{62A}\u{648}\u{627}\u{641}\u{642} \u{645}\u{624}\u{643}\u{62F} \u{644}\u{647}\u{630}\u{647} \u{627}\u{644}\u{642}\u{637}\u{639}\u{629}",
        ] as $arabic) {
            $this->assertStringContainsString($arabic, $section, 'Arabic copy is missing from the section.');
        }

        // The English keys must not survive into the Arabic page.
        $this->assertStringNotContainsString('Vehicle Compatibility', $section);
        $this->assertStringNotContainsString('Confirmed fit for this part', $section);
        $this->assertStringNotContainsString('This part is compatible', $section);
    }

    public function test_the_kurdish_page_reads_right_to_left_and_in_kurdish(): void
    {
        $html = $this->pageIn('ku', $this->localizedProduct());
        $section = $this->section($html);

        $this->assertMatchesRegularExpression('/<html[^>]*\bdir="rtl"/', $html);
        $this->assertStringContainsString(
            __('Vehicle Compatibility', [], 'ku'),
            $section,
            'Kurdish copy is missing from the section.'
        );
        $this->assertStringContainsString(__('Confirmed fit for this part', [], 'ku'), $section);

        $this->assertStringNotContainsString('Vehicle Compatibility', $section);
        $this->assertStringNotContainsString('Confirmed fit for this part', $section);
    }

    public function test_a_year_range_is_isolated_so_it_cannot_be_reordered(): void
    {
        // One product, read three times: creating it per locale would collide on
        // the family slug, which is unique per brand.
        $product = $this->localizedProduct();

        foreach (['en', 'ar', 'ku'] as $locale) {
            $section = $this->section($this->pageIn($locale, $product));

            $this->assertStringContainsString(
                '<bdi>2022'."\u{2013}".'2026</bdi>',
                $section,
                "The year range is not isolated in {$locale}, so bidi reordering can reach it."
            );
            $this->assertStringNotContainsString('2026'."\u{2013}".'2022', $section);
        }
    }

    public function test_a_displacement_is_pinned_left_to_right(): void
    {
        // One product, read three times: creating it per locale would collide on
        // the family slug, which is unique per brand.
        $product = $this->localizedProduct();

        foreach (['en', 'ar', 'ku'] as $locale) {
            $section = $this->section($this->pageIn($locale, $product));

            $this->assertStringContainsString(
                '<span class="fitment-chip strong" dir="ltr">2.0L</span>',
                $section,
                "The displacement is not pinned left to right in {$locale}."
            );
            $this->assertStringNotContainsString('L2.0', $section);
        }
    }

    public function test_each_count_is_isolated_from_the_one_beside_it(): void
    {
        // One product, read three times: creating it per locale would collide on
        // the family slug, which is unique per brand.
        $product = $this->localizedProduct();

        foreach (['en', 'ar', 'ku'] as $locale) {
            $section = $this->section($this->pageIn($locale, $product));

            $this->assertSame(
                2,
                substr_count($this->countLine($section), '<bdi>'),
                "The counts share one run in {$locale}, so a numeral can drift onto the wrong phrase."
            );
        }
    }

    public function test_the_counts_agree_with_the_number_they_report(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();

        // Two families, so the counter has a plural to get wrong.
        foreach ([['Rexton', 'Rexton II', 2007, 2012], ['Actyon', 'Actyon Sports', 2012, 2018]] as [$familyName, $name, $from, $to]) {
            $family = $this->family($brand, $familyName);
            $variant = $this->variant($brand, $family, $name, $from, $to, str($name)->slug()->value());
            $this->engine($variant, 'petrol', 3.2);
            $this->fit($product, $brand, $variant, null, null, '3.2 Petrol');
        }

        // Arabic counts in six forms, not two. Offering only a singular and a
        // plural left every count falling back to the singular, so a page
        // listing two families read as though it listed one.
        foreach (['en', 'ar', 'ku'] as $locale) {
            $line = $this->countLine($this->section($this->pageIn($locale, $product)));

            $this->assertStringNotContainsString(
                trans_choice(':count vehicle family|:count vehicle families', 1, ['count' => 1], $locale),
                $line,
                "The counter reads as one family in {$locale} while two are listed."
            );
            $this->assertStringContainsString(
                trans_choice(':count vehicle family|:count vehicle families', 2, ['count' => 2], $locale),
                $line
            );
        }
    }

    public function test_the_counter_no_longer_carries_the_brand(): void
    {
        $section = $this->section($this->pageIn('en', $this->localizedProduct()));

        $this->assertStringNotContainsString('SSANGYONG', $this->countLine($section));
    }

    public function test_a_card_missing_its_years_and_engine_is_not_called_a_confirmed_fit(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Actyon');
        // Nothing on the variant and nothing on the fitment: the record cannot
        // tell one Actyon from another.
        $this->fit($product, $brand, $this->variant($brand, $family, 'Actyon'));

        $section = $this->section($this->pageIn('en', $product));

        $this->assertStringContainsString('Compatibility details incomplete', $section);
        $this->assertStringNotContainsString('Confirmed fit for this part', $section);
        $this->assertStringContainsString('is-partial', $section);
    }

    public function test_a_card_with_years_but_no_engine_is_not_called_a_confirmed_fit(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $variant = $this->variant($brand, $this->family($brand, 'Rexton'), 'Rexton G4', 2022, 2026);
        $this->fit($product, $brand, $variant);

        $section = $this->section($this->pageIn('en', $product));

        $this->assertStringContainsString('Compatibility details incomplete', $section);
        $this->assertStringNotContainsString('Confirmed fit for this part', $section);
    }

    public function test_a_card_with_an_engine_but_no_years_is_not_called_a_confirmed_fit(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $variant = $this->variant($brand, $this->family($brand, 'Rexton'), 'Rexton G4');
        $this->engine($variant, 'petrol', 2.0, 'turbo');
        $this->fit($product, $brand, $variant, null, null, '2.0 Turbo Petrol');

        $section = $this->section($this->pageIn('en', $product));

        $this->assertStringContainsString('Compatibility details incomplete', $section);
        $this->assertStringNotContainsString('Confirmed fit for this part', $section);
    }

    public function test_a_card_with_years_and_an_engine_is_called_a_confirmed_fit(): void
    {
        $section = $this->section($this->pageIn('en', $this->localizedProduct()));

        $this->assertStringContainsString('Confirmed fit for this part', $section);
        $this->assertStringNotContainsString('Compatibility details incomplete', $section);
    }

    public function test_the_incomplete_state_is_translated_in_every_locale(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $this->fit($product, $brand, $this->variant($brand, $this->family($brand, 'Actyon'), 'Actyon'));

        foreach (['ar', 'ku'] as $locale) {
            $section = $this->section($this->pageIn($locale, $product));

            $this->assertStringContainsString(__('Compatibility details incomplete', [], $locale), $section);
            $this->assertStringNotContainsString('Compatibility details incomplete', $section);
        }
    }

    public function test_the_fitment_board_costs_no_query_per_row(): void
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');

        foreach ([['Rexton II', 2007, 2012], ['Rexton W', 2013, 2017]] as [$name, $from, $to]) {
            $this->addFittedVariant($product, $brand, $family, $name, $from, $to);
        }

        $withTwo = $this->queriesRendering($product);

        foreach ([['Rexton G4', 2018, 2021], ['Rexton Sports', 2022, 2026]] as [$name, $from, $to]) {
            $this->addFittedVariant($product, $brand, $family, $name, $from, $to);
        }

        $withFour = $this->queriesRendering($product);

        $this->assertSame(
            $withTwo,
            $withFour,
            "Two more fitments cost {$withFour} queries instead of {$withTwo}, so the board is reading a relation row by row."
        );
    }

    /**
     * Queries for one render, with the caches and the view tracker already warm
     * so only the fitments are being measured.
     */
    private function queriesRendering(Product $product): int
    {
        $this->get(route('shop.show', $product))->assertOk();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get(route('shop.show', $product))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function addFittedVariant(
        Product $product,
        VehicleBrand $brand,
        VehicleModelFamily $family,
        string $name,
        int $from,
        int $to,
    ): void {
        $variant = $this->variant($brand, $family, $name, $from, $to, str($name)->slug()->value());
        $this->engine($variant, 'petrol', 3.2);
        $this->fit($product, $brand, $variant, null, null, '3.2 Petrol');
    }

    /**
     * A product whose one fitment is complete, named in all three languages.
     */
    private function localizedProduct(): Product
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Rexton',
            'name_en' => 'Rexton',
            'name_ar' => "\u{631}\u{64A}\u{643}\u{633}\u{62A}\u{648}\u{646}",
            'name_ku' => "\u{695}\u{6CE}\u{6A9}\u{633}\u{62A}\u{6C6}\u{646}",
            'slug' => 'rexton',
        ]);
        $variant = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'Rexton G4',
            'name_en' => 'Rexton G4',
            'name_ar' => "\u{631}\u{64A}\u{643}\u{633}\u{62A}\u{648}\u{646} G4",
            'name_ku' => "\u{695}\u{6CE}\u{6A9}\u{633}\u{62A}\u{6C6}\u{646} G4",
            'slug' => 'rexton-g4',
            'production_start_year' => 2022,
            'production_end_year' => 2026,
        ]);
        $this->engine($variant, 'petrol', 2.0, 'turbo');
        $this->fit($product, $brand, $variant, null, null, '2.0 Turbo Petrol');

        return $product;
    }

    private function pageIn(string $locale, Product $product): string
    {
        return (string) $this->get(route('shop.show', $product).'?lang='.$locale)
            ->assertOk()
            ->getContent();
    }

    private function countLine(string $section): string
    {
        preg_match('/<p class="fitment-board-count">(.*?)<\/p>/s', $section, $match);

        return $match[1] ?? '';
    }

    private function productFittingVariantYears(?int $from, ?int $to): Product
    {
        $product = Product::factory()->create(['compatible_models' => null]);
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');
        $variant = $this->variant($brand, $family, 'Rexton G4', $from, $to);
        $this->engine($variant, 'petrol', 2.0, 'turbo');
        $this->fit($product, $brand, $variant, null, null, '2.0 Turbo Petrol');

        return $product;
    }

    /** @return array<int, string> */
    private function relatedPartLinks(string $html): array
    {
        preg_match_all('#href="([^"]*/vehicles/[^"]+)"#', $html, $matches);

        return array_values(array_unique($matches[1]));
    }

    private function section(string $html): string
    {
        $start = strpos($html, 'data-product-compatibility');
        $end = $start === false ? false : strpos($html, '</section>', $start);

        return $start === false ? '' : substr($html, $start, $end === false ? null : $end - $start);
    }

    private function brand(): VehicleBrand
    {
        return VehicleBrand::query()->firstOrCreate(
            ['name' => 'SSANGYONG / KGM'],
            ['slug' => 'ssangyong-kgm'],
        );
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
            'slug' => $slug ?: str($name)->slug()->value(),
            'production_start_year' => $from,
            'production_end_year' => $to,
        ]);
    }

    private function engine(VehicleModel $variant, string $fuel, ?float $size = null, ?string $aspiration = null): void
    {
        $variant->engineTypes()->create([
            'name' => VehicleFuelType::displayName($fuel, $size, $aspiration, 'en'),
            'fuel_type' => $fuel,
            'engine_size' => $size,
            'aspiration' => $aspiration,
        ]);
    }

    private function fit(
        Product $product,
        VehicleBrand $brand,
        VehicleModel $variant,
        ?int $from = null,
        ?int $to = null,
        ?string $engine = null,
    ): void {
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $variant->id,
            'year_from' => $from,
            'year_to' => $to,
            'engine' => $engine,
        ]);
    }
}
