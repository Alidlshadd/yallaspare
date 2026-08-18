<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Support\Garage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFitmentDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create([
            'id' => 1,
            'name_en' => 'Suspension & Steering',
            'slug' => 'suspension-steering',
        ]);
    }

    /**
     * With nothing saved the section asks rather than listing rows for the
     * customer to match by eye.
     */
    public function test_a_visitor_without_a_saved_vehicle_is_asked_to_pick_one(): void
    {
        $product = $this->hubWithFourFitments();

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Which model do you drive?')
            ->assertSeeText('Save to My Garage')
            // Every model this part is listed for is offered as a choice.
            ->assertSee('Rexton')
            ->assertSee('Actyon')
            ->assertSee('Korando Sport')
            ->assertDontSeeText('Compatibility details are available on request.');
    }

    public function test_a_saved_vehicle_inside_the_range_opens_with_a_fit(): void
    {
        $product = $this->hubWithFourFitments();
        $rexton = VehicleModel::query()->where('name', 'Rexton')->firstOrFail();

        $this->withSession([Garage::SESSION_KEY => [
            'brand_id' => $rexton->vehicle_brand_id,
            'brand' => 'SSANGYONG / KGM',
            'model_id' => $rexton->id,
            'model' => 'Rexton',
            'year' => 2012,
        ]])
            ->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Exact fit for your Rexton')
            ->assertSeeText('2007–2017')
            // The picker is replaced by the answer, not shown alongside it.
            ->assertDontSeeText('Which model do you drive?');
    }

    public function test_a_saved_vehicle_outside_the_range_is_told_so(): void
    {
        $product = $this->hubWithFourFitments();
        $rexton = VehicleModel::query()->where('name', 'Rexton')->firstOrFail();

        $this->withSession([Garage::SESSION_KEY => [
            'brand_id' => $rexton->vehicle_brand_id,
            'brand' => 'SSANGYONG / KGM',
            'model_id' => $rexton->id,
            'model' => 'Rexton',
            'year' => 2020,
        ]])
            ->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Not compatible with your Rexton')
            ->assertSeeText('This part is listed for Rexton 2007–2017.');
    }

    /**
     * A model with no year bounds covers every year, so a saved year must not
     * disqualify it.
     */
    public function test_a_model_without_year_bounds_always_fits(): void
    {
        $product = $this->hubWithFourFitments();
        $actyon = VehicleModel::query()->where('name', 'Actyon')->firstOrFail();

        $this->withSession([Garage::SESSION_KEY => [
            'brand_id' => $actyon->vehicle_brand_id,
            'brand' => 'SSANGYONG / KGM',
            'model_id' => $actyon->id,
            'model' => 'Actyon',
            'year' => 1998,
        ]])
            ->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Exact fit for your Actyon');
    }

    public function test_a_vehicle_this_part_does_not_list_is_reported_as_incompatible(): void
    {
        $product = $this->hubWithFourFitments();

        $otherBrand = VehicleBrand::query()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        $corolla = VehicleModel::query()->create([
            'vehicle_brand_id' => $otherBrand->id,
            'name' => 'Corolla',
            'slug' => 'corolla',
        ]);

        $this->withSession([Garage::SESSION_KEY => [
            'brand_id' => $otherBrand->id,
            'brand' => 'Toyota',
            'model_id' => $corolla->id,
            'model' => 'Corolla',
            'year' => 2015,
        ]])
            ->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Not compatible with your Corolla')
            ->assertSeeText('Check the models below or change your vehicle.');
    }

    public function test_saving_a_vehicle_persists_it_for_later_products(): void
    {
        $product = $this->hubWithFourFitments();
        $rexton = VehicleModel::query()->where('name', 'Rexton')->firstOrFail();

        $this->from(route('shop.show', $product))
            ->post(route('garage.store'), [
                'vehicle_model_id' => $rexton->id,
                'year' => 2012,
            ])
            ->assertRedirect(route('shop.show', $product));

        $this->assertSame($rexton->id, session(Garage::SESSION_KEY)['model_id']);
        // The name is resolved from the database, never taken from the request.
        $this->assertSame('Rexton', session(Garage::SESSION_KEY)['model']);
        $this->assertSame(2012, session(Garage::SESSION_KEY)['year']);
    }

    public function test_an_unknown_model_cannot_be_saved(): void
    {
        $this->post(route('garage.store'), ['vehicle_model_id' => 999999, 'year' => 2012])
            ->assertSessionHasErrors('vehicle_model_id');

        $this->assertNull(session(Garage::SESSION_KEY));
    }

    public function test_changing_the_vehicle_clears_it(): void
    {
        $product = $this->hubWithFourFitments();
        $rexton = VehicleModel::query()->where('name', 'Rexton')->firstOrFail();

        $this->withSession([Garage::SESSION_KEY => [
            'brand_id' => $rexton->vehicle_brand_id,
            'brand' => 'SSANGYONG / KGM',
            'model_id' => $rexton->id,
            'model' => 'Rexton',
            'year' => 2012,
        ]])
            ->from(route('shop.show', $product))
            ->delete(route('garage.destroy'))
            ->assertRedirect(route('shop.show', $product));

        $this->assertNull(session(Garage::SESSION_KEY));
    }

    public function test_legacy_compatible_models_remain_visible_without_structured_fitments(): void
    {
        $product = Product::factory()->create([
            'compatible_models' => ['Legacy Model A', 'Legacy Model B'],
        ]);

        $this->get(route('shop.show', $product))
            ->assertOk()
            ->assertSeeText('Legacy Model A')
            ->assertSeeText('Legacy Model B');
    }

    private function hubWithFourFitments(): Product
    {
        $product = Product::factory()->create([
            'name_en' => 'SsangYong Front Wheel Hub Assembly',
            'compatible_models' => null,
        ]);

        $brand = VehicleBrand::query()->create(['name' => 'SSANGYONG / KGM', 'slug' => 'ssangyong-kgm']);

        $this->fitment($product, $brand, 'Rexton', 2007, 2017, '3.2');
        $this->fitment($product, $brand, 'Korando Sport');
        $this->fitment($product, $brand, 'Actyon Sport');
        $this->fitment($product, $brand, 'Actyon');

        return $product;
    }

    private function fitment(
        Product $product,
        VehicleBrand $brand,
        string $modelName,
        ?int $yearFrom = null,
        ?int $yearTo = null,
        ?string $engine = null,
    ): void {
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => $modelName,
            'slug' => str($modelName)->slug(),
        ]);

        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'year_from' => $yearFrom,
            'year_to' => $yearTo,
            'engine' => $engine,
        ]);
    }
}
