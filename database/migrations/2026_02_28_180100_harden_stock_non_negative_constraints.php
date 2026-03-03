<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_warehouse_stocks')) {
            return;
        }

        $this->assertNoNegativeStockRows();
        $this->addCheckIfMissing(
            'product_warehouse_stocks',
            'chk_pws_available_non_negative',
            'available_quantity >= 0'
        );
        $this->addCheckIfMissing(
            'product_warehouse_stocks',
            'chk_pws_reserved_non_negative',
            'reserved_quantity >= 0'
        );
    }

    public function down(): void
    {
        // Do not rollback in production.
        // This is a data-safety hardening migration.
    }

    private function assertNoNegativeStockRows(): void
    {
        $count = (int) DB::table('product_warehouse_stocks')
            ->where('available_quantity', '<', 0)
            ->orWhere('reserved_quantity', '<', 0)
            ->count();

        if ($count > 0) {
            throw new RuntimeException("Cannot add CHECK constraints: found {$count} rows with negative stock values.");
        }
    }

    private function addCheckIfMissing(string $table, string $constraintName, string $expression): void
    {
        if ($this->checkConstraintExists($table, $constraintName)) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraintName}` CHECK ({$expression})"
        );
    }

    private function checkConstraintExists(string $table, string $constraintName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        return DB::selectOne(
            'SELECT 1
             FROM information_schema.table_constraints
             WHERE constraint_schema = ?
               AND table_name = ?
               AND constraint_name = ?
               AND constraint_type = ?
             LIMIT 1',
            [$database, $table, $constraintName, 'CHECK']
        ) !== null;
    }
};
