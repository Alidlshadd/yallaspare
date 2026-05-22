<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->ensureRestrictDelete(
            table: 'order_items',
            constraint: 'order_items_product_id_foreign',
            column: 'product_id',
            referencedTable: 'products',
            referencedColumn: 'id'
        );

        $this->ensureRestrictDelete(
            table: 'price_histories',
            constraint: 'price_histories_product_id_foreign',
            column: 'product_id',
            referencedTable: 'products',
            referencedColumn: 'id'
        );

        $this->ensureRestrictDelete(
            table: 'stock_transactions',
            constraint: 'stock_transactions_product_id_foreign',
            column: 'product_id',
            referencedTable: 'products',
            referencedColumn: 'id'
        );

        // audit_logs uses polymorphic actor/subject columns, so there are no DB FKs to harden.
    }

    public function down(): void
    {
        // Do not rollback in production.
        // Historical FK hardening is intentionally one-way for data safety.
    }

    private function ensureRestrictDelete(
        string $table,
        string $constraint,
        string $column,
        string $referencedTable,
        string $referencedColumn
    ): void {
        if (!Schema::hasTable($table) || !Schema::hasTable($referencedTable) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $currentDeleteRule = $this->getForeignDeleteRule($table, $constraint);
        if ($currentDeleteRule === null) {
            return;
        }

        if (strtoupper($currentDeleteRule) === 'RESTRICT') {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        DB::statement(
            "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` " .
            "FOREIGN KEY (`{$column}`) REFERENCES `{$referencedTable}`(`{$referencedColumn}`) " .
            "ON DELETE RESTRICT ON UPDATE CASCADE"
        );
    }

    private function getForeignDeleteRule(string $table, string $constraint): ?string
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $row = DB::selectOne(
            'SELECT DELETE_RULE AS delete_rule
             FROM information_schema.referential_constraints
             WHERE constraint_schema = ?
               AND table_name = ?
               AND constraint_name = ?
             LIMIT 1',
            [$database, $table, $constraint]
        );

        return $row?->delete_rule;
    }
};
