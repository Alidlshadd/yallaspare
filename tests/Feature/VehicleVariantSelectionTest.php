<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Models\VehicleModelFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Two Tivoli variants are two different cars taking different parts. The admin
 * variant dropdown printed only the name, so both read "Tivoli" and an operator
 * had no way to tell which one they were attaching a part to.
 */
class VehicleVariantSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_label_carries_years_and_engines(): void
    {
        [$older, $newer] = $this->twoTivolis();

        $this->assertSame(
            'Tivoli • 2015–2019 • 1.6 Petrol / 1.6 Turbo Diesel',
            $older->selectionLabel()
        );
        $this->assertSame(
            'Tivoli • 2020–2026 • 1.5 Turbo Petrol / 1.6 Turbo Diesel',
            $newer->selectionLabel()
        );
    }

    public function test_the_short_label_names_one_engine_and_counts_the_rest(): void
    {
        [$older, $newer] = $this->twoTivolis();

        $this->assertSame('Tivoli (2015–2019) — 1.6 Petrol +1', $older->shortSelectionLabel());
        $this->assertSame('Tivoli (2020–2026) — 1.5 Turbo Petrol +1', $newer->shortSelectionLabel());

        // The count is what stops a second engine from being hidden.
        $this->assertStringContainsString('+1', $older->shortSelectionLabel());
    }

    public function test_two_variants_sharing_a_name_produce_different_labels(): void
    {
        [$older, $newer] = $this->twoTivolis();

        $this->assertSame($older->name, $newer->name);
        $this->assertNotSame($older->shortSelectionLabel(), $newer->shortSelectionLabel());
        $this->assertNotSame($older->selectionLabel(), $newer->selectionLabel());
    }

    public function test_a_variant_with_one_engine_gets_no_counter(): void
    {
        $model = $this->variant('Korando', 2019, 2024, [['1.5 Turbo Petrol', 'petrol', 1.5, 'turbo']]);

        $this->assertSame('Korando (2019–2024) — 1.5 Turbo Petrol', $model->shortSelectionLabel());
        $this->assertStringNotContainsString('+', $model->shortSelectionLabel());
    }

    public function test_a_variant_without_years_or_engines_still_reads_as_its_name(): void
    {
        $model = $this->variant('Musso', null, null, []);

        $this->assertSame('Musso', $model->shortSelectionLabel());
        $this->assertSame('Musso', $model->selectionLabel());
    }

    public function test_the_label_never_queries_when_engines_were_not_loaded(): void
    {
        [$older] = $this->twoTivolis();

        $bare = VehicleModel::query()->findOrFail($older->id);
        $this->assertFalse($bare->relationLoaded('engineTypes'));

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $label = $bare->shortSelectionLabel();

        $this->assertSame(0, $queries, 'Building a label lazily loaded the engine relation.');
        $this->assertSame('Tivoli (2015–2019)', $label);
    }

    public function test_the_haystack_matches_a_year_a_fuel_and_a_displacement(): void
    {
        [$older] = $this->twoTivolis();
        $haystack = $older->selectionHaystack();

        foreach (['tivoli', '2015', '2019', 'diesel', 'petrol', '1.6'] as $needle) {
            $this->assertStringContainsString($needle, $haystack, "The filter cannot find '{$needle}'.");
        }
    }

    public function test_the_admin_form_distinguishes_the_two_variants(): void
    {
        [$older, $newer] = $this->twoTivolis();

        $content = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($older->shortSelectionLabel(), $content);
        $this->assertStringContainsString($newer->shortSelectionLabel(), $content);

        // The value stays the variant id, never the label.
        $this->assertStringContainsString('value="'.$older->id.'"', $content);
        $this->assertStringContainsString('value="'.$newer->id.'"', $content);
    }

    public function test_the_form_carries_each_variant_engines_for_the_dependent_select(): void
    {
        [$older, $newer] = $this->twoTivolis();

        $content = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->getContent();

        preg_match("/data-model-map='([^']+)'/", $content, $matches);
        $this->assertArrayHasKey(1, $matches);

        $map = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true, 512, JSON_THROW_ON_ERROR);
        $variants = collect($map)->flatten(1)->keyBy('id');

        $this->assertSame(
            ['1.6 Petrol', '1.6 Turbo Diesel'],
            array_column($variants[$older->id]['engines'], 'label')
        );
        $this->assertSame(
            ['1.5 Turbo Petrol', '1.6 Turbo Diesel'],
            array_column($variants[$newer->id]['engines'], 'label')
        );

        // The engine a fitment stores is the raw name, not the display text.
        $this->assertSame(
            ['1.6 Petrol', '1.6 Turbo Diesel'],
            array_column($variants[$older->id]['engines'], 'value')
        );
    }

    public function test_the_correct_variant_id_reaches_the_database(): void
    {
        [, $newer] = $this->twoTivolis();
        $product = $this->product();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [[
                    'vehicle_brand_id' => $newer->vehicle_brand_id,
                    'vehicle_model_family_id' => $newer->vehicle_model_family_id,
                    'vehicle_model_id' => $newer->id,
                    'engine' => '1.5 Turbo Petrol',
                ]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_vehicle_fitments', [
            'product_id' => $product->id,
            'vehicle_model_id' => $newer->id,
            'engine' => '1.5 Turbo Petrol',
        ]);
    }

    public function test_an_engine_from_the_other_variant_is_refused(): void
    {
        [$older] = $this->twoTivolis();
        $product = $this->product();

        // 1.5 Turbo Petrol belongs to the 2020-2026 car, not this one.
        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [[
                    'vehicle_brand_id' => $older->vehicle_brand_id,
                    'vehicle_model_family_id' => $older->vehicle_model_family_id,
                    'vehicle_model_id' => $older->id,
                    'engine' => '1.5 Turbo Petrol',
                ]],
            ])
            ->assertSessionHasErrors('fitments.0.engine');

        $this->assertDatabaseCount('product_vehicle_fitments', 0);
    }

    public function test_a_missing_variant_is_reported_in_plain_words(): void
    {
        $this->twoTivolis();
        $product = $this->product();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [[
                    'vehicle_brand_id' => VehicleBrand::query()->value('id'),
                    'vehicle_model_id' => '',
                ]],
            ])
            ->assertSessionHasErrors('fitments.0.vehicle_model_id');

        $errors = $response->getSession()->get('errors')->getBag('default');
        $message = $errors->first('fitments.0.vehicle_model_id');

        $this->assertSame('Please select a vehicle variant.', $message);
        $this->assertStringNotContainsString('fitments.0', $message);
    }

    public function test_the_operator_keeps_their_choices_after_a_validation_failure(): void
    {
        [, $newer] = $this->twoTivolis();
        $product = $this->product();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [[
                    'vehicle_brand_id' => $newer->vehicle_brand_id,
                    'vehicle_model_family_id' => $newer->vehicle_model_family_id,
                    'vehicle_model_id' => $newer->id,
                    'year_from' => 2024,
                    'year_to' => 2020,
                ]],
            ])
            ->assertSessionHasErrors('fitments.0.year_to')
            ->assertSessionHasInput('fitments.0.vehicle_model_id', (string) $newer->id);
    }

    public function test_the_variant_dropdown_does_not_query_engines_once_per_option(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'SsangYong', 'slug' => 'ssangyong']);
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Tivoli',
            'slug' => 'tivoli-family',
        ]);

        for ($i = 0; $i < 15; $i++) {
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

        $engineReads = [];
        DB::listen(function ($query) use (&$engineReads): void {
            if (str_contains($query->sql, 'vehicle_model_engine_types') && str_contains($query->sql, 'select')) {
                $engineReads[] = $query->sql;
            }
        });

        $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk();

        $perOption = count(array_filter(
            $engineReads,
            fn (string $sql): bool => str_contains($sql, '"vehicle_model_id" = ')
        ));

        $this->assertSame(0, $perOption, 'Engines were read one variant at a time while building the dropdown.');
        $this->assertLessThanOrEqual(3, count($engineReads), 'Engines were read '.count($engineReads).' times for 15 variants.');
    }

    public function test_the_engine_field_waits_for_a_variant_and_says_so(): void
    {
        $this->twoTivolis();

        $content = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->getContent();

        // Nothing is chosen on a fresh form, so the engine select is disabled
        // and explains what it is waiting for.
        $this->assertMatchesRegularExpression('/<select[^>]*data-admin-engine[^>]*\sdisabled/', $content);
        $this->assertStringContainsString('Select a vehicle variant first', $content);
    }

    public function test_the_selected_vehicle_summary_is_present_and_starts_hidden(): void
    {
        $this->twoTivolis();

        $content = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-admin-variant-summary', $content);
        $this->assertStringContainsString('Selected vehicle', $content);
        $this->assertStringContainsString('Available engines:', $content);
        $this->assertMatchesRegularExpression('/data-admin-variant-summary[^>]*\shidden/', $content);

        // Each option carries what the summary prints, so no request is needed
        // to fill it in when the operator changes the selection.
        $this->assertStringContainsString('data-engine-labels="1.6 Petrol, 1.6 Turbo Diesel"', $content);
    }

    public function test_the_variant_filter_can_match_a_year_or_a_fuel(): void
    {
        [$older] = $this->twoTivolis();

        $content = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-admin-variant-filter', $content);

        preg_match('/data-search="([^"]*tivoli[^"]*)"/', $content, $matches);
        $this->assertArrayHasKey(1, $matches);
        foreach (['2015', '2019', 'diesel'] as $needle) {
            $this->assertStringContainsString($needle, $matches[1]);
        }
    }

    public function test_an_existing_fitment_shows_its_variant_and_engine(): void
    {
        [$older] = $this->twoTivolis();
        $product = $this->product();

        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $older->vehicle_brand_id,
            'vehicle_model_id' => $older->id,
            'engine' => '1.6 Turbo Diesel',
            'year_from' => 2015,
            'year_to' => 2019,
        ]);

        $content = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->getContent();

        // The saved row is listed with the car it names, not a bare "Tivoli".
        $this->assertStringContainsString('1.6 Turbo Diesel', $content);
        $this->assertStringContainsString('Tivoli', $content);
    }

    public function test_the_label_follows_the_active_locale_and_falls_back_to_english(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'SsangYong', 'slug' => 'ssangyong']);
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Tivoli',
            'slug' => 'tivoli-family',
        ]);

        $translated = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'Tivoli',
            'name_en' => 'Tivoli',
            'name_ar' => 'تيفولي',
            'slug' => 'tivoli-ar',
            'production_start_year' => 2015,
            'production_end_year' => 2019,
        ]);
        VehicleModelEngineType::query()->create([
            'vehicle_model_id' => $translated->id,
            'name' => '1.6 Petrol',
            'fuel_type' => 'petrol',
            'engine_size' => 1.6,
        ]);

        $untranslated = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'Korando',
            'name_en' => 'Korando',
            'slug' => 'korando-ar',
            'production_start_year' => 2019,
            'production_end_year' => 2024,
        ]);

        app()->setLocale('ar');

        $translated = $translated->fresh(['engineTypes']);
        $untranslated = $untranslated->fresh(['engineTypes']);

        // Arabic name where there is one, English where there is not.
        $this->assertStringContainsString('تيفولي', $translated->shortSelectionLabel());
        $this->assertStringContainsString('Korando', $untranslated->shortSelectionLabel());

        // Years stay legible in any language, and the fuel is translated.
        $this->assertStringContainsString('2015–2019', $translated->shortSelectionLabel());
        $this->assertStringContainsString('بنزين', $translated->shortSelectionLabel());

        app()->setLocale('en');
    }

    /** @return array{VehicleModel, VehicleModel} */
    private function twoTivolis(): array
    {
        $older = $this->variant('Tivoli', 2015, 2019, [
            ['1.6 Petrol', 'petrol', 1.6, null],
            ['1.6 Turbo Diesel', 'diesel', 1.6, 'turbo'],
        ]);

        $newer = $this->variant('Tivoli', 2020, 2026, [
            ['1.5 Turbo Petrol', 'petrol', 1.5, 'turbo'],
            ['1.6 Turbo Diesel', 'diesel', 1.6, 'turbo'],
        ], $older->brand, $older->family);

        return [$older->fresh(['engineTypes']), $newer->fresh(['engineTypes'])];
    }

    /**
     * @param  list<array{0: string, 1: string|null, 2: float|null, 3: string|null}>  $engines
     */
    private function variant(
        string $name,
        ?int $from,
        ?int $to,
        array $engines,
        ?VehicleBrand $brand = null,
        ?VehicleModelFamily $family = null,
    ): VehicleModel {
        $brand ??= VehicleBrand::query()->firstOrCreate(
            ['slug' => 'ssangyong'],
            ['name' => 'SsangYong']
        );

        $family ??= VehicleModelFamily::query()->firstOrCreate(
            ['vehicle_brand_id' => $brand->id, 'slug' => Str::slug($name).'-family'],
            ['name' => $name]
        );

        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => $name,
            'name_en' => $name,
            'slug' => Str::slug($name).'-'.($from ?? 'x').'-'.($to ?? 'x'),
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

        return $model->fresh(['engineTypes', 'brand', 'family']);
    }

    private function product(): Product
    {
        Category::factory()->create(['id' => 1, 'name_en' => 'Filters', 'slug' => 'filters']);

        return Product::factory()->create(['name_en' => 'Engine Oil Filter']);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }
}
