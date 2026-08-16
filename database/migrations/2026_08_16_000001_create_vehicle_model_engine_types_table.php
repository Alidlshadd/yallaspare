<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_model_engine_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_model_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->timestamps();

            $table->unique(['vehicle_model_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_model_engine_types');
    }
};
