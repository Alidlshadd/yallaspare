<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table): void {
            $table->unsignedSmallInteger('production_start_year')->nullable()->after('slug');
            $table->unsignedSmallInteger('production_end_year')->nullable()->after('production_start_year');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table): void {
            $table->dropColumn(['production_start_year', 'production_end_year']);
        });
    }
};
