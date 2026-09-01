<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Models\VehicleModelFamily;
use App\Support\VehicleFuelType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What makes one car one record.
 *
 * There is one form for adding a vehicle and it made a variant every time, so
 * an operator adding a second engine to a car they had already entered ended up
 * with a second copy of that car. The storefront then offered both — "Tivoli
 * 2015-2019" twice, identical on screen, with the engine that told them apart
 * shown nowhere.
 *
 * An engine is an option on a car, not another car. Years still are: a Rexton
 * G4 built 2018-2021 and one built 2022-2026 take different parts and stay
 * apart, and a variant with no years is never guessed at.
 */
class VehicleModelIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create(['id' => 1, 'name_en' => 'Suspension', 'slug' => 'suspension']);
    }

    public function test_adding_a_second_engine_to_a_recorded_car_does_not_make_a_second_car(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Tivoli');
        $tivoli = $this->variant($brand, $family, 'Tivoli', 2015, 2019);
        $this->engine($tivoli, 'petrol', 1.6);

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_family_id' => $family->id,
                'name_en' => 'Tivoli',
                'production_start_year' => 2015,
                'production_end_year' => 2019,
                'engines' => [['fuel_type' => 'diesel', 'engine_size' => '1.6', 'aspiration' => 'turbo']],
            ])
            ->assertRedirect(route('admin.vehicle-fitments.index'));

        $this->assertSame(1, VehicleModel::query()->where('name', 'Tivoli')->count());
        $this->assertSame(2, $tivoli->engineTypes()->count());
        $this->assertEqualsCanonicalizing(
            ['petrol', 'diesel'],
            $tivoli->engineTypes()->pluck('fuel_type')->all()
        );
    }

    public function test_the_same_engine_submitted_twice_is_not_recorded_twice(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Tivoli');
        $tivoli = $this->variant($brand, $family, 'Tivoli', 2015, 2019);
        $this->engine($tivoli, 'petrol', 1.6);

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_family_id' => $family->id,
                'name_en' => 'Tivoli',
                'production_start_year' => 2015,
                'production_end_year' => 2019,
                'engines' => [['fuel_type' => 'petrol', 'engine_size' => '1.6', 'aspiration' => null]],
            ]);

        $this->assertSame(1, $tivoli->engineTypes()->count());
    }

    public function test_the_same_name_over_different_years_is_a_different_car(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');
        $this->variant($brand, $family, 'Rexton G4', 2018, 2021, 'rexton-g4-2018');

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_family_id' => $family->id,
                'name_en' => 'Rexton G4',
                'production_start_year' => 2022,
                'production_end_year' => 2026,
                'engines' => [['fuel_type' => 'petrol', 'engine_size' => '2.0', 'aspiration' => 'turbo']],
            ]);

        $this->assertSame(2, VehicleModel::query()->where('name', 'Rexton G4')->count());
    }

    public function test_a_car_with_no_years_is_never_folded_into_one_that_has_them(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Actyon');
        $this->variant($brand, $family, 'Actyon Sports', 2005, 2011, 'actyon-sports');

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_family_id' => $family->id,
                'name_en' => 'Actyon Sports',
                'engines' => [['fuel_type' => 'petrol', 'engine_size' => '2.0', 'aspiration' => null]],
            ]);

        // Two rows on purpose: which car the yearless one is has not been
        // established, and guessing would rewrite what parts are sold as fitting.
        $this->assertSame(2, VehicleModel::query()->where('name', 'Actyon Sports')->count());
    }

    public function test_an_edit_cannot_walk_a_variant_onto_another_ones_identity(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Rexton');
        $this->variant($brand, $family, 'Rexton G4', 2018, 2021, 'rexton-g4-2018');
        $other = $this->variant($brand, $family, 'Rexton G4', 2022, 2026, 'rexton-g4-2022');

        $this->actingAs($this->admin())
            ->from(route('admin.vehicle-fitments.models.edit', $other))
            ->patch(route('admin.vehicle-fitments.models.update', $other), [
                'name_en' => 'Rexton G4',
                'production_start_year' => 2018,
                'production_end_year' => 2021,
            ])
            ->assertSessionHasErrors('name_en');

        $this->assertSame(2022, (int) $other->refresh()->production_start_year);
    }

    public function test_the_merge_command_reports_without_changing_anything(): void
    {
        [$keep, $duplicate] = $this->duplicatedTivoli();

        $this->artisan('vehicles:merge-duplicates')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertDatabaseHas('vehicle_models', ['id' => $duplicate->id]);
        $this->assertSame(1, $keep->engineTypes()->count());
    }

    public function test_the_merge_command_keeps_every_fitment(): void
    {
        [$keep, $duplicate] = $this->duplicatedTivoli();

        $petrolPart = Product::factory()->create();
        $dieselPart = Product::factory()->create();
        $this->fit($petrolPart, $brand = $keep->brand, $keep, '1.6 Petrol');
        $this->fit($dieselPart, $brand, $duplicate, '1.6 Turbo Diesel');

        $this->artisan('vehicles:merge-duplicates', ['--apply' => true])->assertSuccessful();

        $this->assertDatabaseMissing('vehicle_models', ['id' => $duplicate->id]);
        $this->assertSame(2, ProductVehicleFitment::query()->where('vehicle_model_id', $keep->id)->count());
        $this->assertSame(2, ProductVehicleFitment::query()->count());
        $this->assertEqualsCanonicalizing(
            ['petrol', 'diesel'],
            $keep->engineTypes()->pluck('fuel_type')->all()
        );
    }

    public function test_the_merge_command_does_not_record_the_same_fit_twice(): void
    {
        [$keep, $duplicate] = $this->duplicatedTivoli();

        $part = Product::factory()->create();
        // The same product, car, years and engine, entered against both copies.
        $this->fit($part, $keep->brand, $keep, '1.6 Petrol');
        $this->fit($part, $keep->brand, $duplicate, '1.6 Petrol');

        $this->artisan('vehicles:merge-duplicates', ['--apply' => true])->assertSuccessful();

        $this->assertSame(1, ProductVehicleFitment::query()->count());
    }

    public function test_the_merge_command_can_be_run_again_safely(): void
    {
        [$keep, $duplicate] = $this->duplicatedTivoli();
        $this->fit(Product::factory()->create(), $keep->brand, $duplicate, '1.6 Turbo Diesel');

        $this->artisan('vehicles:merge-duplicates', ['--apply' => true])->assertSuccessful();
        $this->artisan('vehicles:merge-duplicates', ['--apply' => true])
            ->expectsOutputToContain('No duplicate vehicle variants found')
            ->assertSuccessful();

        $this->assertSame(1, VehicleModel::query()->where('name', 'Tivoli')->count());
        $this->assertSame(1, ProductVehicleFitment::query()->count());
    }

    public function test_the_merge_command_leaves_different_year_ranges_alone(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Korando');

        foreach ([[2012, 2015, 'a'], [2012, 2016, 'b'], [2012, 2018, 'c']] as [$from, $to, $suffix]) {
            $this->variant($brand, $family, 'Korando Sports', $from, $to, 'korando-sports-'.$suffix);
        }

        $this->artisan('vehicles:merge-duplicates', ['--apply' => true])->assertSuccessful();

        $this->assertSame(3, VehicleModel::query()->where('name', 'Korando Sports')->count());
    }

    public function test_the_merge_command_leaves_a_yearless_variant_alone(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Actyon');
        $this->variant($brand, $family, 'Actyon Sports', 2005, 2011, 'actyon-sports');
        $this->variant($brand, $family, 'Actyon Sports', null, null, 'actyon-sports-2');

        $this->artisan('vehicles:merge-duplicates', ['--apply' => true])->assertSuccessful();

        $this->assertSame(2, VehicleModel::query()->where('name', 'Actyon Sports')->count());
    }

    public function test_the_admin_shows_one_card_with_both_engines(): void
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Tivoli');
        $tivoli = $this->variant($brand, $family, 'Tivoli', 2015, 2019);
        $this->engine($tivoli, 'petrol', 1.6);
        $this->engine($tivoli, 'diesel', 1.6, 'turbo');

        $html = (string) $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, '>Tivoli</h4>'));
        // Diesel is hidden from customers, not from the person maintaining the
        // catalogue.
        $this->assertStringContainsString('1.6 Petrol', $html);
        $this->assertStringContainsString('1.6 Turbo Diesel', $html);
    }

    /** @return array{0: VehicleModel, 1: VehicleModel} */
    private function duplicatedTivoli(): array
    {
        $brand = $this->brand();
        $family = $this->family($brand, 'Tivoli');

        $keep = $this->variant($brand, $family, 'Tivoli', 2015, 2019, 'tivoli');
        $this->engine($keep, 'petrol', 1.6);

        // What the form used to leave behind: the same car again, carrying only
        // the engine the operator came back to add.
        $duplicate = $this->variant($brand, $family, 'Tivoli', 2015, 2019, 'tivoli-2');
        $this->engine($duplicate, 'diesel', 1.6, 'turbo');

        return [$keep, $duplicate];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'email_verified_at' => now()]);
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

    private function engine(VehicleModel $variant, string $fuel, ?float $size, ?string $aspiration = null): VehicleModelEngineType
    {
        return $variant->engineTypes()->create([
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
