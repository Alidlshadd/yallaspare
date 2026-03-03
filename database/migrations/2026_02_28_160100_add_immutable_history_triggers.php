<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createImmutableTriggers('price_histories', 'price_histories is immutable');
        $this->createImmutableTriggers('stock_transactions', 'stock_transactions is immutable');
        $this->createImmutableTriggers('audit_logs', 'audit_logs is immutable');
        $this->createImmutableTriggers('order_status_histories', 'order_status_histories is immutable');
    }

    public function down(): void
    {
        $this->dropTriggerIfExists('trg_price_histories_block_update');
        $this->dropTriggerIfExists('trg_price_histories_block_delete');
        $this->dropTriggerIfExists('trg_stock_transactions_block_update');
        $this->dropTriggerIfExists('trg_stock_transactions_block_delete');
        $this->dropTriggerIfExists('trg_audit_logs_block_update');
        $this->dropTriggerIfExists('trg_audit_logs_block_delete');
        $this->dropTriggerIfExists('trg_order_status_histories_block_update');
        $this->dropTriggerIfExists('trg_order_status_histories_block_delete');
    }

    private function createImmutableTriggers(string $table, string $message): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $updateTrigger = "trg_{$table}_block_update";
        $deleteTrigger = "trg_{$table}_block_delete";

        if (!$this->triggerExists($updateTrigger)) {
            DB::unprepared(
                "CREATE TRIGGER `{$updateTrigger}` BEFORE UPDATE ON `{$table}` FOR EACH ROW " .
                "BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'; END"
            );
        }

        if (!$this->triggerExists($deleteTrigger)) {
            DB::unprepared(
                "CREATE TRIGGER `{$deleteTrigger}` BEFORE DELETE ON `{$table}` FOR EACH ROW " .
                "BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'; END"
            );
        }
    }

    private function dropTriggerIfExists(string $triggerName): void
    {
        if (!$this->triggerExists($triggerName)) {
            return;
        }

        DB::unprepared("DROP TRIGGER `{$triggerName}`");
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
