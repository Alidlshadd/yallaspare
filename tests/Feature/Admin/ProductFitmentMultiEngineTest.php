<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Naming several engines on one vehicle card.
 *
 * A part that fits an Actyon Sport fits every engine it was built with, and
 * saying so used to mean filling the same card in three times. The card now
 * takes a set of engines, and each one becomes its own fitment row — because a
 * row is what the product page, the shop filter and the search each read an
 * engine off. What is defended here is that expansion: the right number of
 * rows, the right engine names on them, and nothing invented from what the
 * browser posted.
 */
class ProductFitmentMultiEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create(['id' => 1, 'name_en' => 'Filters', 'slug' => 'filters']);
    }

    public function test_one_engine_makes_one_fitment(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, [
            $engines['2.3 Petrol'],
        ]))->assertRedirect()->assertSessionHas('success', __('Product fitment created.'));

        $this->assertSame(1, ProductVehicleFitment::query()->count());
        $this->assertSame(['2.3 Petrol'], $this->recordedEngines($product));
    }

    public function test_three_engines_make_three_fitments(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, array_values($engines)))
            ->assertRedirect()
            ->assertSessionHas('success', __(':count product fitments created.', ['count' => 3]));

        $this->assertSame(
            ['2.0 Turbo Diesel', '2.2 Turbo Diesel', '2.3 Petrol'],
            $this->recordedEngines($product)
        );
    }

    public function test_no_row_carries_a_sentinel_instead_of_an_engine(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, array_values($engines)));

        foreach (ProductVehicleFitment::query()->get() as $fitment) {
            // Not "all", not a comma-joined list, not null standing for "every
            // engine" — each row names one engine and only this screen would
            // have been able to read anything else back.
            $this->assertNotNull($fitment->engine);
            $this->assertStringNotContainsString(',', (string) $fitment->engine);
            $this->assertNotSame('all', mb_strtolower((string) $fitment->engine));
        }
    }

    public function test_petrol_and_diesel_are_recorded_side_by_side(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, [
            $engines['2.3 Petrol'],
            $engines['2.2 Turbo Diesel'],
        ]));

        $this->assertSame(['2.2 Turbo Diesel', '2.3 Petrol'], $this->recordedEngines($product));
    }

    public function test_the_years_and_notes_reach_every_row(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        $payload = $this->payload($product, $brand, $variant, array_values($engines));
        $payload['fitments'][0]['year_from'] = 2005;
        $payload['fitments'][0]['year_to'] = 2011;
        $payload['fitments'][0]['notes'] = 'Original configuration';

        $this->post(route('admin.vehicle-fitments.store'), $payload);

        $rows = ProductVehicleFitment::query()->get();

        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertSame(2005, (int) $row->year_from);
            $this->assertSame(2011, (int) $row->year_to);
            $this->assertSame('Original configuration', $row->notes);
        }
    }

    public function test_an_engine_from_another_variant_is_refused(): void
    {
        [$product, $brand, $variant] = $this->actyonSport();
        $other = $this->variant($brand, $variant->family, 'Tivoli', 'tivoli');
        $foreign = $other->engineTypes()->create(['name' => '1.6 Petrol', 'fuel_type' => 'petrol', 'engine_size' => 1.6]);

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, [$foreign->id]))
            ->assertSessionHasErrors('fitments.0.engine_ids');

        $this->assertSame(0, ProductVehicleFitment::query()->count());
    }

    public function test_the_same_engine_posted_twice_is_written_once(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();
        $id = $engines['2.3 Petrol'];

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, [$id, $id, $id]));

        $this->assertSame(1, ProductVehicleFitment::query()->count());
    }

    public function test_a_rule_that_already_exists_is_skipped_not_repeated(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, [
            $engines['2.3 Petrol'],
        ]));

        // The same car again, this time with all three engines: two are new.
        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, array_values($engines)))
            ->assertSessionHas('success', __(':count product fitments created.', ['count' => 2])
                .' '.trans_choice(
                    ':count fitment already existed and was skipped.|:count fitments already existed and were skipped.',
                    1,
                    ['count' => 1],
                ));

        $this->assertSame(3, ProductVehicleFitment::query()->count());
        $this->assertSame(
            ['2.0 Turbo Diesel', '2.2 Turbo Diesel', '2.3 Petrol'],
            $this->recordedEngines($product)
        );
    }

    public function test_a_different_year_range_is_not_treated_as_a_duplicate(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        $first = $this->payload($product, $brand, $variant, [$engines['2.3 Petrol']]);
        $first['fitments'][0]['year_from'] = 2005;
        $first['fitments'][0]['year_to'] = 2008;
        $this->post(route('admin.vehicle-fitments.store'), $first);

        $second = $this->payload($product, $brand, $variant, [$engines['2.3 Petrol']]);
        $second['fitments'][0]['year_from'] = 2009;
        $second['fitments'][0]['year_to'] = 2011;
        $this->post(route('admin.vehicle-fitments.store'), $second);

        $this->assertSame(2, ProductVehicleFitment::query()->count());
    }

    public function test_a_refused_batch_leaves_nothing_behind(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();
        [$otherBrand, $otherVariant] = [$this->brand('Toyota', 'toyota'), null];
        $otherVariant = $this->variant($otherBrand, $this->family($otherBrand, 'Corolla'), 'Corolla', 'corolla');

        $payload = $this->payload($product, $brand, $variant, array_values($engines));
        // A second card naming a variant that belongs to another brand.
        $payload['fitments'][] = [
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $otherVariant->id,
            'engine_ids' => [],
        ];

        $this->post(route('admin.vehicle-fitments.store'), $payload)->assertSessionHasErrors();

        $this->assertSame(0, ProductVehicleFitment::query()->count());
    }

    public function test_two_cards_expand_into_the_rules_the_preview_promised(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();
        $tivoli = $this->variant($brand, $variant->family, 'Tivoli', 'tivoli');
        $petrol = $tivoli->engineTypes()->create(['name' => '1.6 Petrol', 'fuel_type' => 'petrol', 'engine_size' => 1.6]);
        $diesel = $tivoli->engineTypes()->create(['name' => '1.6 Turbo Diesel', 'fuel_type' => 'diesel', 'engine_size' => 1.6, 'aspiration' => 'turbo']);

        $this->post(route('admin.vehicle-fitments.store'), [
            'product_id' => $product->id,
            'fitments' => [
                ['vehicle_brand_id' => $brand->id, 'vehicle_model_id' => $tivoli->id, 'engine_ids' => [$petrol->id, $diesel->id]],
                ['vehicle_brand_id' => $brand->id, 'vehicle_model_id' => $variant->id, 'engine_ids' => array_values($engines)],
            ],
        ])->assertSessionHas('success', __(':count product fitments created.', ['count' => 5]));

        $this->assertSame(5, ProductVehicleFitment::query()->count());
    }

    public function test_a_car_with_engines_cannot_be_recorded_without_one(): void
    {
        [$product, $brand, $variant] = $this->actyonSport();

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, []))
            ->assertSessionHasErrors(['fitments.0.engine_ids' => __('Please select at least one engine for this vehicle variant.')]);

        // And no blank row behind it: on a car whose engines are on record, an
        // empty engine column would read as "not recorded" and it is not true.
        $this->assertSame(0, ProductVehicleFitment::query()->count());
    }

    public function test_a_car_with_no_engines_on_record_is_refused_with_a_way_out(): void
    {
        [$product, $brand, $variant] = $this->actyonSport();
        $bare = $this->variant($brand, $variant->family, 'Korando', 'korando');

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $bare, []))
            ->assertSessionHasErrors([
                'fitments.0.engine_ids' => __('No engines are configured for this vehicle variant. Configure an engine before adding the fitment.'),
            ]);

        $this->assertSame(0, ProductVehicleFitment::query()->count());
    }

    public function test_the_error_names_the_card_it_belongs_to(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();
        $second = $this->variant($brand, $variant->family, 'Tivoli', 'tivoli');
        $second->engineTypes()->create(['name' => '1.6 Petrol', 'fuel_type' => 'petrol', 'engine_size' => 1.6]);

        $this->post(route('admin.vehicle-fitments.store'), [
            'product_id' => $product->id,
            'fitments' => [
                ['vehicle_brand_id' => $brand->id, 'vehicle_model_id' => $variant->id, 'engine_ids' => array_values($engines)],
                ['vehicle_brand_id' => $brand->id, 'vehicle_model_id' => $second->id, 'engine_ids' => []],
            ],
        ])
            ->assertSessionHasErrors('fitments.1.engine_ids')
            ->assertSessionDoesntHaveErrors('fitments.0.engine_ids');

        // The valid card is not written either: one refusal stops the batch.
        $this->assertSame(0, ProductVehicleFitment::query()->count());
    }

    public function test_a_refused_batch_keeps_what_the_operator_had_typed(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        $this->from(route('admin.vehicle-fitments.index'))
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [[
                    'vehicle_brand_id' => $brand->id,
                    'vehicle_model_family_id' => $variant->vehicle_model_family_id,
                    'vehicle_model_id' => $variant->id,
                    'engine_ids' => [],
                    'year_from' => 2005,
                    'year_to' => 2011,
                    'notes' => 'Original configuration',
                ]],
            ])
            ->assertRedirect(route('admin.vehicle-fitments.index'))
            ->assertSessionHasInput('fitments.0.vehicle_model_id', $variant->id)
            ->assertSessionHasInput('fitments.0.year_from', 2005)
            ->assertSessionHasInput('fitments.0.notes', 'Original configuration');
    }

    public function test_a_selection_survives_a_refusal_on_another_card(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();
        $bare = $this->variant($brand, $variant->family, 'Korando', 'korando');

        $this->from(route('admin.vehicle-fitments.index'))
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [
                    ['vehicle_brand_id' => $brand->id, 'vehicle_model_id' => $variant->id, 'engine_ids' => array_values($engines)],
                    ['vehicle_brand_id' => $brand->id, 'vehicle_model_id' => $bare->id, 'engine_ids' => []],
                ],
            ])
            ->assertSessionHasErrors('fitments.1.engine_ids')
            ->assertSessionHasInput('fitments.0.engine_ids', array_values($engines));
    }

    public function test_a_variant_is_still_required(): void
    {
        [$product, $brand] = $this->actyonSport();

        $this->post(route('admin.vehicle-fitments.store'), [
            'product_id' => $product->id,
            'fitments' => [['vehicle_brand_id' => $brand->id]],
        ])->assertSessionHasErrors('fitments.0.vehicle_model_id');

        $this->assertSame(0, ProductVehicleFitment::query()->count());
    }

    public function test_an_older_null_engine_row_is_left_alone(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        // A row from before the rule existed.
        $legacy = ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $variant->id,
            'engine' => null,
        ]);

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, array_values($engines)));

        $this->assertDatabaseHas('product_vehicle_fitments', ['id' => $legacy->id, 'engine' => null]);
        $this->assertSame(4, ProductVehicleFitment::query()->count());
    }

    public function test_the_preview_counts_only_what_will_be_written(): void
    {
        [, , $variant] = $this->actyonSport();

        $html = $this->get(route('admin.vehicle-fitments.index'))->assertOk()->getContent();

        // Nothing is picked on a fresh form, so nothing will be written.
        $this->assertStringContainsString(__(':count fitment rules', ['count' => 0]), $html);
        $this->assertStringContainsString(__('Select at least one engine to create this fitment.'), $html);
        // The rounding that made an empty card look like one rule is gone.
        $this->assertStringNotContainsString('Math.max(1,', $html);
    }

    public function test_the_form_offers_the_engines_by_id(): void
    {
        [, , $variant, $engines] = $this->actyonSport();

        $html = $this->get(route('admin.vehicle-fitments.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-admin-engine-picker', $html);
        $this->assertStringContainsString(__('Select all engines'), $html);
        // The wrong old label promised petrol and listed diesel beside it.
        $this->assertStringNotContainsString('Any configured petrol engine', $html);

        foreach ($engines as $id) {
            $this->assertMatchesRegularExpression(
                '/"id":'.$id.',/',
                $html,
                'The engine ids the form has to post are missing from the variant payload.'
            );
        }
    }

    public function test_the_expansion_costs_one_query_for_the_engine_names(): void
    {
        [$product, $brand, $variant, $engines] = $this->actyonSport();

        $reads = 0;
        DB::listen(function ($query) use (&$reads): void {
            if (str_contains($query->sql, 'vehicle_model_engine_types') && str_starts_with(trim($query->sql), 'select')) {
                $reads++;
            }
        });

        $this->post(route('admin.vehicle-fitments.store'), $this->payload($product, $brand, $variant, array_values($engines)));

        // One read for the variant the validator checks, one for the names the
        // writer resolves — not one per engine.
        $this->assertLessThanOrEqual(2, $reads, 'Engine rows were read once per selected engine.');
    }

    public function test_a_visitor_cannot_create_fitments(): void
    {
        $brand = $this->brand('SsangYong', 'ssangyong');
        $variant = $this->variant($brand, $this->family($brand, 'Actyon'), 'Actyon Sport', 'actyon-sport');
        $engine = $variant->engineTypes()->create(['name' => '2.3 Petrol', 'fuel_type' => 'petrol', 'engine_size' => 2.3]);
        $product = Product::factory()->create(['is_active' => true]);

        $this->post(route('admin.vehicle-fitments.store'), [
            'product_id' => $product->id,
            'fitments' => [[
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_id' => $variant->id,
                'engine_ids' => [$engine->id],
            ]],
        ])->assertRedirect(route('login'));

        $this->assertSame(0, ProductVehicleFitment::query()->count());
    }

    /**
     * The car from the report, with the three engines it was built with.
     *
     * @return array{Product, VehicleBrand, VehicleModel, array<string, int>}
     */
    private function actyonSport(): array
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]));

        $brand = $this->brand('SsangYong', 'ssangyong');
        $variant = $this->variant($brand, $this->family($brand, 'Actyon'), 'Actyon Sport', 'actyon-sport', 2005, 2011);

        $engines = [];
        foreach ([
            ['2.0 Turbo Diesel', 'diesel', 2.0, 'turbo'],
            ['2.2 Turbo Diesel', 'diesel', 2.2, 'turbo'],
            ['2.3 Petrol', 'petrol', 2.3, null],
        ] as [$name, $fuel, $size, $aspiration]) {
            $engines[$name] = (int) $variant->engineTypes()->create([
                'name' => $name,
                'fuel_type' => $fuel,
                'engine_size' => $size,
                'aspiration' => $aspiration,
            ])->id;
        }

        return [Product::factory()->create(['is_active' => true]), $brand, $variant->fresh(['engineTypes', 'family']), $engines];
    }

    /**
     * @param  list<int>  $engineIds
     * @return array<string, mixed>
     */
    private function payload(Product $product, VehicleBrand $brand, VehicleModel $variant, array $engineIds): array
    {
        return [
            'product_id' => $product->id,
            'fitments' => [[
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_family_id' => $variant->vehicle_model_family_id,
                'vehicle_model_id' => $variant->id,
                'engine_ids' => $engineIds,
            ]],
        ];
    }

    /** @return list<string> */
    private function recordedEngines(Product $product): array
    {
        return ProductVehicleFitment::query()
            ->where('product_id', $product->id)
            ->pluck('engine')
            ->sort()
            ->values()
            ->all();
    }

    private function brand(string $name, string $slug): VehicleBrand
    {
        return VehicleBrand::query()->create(['name' => $name, 'slug' => $slug]);
    }

    private function family(VehicleBrand $brand, string $name): VehicleModelFamily
    {
        return VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => $name,
            'slug' => str($name)->slug()->value(),
        ]);
    }

    private function variant(
        VehicleBrand $brand,
        VehicleModelFamily $family,
        string $name,
        string $slug,
        ?int $from = null,
        ?int $to = null,
    ): VehicleModel {
        return VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => $name,
            'name_en' => $name,
            'slug' => $slug,
            'production_start_year' => $from,
            'production_end_year' => $to,
        ]);
    }
}
