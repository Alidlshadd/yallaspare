<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $this->ensureOrdersSoftDeletes();
        $this->hardenOrderItemOrderFk();
        $this->hardenOrderStatusHistoryOrderFk();
        $this->ensureBlockPhysicalOrderDeleteTrigger();
    }

    public function down(): void
    {
        // Do not rollback in production.
        // This migration enforces order-history retention controls.
    }

    private function ensureOrdersSoftDeletes(): void
    {
        if (!Schema::hasColumn('orders', 'deleted_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    private function hardenOrderItemOrderFk(): void
    {
        if (!Schema::hasTable('order_items') || !Schema::hasColumn('order_items', 'order_id')) {
            return;
        }

        $constraint = 'order_items_order_id_foreign';
        $deleteRule = $this->getDeleteRule('order_items', $constraint);

        if ($deleteRule === null || strtoupper($deleteRule) === 'RESTRICT') {
            return;
        }

        DB::statement("ALTER TABLE `order_items` DROP FOREIGN KEY `{$constraint}`");
        DB::statement(
            "ALTER TABLE `order_items` ADD CONSTRAINT `{$constraint}` " .
            "FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) " .
            "ON DELETE RESTRICT ON UPDATE CASCADE"
        );
    }

    private function hardenOrderStatusHistoryOrderFk(): void
    {
        if (!Schema::hasTable('order_status_histories') || !Schema::hasColumn('order_status_histories', 'order_id')) {
            return;
        }

        $constraint = 'order_status_histories_order_id_foreign';
        $deleteRule = $this->getDeleteRule('order_status_histories', $constraint);

        if ($deleteRule === null || strtoupper($deleteRule) === 'RESTRICT') {
            return;
        }

        DB::statement("ALTER TABLE `order_status_histories` DROP FOREIGN KEY `{$constraint}`");
        DB::statement(
            "ALTER TABLE `order_status_histories` ADD CONSTRAINT `{$constraint}` " .
            "FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) " .
            "ON DELETE RESTRICT ON UPDATE CASCADE"
        );
    }

    private function ensureBlockPhysicalOrderDeleteTrigger(): void
    {
        $triggerName = 'trg_orders_block_physical_delete';

        if ($this->triggerExists($triggerName)) {
            return;
        }

        DB::unprepared(
            "CREATE TRIGGER `{$triggerName}` BEFORE DELETE ON `orders` FOR EACH ROW " .
            "BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Physical delete blocked: use soft delete on orders'; END"
        );
    }

    private function getDeleteRule(string $table, string $constraint): ?string
    {
        $database = Schema::getConnection()->getDatabaseName();

        $row = DB::selectOne(
            'SELECT delete_rule
             FROM information_schema.referential_constraints
             WHERE constraint_schema = ?
               AND table_name = ?
               AND constraint_name = ?
             LIMIT 1',
            [$database, $table, $constraint]
        );

        return $row?->delete_rule ?? $row?->DELETE_RULE ?? null;
    }

    private function triggerExists(string $triggerName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        return DB::selectOne(
            'SELECT 1
             FROM information_schema.triggers
             WHERE trigger_schema = ?
               AND trigger_name = ?
             LIMIT 1',
            [$database, $triggerName]
        ) !== null;
    }
};
