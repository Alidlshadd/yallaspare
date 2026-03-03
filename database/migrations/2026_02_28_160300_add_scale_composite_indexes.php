<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_compatibilities')) {
            $this->createIndexIfMissing(
                'product_compatibilities',
                ['car_model_id', 'engine_type_id', 'vehicle_year_id', 'product_id'],
                'prod_compat_fitment_lookup_idx'
            );
            $this->createIndexIfMissing(
                'product_compatibilities',
                ['product_id', 'car_model_id', 'engine_type_id', 'vehicle_year_id'],
                'prod_compat_product_fitment_idx'
            );
        }

        if (Schema::hasTable('stock_transactions')) {
            $this->createIndexIfMissing(
                'stock_transactions',
                ['warehouse_id', 'occurred_at', 'product_id'],
                'stock_tx_wh_date_product_idx'
            );
        }

        if (Schema::hasTable('orders')) {
            $this->createIndexIfMissing(
                'orders',
                ['user_id', 'payment_status', 'created_at'],
                'orders_user_payment_created_idx'
            );
        }

        if (Schema::hasTable('price_histories')) {
            $this->createIndexIfMissing(
                'price_histories',
                ['product_id', 'changed_at', 'id'],
                'price_histories_product_date_id_idx'
            );
        }

        if (Schema::hasTable('product_warehouse_stocks')) {
            $this->createIndexIfMissing(
                'product_warehouse_stocks',
                ['warehouse_id', 'product_id'],
                'prod_wh_stock_wh_product_idx'
            );
        }
    }

    public function down(): void
    {
        // Do not rollback in production.
        $this->dropIndexIfExists('product_compatibilities', 'prod_compat_fitment_lookup_idx');
        $this->dropIndexIfExists('product_compatibilities', 'prod_compat_product_fitment_idx');
        $this->dropIndexIfExists('stock_transactions', 'stock_tx_wh_date_product_idx');
        $this->dropIndexIfExists('orders', 'orders_user_payment_created_idx');
        $this->dropIndexIfExists('price_histories', 'price_histories_product_date_id_idx');
        $this->dropIndexIfExists('product_warehouse_stocks', 'prod_wh_stock_wh_product_idx');
    }

    private function createIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $indexName) {
            $tableBlueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
            $tableBlueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        return DB::selectOne(
            'SELECT 1
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?
             LIMIT 1',
            [$database, $table, $indexName]
        ) !== null;
    }
};
