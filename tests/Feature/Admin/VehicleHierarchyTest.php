<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use Database\Seeders\SsangYongVehicleHierarchySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Category::factory()->create(['id' => 1, 'name_en' => 'Parts', 'slug' => 'parts']);
    }

    public function test_ssangyong_seed_builds_required_families_and_variants_without_inventing_years_or_engines(): void
    {
        $this->seed(SsangYongVehicleHierarchySeeder::class);

        $brand = VehicleBrand::query()->where('name', 'SSANGYONG / KGM')->firstOrFail();
        $rexton = $brand->modelFamilies()->where('name', 'Rexton')->firstOrFail();

        $this->assertSame(
            ['Rexton', 'Rexton G4', 'Rexton II', 'Rexton W'],
            $rexton->variants()->pluck('name')->all(),
        );
        $this->assertSame(8, $brand->modelFamilies()->count());
        $this->assertSame(18, $brand->models()->count());
        $this->assertSame(0, $brand->models()->whereHas('engineTypes')->count());
        $this->assertSame(0, $brand->models()->whereNotNull('production_start_year')->count());
        $this->assertSame(0, $brand->models()->whereNotNull('production_end_year')->count());
    }

    public function test_family_contains_multiple_variants_with_optional_years_and_petrol_engines(): void
    {
        [$brand, $family] = $this->family('Rexton');
        $plain = $this->variant($brand, $family, 'Rexton');
        $g4 = $this->variant($brand, $family, 'Rexton G4', 2017, 2020);
        $g4->engineTypes()->createMany([
            ['name' => '2.0 Petrol'],
            ['name' => '2.0 Turbo Petrol'],
        ]);

        $this->assertCount(2, $family->variants);
        $this->assertNull($plain->production_start_year);
        $this->assertSame(2017, $g4->production_start_year);
        $this->assertSame(['2.0 Petrol', '2.0 Turbo Petrol'], $g4->engineTypes()->pluck('name')->all());
        $this->assertNull($plain->image_path);
    }

    public function test_valid_optional_variant_image_upload_works(): void
    {
        Storage::fake('public');
        [$brand, $family] = $this->family('Rexton');

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'vehicle_model_family_id' => $family->id,
                'name' => 'Rexton W',
                'image' => UploadedFile::fake()->image('rexton-w.jpg', 1200, 800),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $variant = VehicleModel::query()->where('name', 'Rexton W')->firstOrFail();
        $this->assertNotNull($variant->image_path);
        Storage::disk('public')->assertExists($variant->image_path);
    }

    public function test_invalid_and_svg_variant_images_are_rejected(): void
    {
        Storage::fake('public');
        [$brand, $family] = $this->family('Rexton');
        $payload = [
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'Rexton W',
        ];

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), $payload + [
                'image' => UploadedFile::fake()->create('vehicle.txt', 5, 'text/plain'),
            ])
            ->assertSessionHasErrors('image');

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), $payload + [
                'image' => UploadedFile::fake()->createWithContent('vehicle.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('vehicle_models', ['name' => 'Rexton W']);
    }

    public function test_variant_image_can_be_replaced_and_removed_safely(): void
    {
        Storage::fake('public');
        [$brand, $family] = $this->family('Rexton');
        Storage::disk('public')->put('vehicle-variants/old.jpg', 'old-image');
        $variant = $this->variant($brand, $family, 'Rexton W');
        $variant->update(['image_path' => 'vehicle-variants/old.jpg']);

        $this->actingAs($this->admin())
            ->patch(route('admin.vehicle-fitments.models.update', $variant), [
                'vehicle_model_family_id' => $family->id,
                'name' => 'Rexton W',
                'image' => UploadedFile::fake()->image('replacement.jpg', 1000, 700),
            ])
            ->assertSessionHasNoErrors();

        $replacementPath = $variant->fresh()->image_path;
        $this->assertNotSame('vehicle-variants/old.jpg', $replacementPath);
        Storage::disk('public')->assertMissing('vehicle-variants/old.jpg');
        Storage::disk('public')->assertExists($replacementPath);

        $this->actingAs($this->admin())
            ->patch(route('admin.vehicle-fitments.models.update', $variant), [
                'vehicle_model_family_id' => $family->id,
                'name' => 'Rexton W',
                'remove_image' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($variant->fresh()->image_path);
        Storage::disk('public')->assertMissing($replacementPath);
    }

    public function test_existing_fitment_keeps_same_variant_when_family_changes(): void
    {
        [$brand, $rexton] = $this->family('Rexton');
        $variant = $this->variant($brand, $rexton, 'Rexton W');
        $product = Product::factory()->create();
        $fitment = ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $variant->id,
        ]);
        $otherFamily = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Rexton Archive',
            'slug' => 'rexton-archive',
        ]);

        $variant->update(['vehicle_model_family_id' => $otherFamily->id]);

        $this->assertSame($variant->id, $fitment->fresh()->vehicle_model_id);
        $this->assertSame($otherFamily->id, $fitment->fresh()->model->family->id);
    }

    public function test_product_can_fit_multiple_variants_and_admin_ui_supports_light_and_dark_modes(): void
    {
        [$brand, $family] = $this->family('Rexton');
        $first = $this->variant($brand, $family, 'Rexton II');
        $second = $this->variant($brand, $family, 'Rexton W');
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.store'), [
                'product_id' => $product->id,
                'fitments' => [
                    ['vehicle_brand_id' => $brand->id, 'vehicle_model_family_id' => $family->id, 'vehicle_model_id' => $first->id],
                    ['vehicle_brand_id' => $brand->id, 'vehicle_model_family_id' => $family->id, 'vehicle_model_id' => $second->id],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, $product->vehicleFitments()->count());

        $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->assertSee('bg-white', false)
            ->assertSee('dark:bg-slate-900', false)
            ->assertSeeText('Rexton II')
            ->assertSeeText('Rexton W');
    }

    public function test_no_my_garage_schema_or_route_is_introduced(): void
    {
        $this->assertFalse(Schema::hasTable('garages'));
        $this->assertFalse(Schema::hasTable('user_vehicles'));
        $this->assertFalse(collect(Route::getRoutes())->contains(fn ($route) => str_contains(strtolower((string) $route->getName()), 'garage')));
    }

    public function test_mobile_api_preserves_legacy_models_and_exposes_family_hierarchy(): void
    {
        [$brand, $family] = $this->family('Rexton');
        $variant = $this->variant($brand, $family, 'Rexton W', 2012, 2017);
        $variant->engineTypes()->create(['name' => '3.2 Petrol']);
        $product = Product::factory()->create(['is_active' => true]);
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $variant->id,
            'year_from' => 2012,
            'year_to' => 2017,
            'engine' => '3.2 Petrol',
        ]);

        $this->getJson('/api/mobile/vehicle-fitments')
            ->assertOk()
            ->assertJsonPath('data.0.families.0.name', 'Rexton')
            ->assertJsonPath('data.0.families.0.variants.0.name', 'Rexton W')
            ->assertJsonPath('data.0.models.0.name', 'Rexton W');

        $this->getJson('/api/mobile/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.vehicle_fitments.0.family', 'Rexton')
            ->assertJsonPath('data.vehicle_fitments.0.variant', 'Rexton W')
            ->assertJsonPath('data.vehicle_fitments.0.engine', '3.2 Petrol');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /** @return array{VehicleBrand, VehicleModelFamily} */
    private function family(string $name): array
    {
        $brand = VehicleBrand::query()->firstOrCreate(
            ['name' => 'SSANGYONG / KGM'],
            ['slug' => 'ssangyong-kgm'],
        );
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => $name,
            'slug' => str($name)->slug(),
        ]);

        return [$brand, $family];
    }

    private function variant(
        VehicleBrand $brand,
        VehicleModelFamily $family,
        string $name,
        ?int $yearFrom = null,
        ?int $yearTo = null,
    ): VehicleModel {
        return VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => $name,
            'slug' => str($name)->slug(),
            'production_start_year' => $yearFrom,
            'production_end_year' => $yearTo,
        ]);
    }
}
