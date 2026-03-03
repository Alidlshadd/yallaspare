<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->string('name_en');
    $table->string('name_ar');
    $table->string('name_ku');
    $table->text('description_en')->nullable();
    $table->text('description_ar')->nullable();
    $table->text('description_ku')->nullable();
    $table->decimal('price', 10, 2);
    $table->integer('stock_quantity');
    $table->string('sku')->unique();
    $table->string('brand')->nullable();
    $table->json('compatible_models')->nullable();
    $table->string('image')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
