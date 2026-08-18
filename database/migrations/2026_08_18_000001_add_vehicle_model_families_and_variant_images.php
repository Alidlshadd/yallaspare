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
        Schema::create('vehicle_model_families', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_brand_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->timestamps();

            $table->unique(['vehicle_brand_id', 'slug']);
            $table->index('name');
        });

        Schema::table('vehicle_models', function (Blueprint $table): void {
            $table->foreignId('vehicle_model_family_id')
                ->nullable()
                ->after('vehicle_brand_id')
                ->constrained('vehicle_model_families')
                ->restrictOnDelete();
            $table->string('image_path')->nullable()->after('production_end_year');
            $table->index(['vehicle_model_family_id', 'name']);
        });

        $now = now();
        DB::table('vehicle_models')
            ->select(['id', 'vehicle_brand_id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $model) use ($now): void {
                $baseSlug = Str::slug((string) $model->name) ?: 'family-'.$model->id;
                $slug = $baseSlug;
                $suffix = 2;

                while (DB::table('vehicle_model_families')
                    ->where('vehicle_brand_id', $model->vehicle_brand_id)
                    ->where('slug', $slug)
                    ->exists()) {
                    $slug = $baseSlug.'-'.$suffix++;
                }

                $familyId = DB::table('vehicle_model_families')->insertGetId([
                    'vehicle_brand_id' => $model->vehicle_brand_id,
                    'name' => $model->name,
                    'slug' => $slug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('vehicle_models')
                    ->where('id', $model->id)
                    ->update(['vehicle_model_family_id' => $familyId]);
            });
    }

    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table): void {
            $table->dropForeign(['vehicle_model_family_id']);
            $table->dropIndex(['vehicle_model_family_id', 'name']);
            $table->dropColumn(['vehicle_model_family_id', 'image_path']);
        });

        Schema::dropIfExists('vehicle_model_families');
    }
};
