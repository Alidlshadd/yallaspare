<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('products')) {
            return;
        }

        if ($this->indexExists('products', 'products_search_fulltext_idx')) {
            return;
        }

        // Run this migration during a low-traffic window.
        // For very large tables, prefer pt-online-schema-change/gh-ost.
        try {
            DB::statement(
                "ALTER TABLE `products` " .
                "ALGORITHM=INPLACE, LOCK=NONE, " .
                "ADD FULLTEXT INDEX `products_search_fulltext_idx` (`sku`, `name_en`, `name_ar`, `name_ku`, `brand`)"
            );
        } catch (\Throwable $e) {
            // Fallback for servers that reject explicit algorithm/lock hints.
            DB::statement(
                "ALTER TABLE `products` " .
                "ADD FULLTEXT INDEX `products_search_fulltext_idx` (`sku`, `name_en`, `name_ar`, `name_ku`, `brand`)"
            );
        }
    }

    public function down(): void
    {
        // Do not rollback in production.
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('products') || !$this->indexExists('products', 'products_search_fulltext_idx')) {
            return;
        }

        DB::statement("ALTER TABLE `products` DROP INDEX `products_search_fulltext_idx`");
    }

    private function indexExists(string $table, string $indexName): bool
    {
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
