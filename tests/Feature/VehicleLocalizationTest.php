<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use App\Support\VehicleLocalization;
use Database\Seeders\SsangYongVehicleHierarchySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Category::factory()->create(['id' => 1, 'name_en' => 'Parts', 'name_ar' => 'قطع', 'name_ku' => 'پارچە', 'slug' => 'parts']);
    }

    public function test_family_and_variant_resolve_all_locales_and_fall_back_to_english(): void
    {
        [$family, $variant] = $this->vehicle();

        $this->assertSame('Rexton', $family->localizedName('en'));
        $this->assertSame('ريكستون', $family->localizedName('ar'));
        $this->assertSame('ڕێکستۆن', $family->localizedName('ku'));
        $this->assertSame('Rexton W', $variant->localizedName('en'));
        $this->assertSame('ريكستون W', $variant->localizedName('ar'));
        $this->assertSame('ڕێکستۆن W', $variant->localizedName('ku'));

        $variant->update(['name_ar' => null, 'name_ku' => '']);
        $this->assertSame('Rexton W', $variant->fresh()->localizedName('ar'));
        $this->assertSame('Rexton W', $variant->fresh()->localizedName('ku'));
    }

    public function test_petrol_engine_terms_are_localized_without_changing_displacement_or_storage_value(): void
    {
        [, $variant] = $this->vehicle();
        $engine = $variant->engineTypes()->create(['name' => '2.0 Turbo Petrol']);

        $this->assertSame('2.0 Turbo Petrol', $engine->name);
        $this->assertSame('2.0 Turbo Petrol', $engine->localizedName('en'));
        $this->assertSame('2.0 تيربو بنزين', $engine->localizedName('ar'));
        $this->assertSame('2.0 تۆربۆ بەنزین', $engine->localizedName('ku'));
        $this->assertSame('3.2 بنزين', VehicleLocalization::engine('3.2 Petrol', 'ar'));
    }

    public function test_localizing_records_preserves_variant_ids_and_existing_fitment_relationships(): void
    {
        [$family, $variant, $brand] = $this->vehicle();
        $product = Product::factory()->create(['is_active' => true]);
        $fitment = ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $variant->id,
            'engine' => '3.2 Petrol',
        ]);
        $familyId = $family->id;
        $variantId = $variant->id;
        $fitmentId = $fitment->id;

        $family->update(['name_ar' => 'ريكستون محدث']);
        $variant->update(['name_ku' => 'ڕێکستۆن W نوێ']);

        $this->assertSame($familyId, $family->fresh()->id);
        $this->assertSame('rexton', $family->fresh()->slug);
        $this->assertSame($variantId, $variant->fresh()->id);
        $this->assertSame('rexton-w', $variant->fresh()->slug);
        $this->assertSame($fitmentId, $fitment->fresh()->id);
        $this->assertSame($variantId, $fitment->fresh()->vehicle_model_id);
    }

    public function test_ssangyong_seed_populates_translations_without_diesel_or_new_records_per_locale(): void
    {
        $this->seed(SsangYongVehicleHierarchySeeder::class);

        $brand = VehicleBrand::query()->where('name', 'SSANGYONG / KGM')->firstOrFail();
        $rexton = $brand->modelFamilies()->where('name', 'Rexton')->firstOrFail();
        $tivoliAir = $brand->models()->where('name', 'Tivoli Air / XLV')->firstOrFail();

        $this->assertSame(8, $brand->modelFamilies()->count());
        $this->assertSame(18, $brand->models()->count());
        $this->assertSame('ريكستون', $rexton->name_ar);
        $this->assertSame('ڕێکستۆن', $rexton->name_ku);
        $this->assertSame('تيفولي إير / XLV', $tivoliAir->name_ar);
        $this->assertSame('تێڤۆلی ئەیر / XLV', $tivoliAir->name_ku);
        $this->assertFalse($brand->models()->whereHas('engineTypes', fn ($query) => $query->where('name', 'like', '%Diesel%'))->exists());
    }

    public function test_admin_vehicle_data_and_selectors_use_current_locale_while_submitting_stable_values(): void
    {
        [$family, $variant] = $this->vehicle();
        $variant->engineTypes()->create(['name' => '3.2 Petrol']);

        $this->actingAs($this->admin())
            ->withSession(['locale' => 'ar'])
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->assertSeeText('ريكستون')
            ->assertSeeText('ريكستون W')
            ->assertSeeText('3.2 بنزين')
            ->assertSee('"value":"3.2 Petrol"', false)
            ->assertSee('name="name_en"', false)
            ->assertSee('name="name_ar"', false)
            ->assertSee('name="name_ku"', false);

        $this->assertSame($family->id, $variant->fresh()->vehicle_model_family_id);
    }

    public function test_product_detail_localizes_family_variant_and_engine_and_keeps_rtl_layout(): void
    {
        [, $variant, $brand] = $this->vehicle();
        $product = Product::factory()->create(['is_active' => true]);
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $variant->id,
            'year_from' => 2012,
            'year_to' => 2017,
            'engine' => '3.2 Petrol',
        ]);

        $this->withSession(['locale' => 'ar'])
            ->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('ريكستون')
            ->assertSeeText('ريكستون W')
            ->assertSeeText('3.2 بنزين')
            ->assertSee('dir="rtl"', false);

        $this->withSession(['locale' => 'ku'])
            ->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('ڕێکستۆن')
            ->assertSeeText('ڕێکستۆن W')
            ->assertSeeText('3.2 بەنزین');
    }

    public function test_mobile_api_localizes_name_and_adds_translations_without_removing_legacy_fields(): void
    {
        [, $variant, $brand] = $this->vehicle();
        $variant->engineTypes()->create(['name' => '3.2 Petrol']);
        $product = Product::factory()->create(['is_active' => true]);
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $variant->id,
            'engine' => '3.2 Petrol',
        ]);

        $this->withHeader('Accept-Language', 'ar-IQ')
            ->getJson('/api/mobile/vehicle-fitments')
            ->assertOk()
            ->assertJsonPath('data.0.families.0.name', 'ريكستون')
            ->assertJsonPath('data.0.families.0.name_en', 'Rexton')
            ->assertJsonPath('data.0.families.0.variants.0.name_ar', 'ريكستون W')
            ->assertJsonPath('data.0.families.0.variants.0.engines.0', '3.2 بنزين')
            ->assertJsonPath('data.0.families.0.variants.0.engine_values.0', '3.2 Petrol')
            ->assertJsonPath('data.0.models.0.id', $variant->id);

        $this->withHeader('Accept-Language', 'ckb-IQ')
            ->getJson('/api/mobile/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.vehicle_fitments.0.family', 'ڕێکستۆن')
            ->assertJsonPath('data.vehicle_fitments.0.variant', 'ڕێکستۆن W')
            ->assertJsonPath('data.vehicle_fitments.0.engine', '3.2 Petrol')
            ->assertJsonPath('data.vehicle_fitments.0.engine_label', '3.2 بەنزین');
    }

    public function test_no_my_garage_dependency_is_added(): void
    {
        $this->assertFalse(Schema::hasTable('garages'));
        $this->assertFalse(Schema::hasTable('user_vehicles'));
        $this->assertFalse(collect(Route::getRoutes())->contains(fn ($route) => str_contains(strtolower((string) $route->getName()), 'garage')));
    }

    /** @return array{VehicleModelFamily, VehicleModel, VehicleBrand} */
    private function vehicle(): array
    {
        $brand = VehicleBrand::query()->create(['name' => 'SSANGYONG / KGM', 'slug' => 'ssangyong-kgm']);
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Rexton',
            'name_en' => 'Rexton',
            'name_ar' => 'ريكستون',
            'name_ku' => 'ڕێکستۆن',
            'slug' => 'rexton',
        ]);
        $variant = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'Rexton W',
            'name_en' => 'Rexton W',
            'name_ar' => 'ريكستون W',
            'name_ku' => 'ڕێکستۆن W',
            'slug' => 'rexton-w',
        ]);

        return [$family, $variant, $brand];
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }
}
