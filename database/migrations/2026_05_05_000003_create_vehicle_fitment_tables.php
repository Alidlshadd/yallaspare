<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('vehicle_models', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_brand_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['vehicle_brand_id', 'slug']);
            $table->index('name');
        });

        Schema::create('product_vehicle_fitments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->string('engine')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'vehicle_brand_id']);
            $table->index(['vehicle_brand_id', 'vehicle_model_id']);
            $table->index(['year_from', 'year_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_vehicle_fitments');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_brands');
    }
};
