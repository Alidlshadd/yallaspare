<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('session_id', 40)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['product_id', 'viewed_at'], 'product_views_product_time_idx');
            $table->index(['product_id', 'session_id', 'viewed_at'], 'product_views_product_session_time_idx');
            $table->index(['product_id', 'user_id', 'viewed_at'], 'product_views_product_user_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};
