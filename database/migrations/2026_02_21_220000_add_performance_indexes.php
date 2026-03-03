<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['stock_quantity', 'is_active'], 'products_stock_active_idx');
            $table->index(['category_id', 'is_active'], 'products_category_active_idx');
            $table->index('created_at', 'products_created_at_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'orders_status_created_idx');
            $table->index(['user_id', 'created_at'], 'orders_user_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_idx');
            $table->index('dealer_status', 'users_dealer_status_idx');
            $table->index('is_admin', 'users_is_admin_idx');
            $table->index('created_at', 'users_created_at_idx');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'inventory_movements_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inventory_movements_user_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_idx');
            $table->dropIndex('users_dealer_status_idx');
            $table->dropIndex('users_is_admin_idx');
            $table->dropIndex('users_created_at_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_created_idx');
            $table->dropIndex('orders_user_created_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_stock_active_idx');
            $table->dropIndex('products_category_active_idx');
            $table->dropIndex('products_created_at_idx');
        });
    }
};

