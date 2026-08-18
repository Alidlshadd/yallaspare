<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleModelEngineTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_and_multiple_engine_types_can_be_created_together(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'Toyota', 'slug' => 'toyota']);

        $this->actingAs($admin)
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'name' => 'Corolla',
                'new_family_name' => 'Corolla',
                'engine_types' => ['1.5 Petrol', '2.0 Petrol'],
                'engine_types_text' => "2.0 Petrol\n1.8 Petrol",
                'production_start_year' => 2018,
                'production_end_year' => 2024,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', __('Vehicle model and engine types created.'));

        $model = VehicleModel::query()->where('name', 'Corolla')->firstOrFail();

        $this->assertSame(2018, $model->production_start_year);
        $this->assertSame(2024, $model->production_end_year);

        $this->assertSame(
            ['1.5 Petrol', '1.8 Petrol', '2.0 Petrol'],
            $model->engineTypes()->pluck('name')->all(),
        );
    }

    public function test_engine_types_can_be_replaced_when_editing_a_model(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Torres',
            'slug' => 'torres',
        ]);
        $model->engineTypes()->createMany([
            ['name' => '1.5 Petrol'],
            ['name' => '2.0 Petrol'],
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.vehicle-fitments.models.update', $model), [
                'name' => 'Torres',
                'engine_types_text' => '1.5 Petrol; 2.0 Turbo Petrol',
                'production_start_year' => 2023,
                'production_end_year' => 2026,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Torres', $model->fresh()->name);
        $this->assertSame(2023, $model->fresh()->production_start_year);
        $this->assertSame(2026, $model->fresh()->production_end_year);
        $this->assertSame(['1.5 Petrol', '2.0 Turbo Petrol'], $model->engineTypes()->pluck('name')->all());
    }

    public function test_no_more_than_twenty_engine_types_can_be_saved(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'Honda', 'slug' => 'honda']);

        $this->actingAs($admin)
            ->from(route('admin.vehicle-fitments.index'))
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'name' => 'Civic',
                'engine_types_text' => collect(range(1, 21))->map(fn ($number) => "Engine {$number}")->implode(','),
            ])
            ->assertRedirect(route('admin.vehicle-fitments.index'))
            ->assertSessionHasErrors('engine_types');

        $this->assertDatabaseMissing('vehicle_models', ['name' => 'Civic']);
    }

    public function test_engine_types_are_rendered_with_the_vehicle_model(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'Nissan', 'slug' => 'nissan']);
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'X-Trail',
            'slug' => 'x-trail',
        ]);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'X-Trail',
            'slug' => 'x-trail',
            'production_start_year' => 2021,
            'production_end_year' => 2025,
        ]);
        $model->engineTypes()->create(['name' => 'e-POWER']);

        $this->actingAs($admin)
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->assertSee('X-Trail')
            ->assertSee('e-POWER')
            ->assertSee('2021–2025')
            ->assertSee('data-engine-tags', false)
            ->assertDontSee('vf-engine-types-list', false);
    }

    public function test_model_end_year_cannot_be_before_start_year(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'Mazda', 'slug' => 'mazda']);

        $this->actingAs($admin)
            ->from(route('admin.vehicle-fitments.index'))
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'name' => 'CX-5',
                'production_start_year' => 2024,
                'production_end_year' => 2020,
            ])
            ->assertRedirect(route('admin.vehicle-fitments.index'))
            ->assertSessionHasErrors('production_end_year');

        $this->assertDatabaseMissing('vehicle_models', ['name' => 'CX-5']);
    }

    public function test_diesel_engine_type_is_stored_with_its_parsed_fuel_details(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->actingAs($admin)
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'name_en' => 'Kyron',
                'engine_types_text' => '2.0 Diesel',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehicle_model_engine_types', [
            'name' => '2.0 Diesel',
            'fuel_type' => 'diesel',
            'engine_size' => '2.0',
        ]);
    }

    public function test_structured_engines_are_stored_for_each_canonical_fuel_type(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->actingAs($admin)
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'name_en' => 'Torres',
                'engines' => [
                    ['fuel_type' => 'petrol', 'engine_size' => '1.5', 'aspiration' => 'turbo'],
                    ['fuel_type' => 'diesel', 'engine_size' => '2.2'],
                    ['fuel_type' => 'hybrid', 'engine_size' => '1.6'],
                    ['fuel_type' => 'electric'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehicle_model_engine_types', ['name' => '1.5 Turbo Petrol', 'fuel_type' => 'petrol']);
        $this->assertDatabaseHas('vehicle_model_engine_types', ['name' => '2.2 Diesel', 'fuel_type' => 'diesel']);
        $this->assertDatabaseHas('vehicle_model_engine_types', ['name' => '1.6 Hybrid', 'fuel_type' => 'hybrid']);
        $this->assertDatabaseHas('vehicle_model_engine_types', ['name' => 'Electric', 'fuel_type' => 'electric']);
    }

    public function test_electric_engine_never_stores_a_displacement(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->actingAs($admin)
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'name_en' => 'Torres EVX',
                // A stale value left in the form must not be saved as a fake size.
                'engines' => [['fuel_type' => 'electric', 'engine_size' => '2.0', 'aspiration' => 'turbo']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehicle_model_engine_types', [
            'name' => 'Electric',
            'fuel_type' => 'electric',
            'engine_size' => null,
            'aspiration' => null,
        ]);
    }

    public function test_unknown_fuel_type_is_rejected(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->actingAs($admin)
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'name_en' => 'Kyron',
                'engines' => [['fuel_type' => 'hydrogen', 'engine_size' => '2.0']],
            ])
            ->assertSessionHasErrors('engines.0.fuel_type');

        $this->assertDatabaseMissing('vehicle_models', ['name' => 'Kyron']);
    }
}
