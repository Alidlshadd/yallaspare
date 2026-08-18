<?php

namespace Database\Seeders;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SsangYongVehicleHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $catalog = (array) config('vehicle_hierarchy.ssangyong');
        $brandName = (string) ($catalog['brand'] ?? 'SSANGYONG / KGM');
        $aliases = array_values(array_unique(array_merge([$brandName], (array) ($catalog['aliases'] ?? []))));

        DB::transaction(function () use ($catalog, $brandName, $aliases): void {
            $brand = VehicleBrand::query()
                ->whereIn('name', $aliases)
                ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$brandName])
                ->first();

            $brand ??= VehicleBrand::query()->create([
                'name' => $brandName,
                'slug' => $this->uniqueBrandSlug($brandName),
            ]);

            foreach ((array) ($catalog['families'] ?? []) as $familySlug => $familyData) {
                $familyName = (string) ($familyData['name_en'] ?? $familySlug);
                $family = VehicleModelFamily::query()->firstOrCreate(
                    ['vehicle_brand_id' => $brand->id, 'name' => $familyName],
                    ['slug' => $this->uniqueFamilySlug($brand->id, $familyName)],
                );
                $family->forceFill([
                    'name_en' => $familyName,
                    'name_ar' => $familyData['name_ar'] ?? null,
                    'name_ku' => $familyData['name_ku'] ?? null,
                ])->save();

                foreach ((array) ($familyData['variants'] ?? []) as $variantSlug => $variantData) {
                    $variantName = (string) ($variantData['name_en'] ?? $variantSlug);
                    $variant = VehicleModel::query()->firstOrCreate(
                        ['vehicle_brand_id' => $brand->id, 'name' => $variantName],
                        [
                            'vehicle_model_family_id' => $family->id,
                            'slug' => $this->uniqueVariantSlug($brand->id, (string) $variantName),
                        ],
                    );

                    $variant->forceFill([
                        'name_en' => $variantName,
                        'name_ar' => $variantData['name_ar'] ?? null,
                        'name_ku' => $variantData['name_ku'] ?? null,
                    ])->save();

                    if ((int) $variant->vehicle_model_family_id !== (int) $family->id) {
                        $oldFamilyId = $variant->vehicle_model_family_id;
                        $variant->update(['vehicle_model_family_id' => $family->id]);

                        if ($oldFamilyId) {
                            VehicleModelFamily::query()
                                ->whereKey($oldFamilyId)
                                ->whereDoesntHave('variants')
                                ->delete();
                        }
                    }
                }
            }
        });
    }

    private function uniqueBrandSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'vehicle-brand';
        $slug = $base;
        $suffix = 2;
        while (VehicleBrand::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function uniqueFamilySlug(int $brandId, string $name): string
    {
        $base = Str::slug($name) ?: 'family';
        $slug = $base;
        $suffix = 2;
        while (VehicleModelFamily::query()->where('vehicle_brand_id', $brandId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function uniqueVariantSlug(int $brandId, string $name): string
    {
        $base = Str::slug($name) ?: 'variant';
        $slug = $base;
        $suffix = 2;
        while (VehicleModel::query()->where('vehicle_brand_id', $brandId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
