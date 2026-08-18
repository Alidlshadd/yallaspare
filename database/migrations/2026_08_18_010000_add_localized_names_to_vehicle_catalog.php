<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_model_families', function (Blueprint $table): void {
            $table->string('name_en', 120)->nullable()->after('name');
            $table->string('name_ar', 120)->nullable()->after('name_en');
            $table->string('name_ku', 120)->nullable()->after('name_ar');
        });

        Schema::table('vehicle_models', function (Blueprint $table): void {
            $table->string('name_en', 120)->nullable()->after('name');
            $table->string('name_ar', 120)->nullable()->after('name_en');
            $table->string('name_ku', 120)->nullable()->after('name_ar');
        });

        DB::table('vehicle_model_families')
            ->whereNull('name_en')
            ->update(['name_en' => DB::raw('name')]);

        DB::table('vehicle_models')
            ->whereNull('name_en')
            ->update(['name_en' => DB::raw('name')]);
    }

    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table): void {
            $table->dropColumn(['name_en', 'name_ar', 'name_ku']);
        });

        Schema::table('vehicle_model_families', function (Blueprint $table): void {
            $table->dropColumn(['name_en', 'name_ar', 'name_ku']);
        });
    }
};
