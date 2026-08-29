<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Support\CatalogLandingCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogLandingPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1]);
        }

        CatalogLandingCache::flush();
    }

    public function test_the_brand_index_lists_brands_with_what_is_in_stock(): void
    {
        $stocked = $this->brandWithProducts('Bosch', 2);
        ProductBrand::query()->create(['name' => 'Empty Brand', 'slug' => 'empty-brand']);

        $this->get(route('catalog.brands'))
            ->assertOk()
            ->assertSee('Bosch')
            ->assertSee('2 parts')
            ->assertSee('Empty Brand')
            ->assertSee('Coming soon')
            ->assertSee(route('catalog.brand', $stocked->slug), false);
    }

    public function test_a_brand_page_shows_only_that_brands_active_products(): void
    {
        $bosch = $this->brandWithProducts('Bosch', 1);
        $ngk = $this->brandWithProducts('NGK', 1);

        $boschProduct = Product::query()->where('product_brand_id', $bosch->id)->firstOrFail();
        $ngkProduct = Product::query()->where('product_brand_id', $ngk->id)->firstOrFail();

        $this->get(route('catalog.brand', $bosch->slug))
            ->assertOk()
            ->assertSee('Bosch spare parts')
            ->assertSee($boschProduct->name_en)
            ->assertDontSee($ngkProduct->name_en);
    }

    public function test_a_brand_page_hides_products_that_are_switched_off(): void
    {
        $brand = $this->brandWithProducts('Bosch', 1);
        $hidden = $this->product($brand, ['is_active' => false, 'name_en' => 'Retired Filter']);

        $this->get(route('catalog.brand', $brand->slug))
            ->assertOk()
            ->assertDontSee($hidden->name_en);
    }

    public function test_an_unknown_brand_is_a_404_rather_than_an_empty_page(): void
    {
        $this->get('/brands/does-not-exist')->assertNotFound();
    }

    public function test_a_brand_page_with_nothing_on_it_asks_not_to_be_indexed(): void
    {
        ProductBrand::query()->create(['name' => 'Empty Brand', 'slug' => 'empty-brand']);

        $this->get(route('catalog.brand', 'empty-brand'))
            ->assertOk()
            ->assertSee('noindex,follow', false);
    }

    public function test_a_brand_page_with_products_is_indexable(): void
    {
        $brand = $this->brandWithProducts('Bosch', 1);

        $this->get(route('catalog.brand', $brand->slug))
            ->assertOk()
            ->assertDontSee('noindex', false);
    }

    public function test_the_vehicle_index_counts_only_models_that_have_parts(): void
    {
        [$make, $model] = $this->vehicleWithParts('Toyota', 'Corolla', 1);
        VehicleModel::query()->create([
            'vehicle_brand_id' => $make->id,
            'name' => 'Supra',
            'slug' => 'supra',
        ]);

        $this->get(route('catalog.vehicles'))
            ->assertOk()
            ->assertSee('Toyota')
            ->assertSee('1 model with parts')
            ->assertSee(route('catalog.vehicle-brand', $make->slug), false);

        $this->assertSame('corolla', $model->slug);
    }

    public function test_a_make_page_lists_its_models_and_the_parts_that_fit_them(): void
    {
        [$make, $model, $product] = $this->vehicleWithParts('Toyota', 'Corolla', 1);

        $this->get(route('catalog.vehicle-brand', $make->slug))
            ->assertOk()
            ->assertSee('Toyota spare parts')
            ->assertSee('Corolla')
            ->assertSee($product->name_en)
            ->assertSee(route('catalog.vehicle-model', [$make->slug, $model->slug]), false);
    }

    public function test_a_car_page_shows_only_the_parts_recorded_as_fitting_it(): void
    {
        [$make, $corolla, $fitting] = $this->vehicleWithParts('Toyota', 'Corolla', 1);

        $supra = VehicleModel::query()->create([
            'vehicle_brand_id' => $make->id,
            'name' => 'Supra',
            'slug' => 'supra',
        ]);
        $other = $this->product(null, ['name_en' => 'Supra Only Part']);
        ProductVehicleFitment::query()->create([
            'product_id' => $other->id,
            'vehicle_brand_id' => $make->id,
            'vehicle_model_id' => $supra->id,
        ]);

        $this->get(route('catalog.vehicle-model', [$make->slug, $corolla->slug]))
            ->assertOk()
            ->assertSee('Toyota Corolla spare parts')
            ->assertSee($fitting->name_en)
            ->assertDontSee($other->name_en);
    }

    public function test_a_car_page_offers_the_other_models_of_the_same_make(): void
    {
        [$make, $corolla] = $this->vehicleWithParts('Toyota', 'Corolla', 1);
        [, $camry] = $this->vehicleWithParts('Toyota', 'Camry', 1);

        $this->get(route('catalog.vehicle-model', [$make->slug, $corolla->slug]))
            ->assertOk()
            ->assertSee('Other Toyota models')
            ->assertSee(route('catalog.vehicle-model', [$make->slug, $camry->slug]), false);
    }

    public function test_a_model_slug_is_resolved_within_its_own_make(): void
    {
        [$toyota, $toyotaCorolla, $toyotaPart] = $this->vehicleWithParts('Toyota', 'Corolla', 1);
        [$honda, , $hondaPart] = $this->vehicleWithParts('Honda', 'Corolla', 1);

        $this->get(route('catalog.vehicle-model', [$honda->slug, 'corolla']))
            ->assertOk()
            ->assertSee($hondaPart->name_en)
            ->assertDontSee($toyotaPart->name_en);

        $this->assertSame('corolla', $toyotaCorolla->slug);
        $this->assertNotSame($toyota->id, $honda->id);
    }

    public function test_a_model_that_belongs_to_another_make_is_a_404(): void
    {
        $this->vehicleWithParts('Toyota', 'Corolla', 1);
        $honda = VehicleBrand::query()->create(['name' => 'Honda', 'slug' => 'honda']);

        $this->get(route('catalog.vehicle-model', [$honda->slug, 'corolla']))->assertNotFound();
    }

    public function test_every_landing_page_carries_its_breadcrumb_trail_as_structured_data(): void
    {
        [$make, $model] = $this->vehicleWithParts('Toyota', 'Corolla', 1);

        $this->get(route('catalog.vehicle-model', [$make->slug, $model->slug]))
            ->assertOk()
            ->assertSee('BreadcrumbList', false)
            ->assertSee('ItemList', false)
            ->assertSee('"position":3', false);
    }

    public function test_the_sitemap_offers_landing_pages_that_have_something_on_them(): void
    {
        $brand = $this->brandWithProducts('Bosch', 1);
        [$make, $model] = $this->vehicleWithParts('Toyota', 'Corolla', 1);
        ProductBrand::query()->create(['name' => 'Empty Brand', 'slug' => 'empty-brand']);

        $sitemap = $this->get(route('sitemap'))->assertOk();

        $sitemap->assertSee(url('/brands'), false);
        $sitemap->assertSee(url('/vehicles'), false);
        $sitemap->assertSee(url('/brands/'.$brand->slug), false);
        $sitemap->assertSee(url('/vehicles/'.$make->slug), false);
        $sitemap->assertSee(url('/vehicles/'.$make->slug.'/'.$model->slug), false);
        $sitemap->assertDontSee(url('/brands/empty-brand'), false);
    }

    public function test_a_product_page_links_to_the_landing_pages_it_belongs_to(): void
    {
        $brand = $this->brandWithProducts('Bosch', 0);
        [$make, $model] = $this->vehicleWithParts('Toyota', 'Corolla', 0);

        $product = $this->product($brand, ['name_en' => 'Front Brake Pad']);
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $make->id,
            'vehicle_model_id' => $model->id,
        ]);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSee(route('catalog.brand', $brand->slug), false)
            ->assertSee(route('catalog.vehicle-model', [$make->slug, $model->slug]), false);
    }

    public function test_the_brand_index_notices_a_product_leaving_the_catalogue(): void
    {
        $brand = $this->brandWithProducts('Bosch', 1);

        $this->get(route('catalog.brands'))->assertOk()->assertSee('1 part');

        Product::query()->where('product_brand_id', $brand->id)->firstOrFail()->update(['is_active' => false]);

        $this->get(route('catalog.brands'))->assertOk()->assertSee('Coming soon');
    }

    private function brandWithProducts(string $name, int $count): ProductBrand
    {
        $brand = ProductBrand::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
        ]);

        for ($index = 0; $index < $count; $index++) {
            $this->product($brand);
        }

        return $brand;
    }

    /**
     * @return array{0: VehicleBrand, 1: VehicleModel, 2: ?Product}
     */
    private function vehicleWithParts(string $makeName, string $modelName, int $count): array
    {
        $make = VehicleBrand::query()->firstOrCreate(
            ['slug' => str($makeName)->slug()->value()],
            ['name' => $makeName],
        );

        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $make->id,
            'name' => $modelName,
            'slug' => str($modelName)->slug()->value(),
        ]);

        $product = null;

        for ($index = 0; $index < $count; $index++) {
            $product = $this->product(null);
            ProductVehicleFitment::query()->create([
                'product_id' => $product->id,
                'vehicle_brand_id' => $make->id,
                'vehicle_model_id' => $model->id,
            ]);
        }

        return [$make, $model, $product];
    }

    private function product(?ProductBrand $brand, array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => 1,
            'product_brand_id' => $brand?->id,
            'is_active' => true,
            'stock_quantity' => 5,
        ], $attributes));
    }
}
