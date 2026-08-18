<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleVariantPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_renders_with_the_four_fuel_types(): void
    {
        $this->brandWithFamily();

        $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.models.create'))
            ->assertOk()
            ->assertSeeText('Create Vehicle Variant')
            ->assertSeeText('Petrol')
            ->assertSeeText('Diesel')
            ->assertSeeText('Hybrid')
            ->assertSeeText('Electric');
    }

    public function test_edit_page_shows_the_variant_and_its_engines(): void
    {
        $model = $this->variant();
        $model->engineTypes()->create(['name' => '2.0 Turbo Petrol', 'fuel_type' => 'petrol', 'engine_size' => 2.0, 'aspiration' => 'turbo']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.models.edit', $model))
            ->assertOk()
            ->assertSeeText('Rexton W');

        // The stored engine comes back as selected form values, not as text.
        $this->assertStringContainsString('value="petrol" data-has-displacement="1" selected', $response->getContent());
        $this->assertStringContainsString('value="2.0"', $response->getContent());
    }

    public function test_edit_page_preselects_the_current_model_family(): void
    {
        $model = $this->variant();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.models.edit', $model))
            ->assertOk();

        $this->assertStringContainsString(
            'value="'.$model->vehicle_model_family_id.'" selected',
            $response->getContent(),
        );
    }

    public function test_variant_pages_require_the_product_permission(): void
    {
        $model = $this->variant();
        $customer = User::factory()->create(['role' => User::ROLE_USER, 'email_verified_at' => now()]);

        $this->actingAs($customer)->get(route('admin.vehicle-fitments.models.create'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.vehicle-fitments.models.edit', $model))->assertForbidden();
    }

    public function test_variant_pages_are_closed_to_guests(): void
    {
        $model = $this->variant();

        $this->get(route('admin.vehicle-fitments.models.create'))->assertRedirect(route('login'));
        $this->get(route('admin.vehicle-fitments.models.edit', $model))->assertRedirect(route('login'));
    }

    public function test_creating_a_variant_returns_to_the_index_with_the_engines_saved(): void
    {
        $brand = $this->brandWithFamily();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'new_family_name_en' => 'Torres',
                'name_en' => 'Torres EVX',
                'engines' => [
                    ['fuel_type' => 'petrol', 'engine_size' => '1.5', 'aspiration' => 'turbo'],
                    ['fuel_type' => 'electric'],
                ],
            ])
            ->assertRedirect(route('admin.vehicle-fitments.index'));

        $model = VehicleModel::query()->where('name', 'Torres EVX')->firstOrFail();
        $this->assertSame(
            ['1.5 Turbo Petrol', 'Electric'],
            $model->engineTypes()->orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_editing_replaces_the_engine_list(): void
    {
        $model = $this->variant();
        $model->engineTypes()->create(['name' => '3.2 Petrol', 'fuel_type' => 'petrol', 'engine_size' => 3.2]);

        $this->actingAs($this->admin())
            ->patch(route('admin.vehicle-fitments.models.update', $model), [
                'vehicle_model_family_id' => $model->vehicle_model_family_id,
                'name_en' => 'Rexton W',
                'engines' => [['fuel_type' => 'diesel', 'engine_size' => '2.2']],
            ])
            ->assertRedirect(route('admin.vehicle-fitments.index'));

        $this->assertSame(['2.2 Diesel'], $model->engineTypes()->pluck('name')->all());
    }

    public function test_an_svg_upload_is_rejected(): void
    {
        Storage::fake('public');
        $brand = $this->brandWithFamily();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'new_family_name_en' => 'Musso',
                'name_en' => 'Musso',
                'image' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('vehicle_models', ['name' => 'Musso']);
    }

    public function test_a_webp_upload_is_accepted(): void
    {
        Storage::fake('public');
        $brand = $this->brandWithFamily();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-fitments.models.store'), [
                'vehicle_brand_id' => $brand->id,
                'new_family_name_en' => 'Musso',
                'name_en' => 'Musso',
                'image' => UploadedFile::fake()->image('musso.webp', 800, 600),
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull(VehicleModel::query()->where('name', 'Musso')->value('image_path'));
    }

    public function test_the_index_links_to_the_variant_pages_instead_of_inlining_the_form(): void
    {
        $model = $this->variant();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk();

        $response->assertSee(route('admin.vehicle-fitments.models.create'), false);
        $response->assertSee(route('admin.vehicle-fitments.models.edit', $model), false);
        $response->assertDontSee('data-vf-edit-panel', false);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function brandWithFamily(): VehicleBrand
    {
        $brand = VehicleBrand::query()->create(['name' => 'SSANGYONG / KGM', 'slug' => 'ssangyong-kgm']);
        VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Rexton',
            'slug' => 'rexton',
        ]);

        return $brand;
    }

    private function variant(): VehicleModel
    {
        $brand = $this->brandWithFamily();

        return VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $brand->modelFamilies()->value('id'),
            'name' => 'Rexton W',
            'name_en' => 'Rexton W',
            'slug' => 'rexton-w',
        ]);
    }
}
