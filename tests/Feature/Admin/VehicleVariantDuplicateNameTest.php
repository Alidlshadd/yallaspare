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

/**
 * A variant name is not an identity.
 *
 * One name covers several cars: a Tivoli built 2015-2019 and a Tivoli built
 * 2020-2023 are different vehicles taking different parts, and the shop has to
 * hold both. The create and edit forms used to refuse the second one, which
 * left no way to record it at all.
 */
class VehicleVariantDuplicateNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_variants_of_one_brand_may_share_a_name(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->createVariant($brand, 'Tivoli', 2015, 2019)->assertSessionHasNoErrors();
        $this->createVariant($brand, 'Tivoli', 2020, 2023)->assertSessionHasNoErrors();

        $tivolis = VehicleModel::query()->where('vehicle_brand_id', $brand->id)->where('name', 'Tivoli')->get();

        $this->assertCount(2, $tivolis, 'Both Tivoli variants should exist.');
        $this->assertSame(
            [2015, 2020],
            $tivolis->pluck('production_start_year')->map(fn ($y) => (int) $y)->sort()->values()->all()
        );
    }

    public function test_the_second_variant_gets_a_slug_of_its_own_without_being_renamed(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->createVariant($brand, 'Tivoli', 2015, 2019);
        $this->createVariant($brand, 'Tivoli', 2020, 2023);

        $slugs = VehicleModel::query()->where('vehicle_brand_id', $brand->id)->pluck('slug')->sort()->values()->all();

        // The administrator kept the name they wanted; the slug made room.
        $this->assertSame(['tivoli', 'tivoli-2'], $slugs);
    }

    public function test_a_variant_can_be_renamed_to_a_name_another_one_already_uses(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        $this->createVariant($brand, 'Tivoli', 2015, 2019);
        $this->createVariant($brand, 'Tivoli XLV', 2020, 2023);

        $second = VehicleModel::query()->where('name', 'Tivoli XLV')->firstOrFail();

        $this->actingAsAdmin()
            ->patch(route('admin.vehicle-fitments.models.update', $second), [
                'name_en' => 'Tivoli',
                'production_start_year' => 2020,
                'production_end_year' => 2023,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Tivoli', $second->fresh()->name);
        $this->assertSame(2, VehicleModel::query()->where('name', 'Tivoli')->count());
    }

    public function test_the_finder_tells_two_variants_of_the_same_name_apart(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        $older = $this->variant($brand, 'Tivoli', 'tivoli', 2015, 2019);
        $newer = $this->variant($brand, 'Tivoli', 'tivoli-2', 2020, 2023);

        $oldPart = Product::factory()->create(['category_id' => $this->category()->id, 'name_en' => 'Old Tivoli Filter', 'is_active' => true]);
        $newPart = Product::factory()->create(['category_id' => $this->category()->id, 'name_en' => 'New Tivoli Filter', 'is_active' => true]);

        $this->fitment($oldPart, $brand, $older, 2015, 2019);
        $this->fitment($newPart, $brand, $newer, 2020, 2023);

        // Filtering by the variant's id picks exactly one of them.
        $this->get(route('shop.index', ['brand' => 'KGM', 'model' => $older->id]))
            ->assertOk()
            ->assertSee('Old Tivoli Filter')
            ->assertDontSee('New Tivoli Filter');

        $this->get(route('shop.index', ['brand' => 'KGM', 'model' => $newer->id]))
            ->assertOk()
            ->assertSee('New Tivoli Filter')
            ->assertDontSee('Old Tivoli Filter');
    }

    public function test_an_older_link_carrying_a_name_still_finds_the_parts(): void
    {
        // Bookmarks and the catalogue landing pages carry names, and a name now
        // belongs to more than one variant — so it matches all of them.
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        $older = $this->variant($brand, 'Tivoli', 'tivoli', 2015, 2019);
        $newer = $this->variant($brand, 'Tivoli', 'tivoli-2', 2020, 2023);

        $oldPart = Product::factory()->create(['category_id' => $this->category()->id, 'name_en' => 'Old Tivoli Filter', 'is_active' => true]);
        $newPart = Product::factory()->create(['category_id' => $this->category()->id, 'name_en' => 'New Tivoli Filter', 'is_active' => true]);

        $this->fitment($oldPart, $brand, $older, 2015, 2019);
        $this->fitment($newPart, $brand, $newer, 2020, 2023);

        $this->get(route('shop.index', ['brand' => 'KGM', 'model' => 'Tivoli']))
            ->assertOk()
            ->assertSee('Old Tivoli Filter')
            ->assertSee('New Tivoli Filter');
    }

    public function test_a_dropdown_shows_the_years_so_the_two_can_be_told_apart(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        $this->variant($brand, 'Tivoli', 'tivoli', 2015, 2019);
        $this->variant($brand, 'Tivoli', 'tivoli-2', 2020, 2023);

        $html = (string) $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Tivoli — 2015–2019', $html);
        $this->assertStringContainsString('Tivoli — 2020–2023', $html);
    }

    public function test_existing_fitments_are_untouched_by_a_rename(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        $variant = $this->variant($brand, 'Tivoli XLV', 'tivoli-xlv', 2020, 2023);
        $part = Product::factory()->create(['category_id' => $this->category()->id, 'name_en' => 'XLV Filter', 'is_active' => true]);
        $fitment = $this->fitment($part, $brand, $variant, 2020, 2023);

        $this->actingAsAdmin()
            ->patch(route('admin.vehicle-fitments.models.update', $variant), ['name_en' => 'Tivoli'])
            ->assertSessionHasNoErrors();

        $fitment->refresh();
        $this->assertSame($variant->id, (int) $fitment->vehicle_model_id);
        $this->assertSame('tivoli-xlv', $variant->fresh()->slug, 'A rename should not move the variant to a new URL.');
    }

    public function test_naming_a_family_that_already_exists_joins_it_instead_of_failing(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->actingAsAdmin()->post(route('admin.vehicle-fitments.models.store'), [
            'vehicle_brand_id' => $brand->id,
            'new_family_name_en' => 'Tivoli',
            'name_en' => 'Tivoli',
            'production_start_year' => 2015,
            'production_end_year' => 2019,
        ])->assertSessionHasNoErrors();

        // The same family name again: the second variant joins the first
        // family rather than being refused.
        $this->actingAsAdmin()->post(route('admin.vehicle-fitments.models.store'), [
            'vehicle_brand_id' => $brand->id,
            'new_family_name_en' => 'Tivoli',
            'name_en' => 'Tivoli',
            'production_start_year' => 2020,
            'production_end_year' => 2023,
        ])->assertSessionHasNoErrors();

        $families = VehicleModelFamily::query()->where('vehicle_brand_id', $brand->id)->get();

        $this->assertCount(1, $families, 'The family should have been reused, not duplicated.');
        $this->assertSame(2, VehicleModel::query()->where('vehicle_model_family_id', $families->first()->id)->count());
    }

    public function test_joining_a_family_does_not_wipe_the_translations_it_already_had(): void
    {
        // firstOrCreate hands back the existing family, and the form's Arabic
        // and Kurdish fields are usually left empty when somebody is adding a
        // variant. Writing those blanks over real translations is silent damage.
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Tivoli',
            'name_en' => 'Tivoli',
            'name_ar' => 'تيفولي',
            'name_ku' => 'تیڤۆلی',
            'slug' => 'tivoli',
        ]);

        $this->actingAsAdmin()->post(route('admin.vehicle-fitments.models.store'), [
            'vehicle_brand_id' => $brand->id,
            'new_family_name_en' => 'Tivoli',
            'name_en' => 'Tivoli',
            'production_start_year' => 2020,
            'production_end_year' => 2023,
        ])->assertSessionHasNoErrors();

        $family->refresh();

        $this->assertSame('تيفولي', $family->name_ar);
        $this->assertSame('تیڤۆلی', $family->name_ku);
    }

    public function test_a_family_can_be_renamed_to_a_name_another_family_uses(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        VehicleModelFamily::query()->create(['vehicle_brand_id' => $brand->id, 'name' => 'Tivoli', 'name_en' => 'Tivoli', 'slug' => 'tivoli']);
        $second = VehicleModelFamily::query()->create(['vehicle_brand_id' => $brand->id, 'name' => 'Korando', 'name_en' => 'Korando', 'slug' => 'korando']);

        $this->actingAsAdmin()
            ->patch(route('admin.vehicle-fitments.families.update', $second), ['name_en' => 'Tivoli'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Tivoli', $second->fresh()->name);
    }

    // ------------------------------------------------------------------ setup

    private function createVariant(VehicleBrand $brand, string $name, int $from, int $to)
    {
        return $this->actingAsAdmin()->post(route('admin.vehicle-fitments.models.store'), [
            'vehicle_brand_id' => $brand->id,
            'name_en' => $name,
            'production_start_year' => $from,
            'production_end_year' => $to,
        ]);
    }

    private function variant(VehicleBrand $brand, string $name, string $slug, int $from, int $to): VehicleModel
    {
        return VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => $name,
            'name_en' => $name,
            'slug' => $slug,
            'production_start_year' => $from,
            'production_end_year' => $to,
        ]);
    }

    private function fitment(Product $product, VehicleBrand $brand, VehicleModel $model, int $from, int $to): ProductVehicleFitment
    {
        return ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'year_from' => $from,
            'year_to' => $to,
        ]);
    }

    private function category(): Category
    {
        return Category::query()->find(1)
            ?? Category::factory()->create(['id' => 1, 'name_en' => 'Vehicle Parts', 'slug' => 'vehicle-parts']);
    }

    private function actingAsAdmin(): self
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->forceFill(['role' => User::ROLE_SUPER_ADMIN])->save();

        return $this->actingAs($admin)->withSession(['admin_2fa.verified_user_id' => $admin->id]);
    }
}
