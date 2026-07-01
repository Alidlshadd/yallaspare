<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('delivery_zones');
    }

    public function down(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('city', 120);
            $table->string('district', 120)->nullable();
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('free_shipping_min', 12, 2)->nullable();
            $table->unsignedSmallInteger('delivery_days_min')->default(1);
            $table->unsignedSmallInteger('delivery_days_max')->default(3);
            $table->boolean('cash_on_delivery_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['city', 'district'], 'delivery_zones_city_district_unique');
            $table->index(['is_active', 'city'], 'delivery_zones_active_city_idx');
        });
    }
};
