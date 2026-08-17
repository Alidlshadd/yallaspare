<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_brands')) {
            return;
        }

        if (! Schema::hasColumn('product_brands', 'logo_path')) {
            Schema::table('product_brands', function (Blueprint $table): void {
                $table->string('logo_path')->nullable()->after('country_code');
            });
        }

        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'product_brand_id')) {
            return;
        }

        DB::table('products')
            ->select('brand')
            ->whereNotNull('brand')
            ->where('brand', '<>', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->each(function ($rawName): void {
                $name = trim((string) $rawName);
                if ($name === '') {
                    return;
                }

                $brand = DB::table('product_brands')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->first();

                if (! $brand) {
                    $baseSlug = Str::slug($name) ?: 'brand';
                    $slug = $baseSlug;
                    $suffix = 2;

                    while (DB::table('product_brands')->where('slug', $slug)->exists()) {
                        $slug = $baseSlug.'-'.$suffix++;
                    }

                    $brandId = DB::table('product_brands')->insertGetId([
                        'name' => $name,
                        'slug' => $slug,
                        'country_code' => null,
                        'logo_path' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ]);
                } else {
                    $brandId = (int) $brand->id;

                    if (property_exists($brand, 'deleted_at') && $brand->deleted_at !== null) {
                        DB::table('product_brands')->where('id', $brandId)->update([
                            'deleted_at' => null,
                            'updated_at' => now(),
                        ]);
                    }
                }

                DB::table('products')
                    ->whereNull('product_brand_id')
                    ->where('brand', $rawName)
                    ->update(['product_brand_id' => $brandId]);
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('product_brands') && Schema::hasColumn('product_brands', 'logo_path')) {
            Schema::table('product_brands', function (Blueprint $table): void {
                $table->dropColumn('logo_path');
            });
        }
    }
};
