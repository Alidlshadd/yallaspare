<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Engines were free text, so a "2.0 Turbo Petrol" could not be filtered or
     * translated. These columns hold the parts separately; `name` stays as the
     * display text so nothing that already reads it breaks.
     */
    public function up(): void
    {
        Schema::table('vehicle_model_engine_types', function (Blueprint $table): void {
            $table->string('fuel_type', 16)->nullable()->after('name')->index();
            $table->decimal('engine_size', 3, 1)->nullable()->after('fuel_type');
            $table->string('aspiration', 16)->nullable()->after('engine_size');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_model_engine_types', function (Blueprint $table): void {
            $table->dropIndex(['fuel_type']);
            $table->dropColumn(['fuel_type', 'engine_size', 'aspiration']);
        });
    }
};
