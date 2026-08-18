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
use Tests\TestCase;

class ProductFitmentBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create([
            'id' => 1,
            'name_en' => 'Brake Parts',
            'slug' => 'brake-parts',
        ]);
    }

    public function test_multiple_vehicle_fitments_are_created_for_one_product_at_once(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['is_active' => true]);
        [$toyota, $corolla] = $this->vehicle('Toyota', 'Corolla');
        [$honda, $civic] = $this->vehicle('Honda', 'Civic');
        [$ford, $focus] = $this->vehicle('Ford', 'Focus');
        $corolla->engineTypes()->create(['name' => '1.8 Petrol']);
        $civic->engineTypes()->create(['name' => '1.5 Turbo Petrol']);

        $this->actingAs($admin)
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [
                    [
                        'vehicle_brand_id' => $toyota->id,
                        'vehicle_model_id' => $corolla->id,
                        'year_from' => 2018,
                        'year_to' => 2022,
                        'engine' => '1.8 Petrol',
                    ],
                    [
                        'vehicle_brand_id' => $honda->id,
                        'vehicle_model_id' => $civic->id,
                        'year_from' => 2019,
                        'year_to' => 2024,
                        'engine' => '1.5 Turbo Petrol',
                    ],
                    [
                        'vehicle_brand_id' => $ford->id,
                        'vehicle_model_id' => $focus->id,
                        'notes' => 'All engines',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', __(':count product fitments created.', ['count' => 3]));

        $this->assertSame(3, ProductVehicleFitment::query()->where('product_id', $product->id)->count());
        $this->assertDatabaseHas('product_vehicle_fitments', [
            'product_id' => $product->id,
            'vehicle_model_id' => $corolla->id,
            'engine' => '1.8 Petrol',
        ]);
        $this->assertDatabaseHas('product_vehicle_fitments', [
            'product_id' => $product->id,
            'vehicle_model_id' => $civic->id,
            'year_to' => 2024,
        ]);
        $this->assertDatabaseHas('product_vehicle_fitments', [
            'product_id' => $product->id,
            'vehicle_model_id' => $focus->id,
            'notes' => 'All engines',
        ]);
    }

    public function test_invalid_row_prevents_the_entire_batch_from_being_created(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['is_active' => true]);
        [$toyota, $corolla] = $this->vehicle('Toyota', 'Corolla');
        [$honda, $civic] = $this->vehicle('Honda', 'Civic');

        $this->actingAs($admin)
            ->from(route('admin.vehicle-fitments.index'))
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [
                    [
                        'vehicle_brand_id' => $toyota->id,
                        'vehicle_model_id' => $corolla->id,
                    ],
                    [
                        'vehicle_brand_id' => $honda->id,
                        'vehicle_model_id' => $civic->id,
                        'year_from' => 2025,
                        'year_to' => 2020,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.vehicle-fitments.index'))
            ->assertSessionHasErrors('fitments.1.year_to');

        $this->assertDatabaseCount('product_vehicle_fitments', 0);
    }

    public function test_model_must_belong_to_the_selected_brand(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['is_active' => true]);
        [$toyota] = $this->vehicle('Toyota', 'Corolla');
        [, $civic] = $this->vehicle('Honda', 'Civic');

        $this->actingAs($admin)
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [[
                    'vehicle_brand_id' => $toyota->id,
                    'vehicle_model_id' => $civic->id,
                ]],
            ])
            ->assertSessionHasErrors('fitments.0.vehicle_model_id');

        $this->assertDatabaseCount('product_vehicle_fitments', 0);
    }

    public function test_legacy_single_fitment_payload_remains_supported(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['is_active' => true]);
        [$brand, $model] = $this->vehicle('KGM', 'Actyon');
        $model->engineTypes()->create(['name' => '2.3 Petrol']);

        $this->actingAs($admin)
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_id' => $model->id,
                'year_from' => 2007,
                'year_to' => 2011,
                'engine' => '2.3 Petrol',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_vehicle_fitments', [
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'year_from' => 2007,
            'year_to' => 2011,
            'engine' => '2.3 Petrol',
        ]);
    }

    public function test_diesel_fitment_is_rejected(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        [$brand, $model] = $this->vehicle('KGM', 'Actyon');

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_id' => $model->id,
                'engine' => '2.0 Diesel',
            ])
            ->assertSessionHasErrors('fitments.0.engine');

        $this->assertDatabaseCount('product_vehicle_fitments', 0);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /** @return array{VehicleBrand, VehicleModel} */
    private function vehicle(string $brandName, string $modelName): array
    {
        $brand = VehicleBrand::query()->create([
            'name' => $brandName,
            'slug' => str($brandName)->slug(),
        ]);
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => $modelName,
            'slug' => str($modelName)->slug(),
        ]);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => $modelName,
            'slug' => str($modelName)->slug(),
        ]);

        return [$brand, $model];
    }
}
