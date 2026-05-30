<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recently_viewed_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'product_id'], 'recently_viewed_products_user_product_unique');
            $table->index(['user_id', 'viewed_at'], 'recently_viewed_products_user_viewed_idx');
        });

        Schema::create('back_in_stock_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_id'], 'back_in_stock_subscriptions_user_product_unique');
            $table->index(['product_id', 'notified_at'], 'back_in_stock_subscriptions_product_notified_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('back_in_stock_subscriptions');
        Schema::dropIfExists('recently_viewed_products');
    }
};
