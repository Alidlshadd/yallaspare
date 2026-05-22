<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_reviews')) {
            return;
        }

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'user_id'], 'product_reviews_product_user_unique');
            $table->index(['product_id', 'is_approved', 'rating'], 'product_reviews_product_status_rating_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
