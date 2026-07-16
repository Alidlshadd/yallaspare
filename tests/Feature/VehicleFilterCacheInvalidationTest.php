<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Support\VehicleFilterCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VehicleFilterCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1, 'name_en' => 'Brake Parts', 'slug' => 'brake-parts']);
        }
    }

    private function warmCache(): void
    {
        $this->get(route('user.shop.home'))->assertOk();
        $this->assertTrue(Cache::has(VehicleFilterCache::KEY));
    }

    public function test_first_home_request_stores_options_in_cache(): void
    {
        $this->assertFalse(Cache::has(VehicleFilterCache::KEY));

        $this->get(route('user.shop.home'))->assertOk();

        $this->assertTrue(Cache::has(VehicleFilterCache::KEY));
    }

    public function test_creating_vehicle_brand_flushes_cache_and_appears_immediately(): void
    {
        $this->warmCache();

        // Distinctive name: the storefront falls back to a hardcoded list of
        // common brands (Toyota, BMW, ...) when no data exists, so a common
        // name would false-positive in the rendered HTML.
        VehicleBrand::query()->create(['name' => 'Pagani', 'slug' => 'pagani']);

        $this->assertFalse(Cache::has(VehicleFilterCache::KEY));

        $this->get(route('user.shop.home'))->assertOk()->assertSee('Pagani');
    }

    public function test_creating_vehicle_model_flushes_cache_and_appears_immediately(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'Pagani', 'slug' => 'pagani']);
        $this->warmCache();

        VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Huayra',
            'slug' => 'huayra',
        ]);

        $this->assertFalse(Cache::has(VehicleFilterCache::KEY));

        $this->get(route('user.shop.home'))->assertOk()->assertSee('Huayra');
    }

    public function test_updating_vehicle_brand_flushes_cache(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        $this->warmCache();

        $brand->update(['name' => 'Toyota Motors']);

        $this->assertFalse(Cache::has(VehicleFilterCache::KEY));
    }

    public function test_deleting_vehicle_brand_flushes_cache(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        $this->warmCache();

        $brand->delete();

        $this->assertFalse(Cache::has(VehicleFilterCache::KEY));
    }

    public function test_creating_fitment_flushes_cache(): void
    {
        $product = Product::factory()->create();
        $this->warmCache();

        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'engine' => '2.0L',
        ]);

        $this->assertFalse(Cache::has(VehicleFilterCache::KEY));
    }

    public function test_changing_product_brand_flushes_cache_but_stock_change_does_not(): void
    {
        $product = Product::factory()->create(['brand' => 'Bosch']);
        $this->warmCache();

        $product->update(['stock_quantity' => 42]);
        $this->assertTrue(Cache::has(VehicleFilterCache::KEY));

        $product->update(['brand' => 'Denso']);
        $this->assertFalse(Cache::has(VehicleFilterCache::KEY));
    }
}
