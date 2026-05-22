<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropFinancialTriggers();
        $this->dropFinancialOnlyColumns();
        $this->dropFinancialOnlyTables();
    }

    public function down(): void
    {
        // Do not rollback in production.
        // This simplification migration intentionally removes non-core financial-grade extras.
    }

    private function dropFinancialTriggers(): void
    {
        $triggers = [
            'trg_order_items_currency_consistency_bi',
            'trg_order_items_currency_consistency_bu',
            'trg_price_histories_financial_bi',
            'trg_stock_transactions_financial_bi',
            'trg_audit_logs_financial_bi',
            'trg_accounting_events_hash_bi',
        ];

        foreach ($triggers as $triggerName) {
            $this->dropTriggerIfExists($triggerName);
        }
    }

    private function dropFinancialOnlyColumns(): void
    {
        if (Schema::hasTable('price_histories')) {
            $this->dropIndexIfExists('price_histories', 'price_histories_current_hash_idx');
            $this->dropIndexIfExists('price_histories', 'price_histories_changed_at_idx');
            $this->dropIndexIfExists('price_histories', 'price_histories_anomaly_date_idx');
            $this->dropIndexIfExists('price_histories', 'price_histories_anomaly_score_date_idx');

            Schema::table('price_histories', function (Blueprint $table) {
                if (Schema::hasColumn('price_histories', 'previous_hash')) {
                    $table->dropColumn('previous_hash');
                }
                if (Schema::hasColumn('price_histories', 'current_hash')) {
                    $table->dropColumn('current_hash');
                }
                if (Schema::hasColumn('price_histories', 'is_anomaly')) {
                    $table->dropColumn('is_anomaly');
                }
                if (Schema::hasColumn('price_histories', 'anomaly_reason')) {
                    $table->dropColumn('anomaly_reason');
                }
                if (Schema::hasColumn('price_histories', 'anomaly_score')) {
                    $table->dropColumn('anomaly_score');
                }
            });
        }

        if (Schema::hasTable('stock_transactions')) {
            $this->dropIndexIfExists('stock_transactions', 'stock_transactions_current_hash_idx');
            $this->dropIndexIfExists('stock_transactions', 'stock_transactions_occurred_at_idx');
            $this->dropIndexIfExists('stock_transactions', 'stock_tx_anomaly_date_idx');
            $this->dropIndexIfExists('stock_transactions', 'stock_tx_bulk_adj_date_idx');

            Schema::table('stock_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('stock_transactions', 'previous_hash')) {
                    $table->dropColumn('previous_hash');
                }
                if (Schema::hasColumn('stock_transactions', 'current_hash')) {
                    $table->dropColumn('current_hash');
                }
                if (Schema::hasColumn('stock_transactions', 'is_bulk_adjustment')) {
                    $table->dropColumn('is_bulk_adjustment');
                }
                if (Schema::hasColumn('stock_transactions', 'is_anomaly')) {
                    $table->dropColumn('is_anomaly');
                }
                if (Schema::hasColumn('stock_transactions', 'anomaly_reason')) {
                    $table->dropColumn('anomaly_reason');
                }
                if (Schema::hasColumn('stock_transactions', 'anomaly_score')) {
                    $table->dropColumn('anomaly_score');
                }
                if (Schema::hasColumn('stock_transactions', 'unit_cost')) {
                    $table->dropColumn('unit_cost');
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            $this->dropIndexIfExists('audit_logs', 'audit_logs_current_hash_idx');
            $this->dropIndexIfExists('audit_logs', 'audit_logs_occurred_at_idx');

            Schema::table('audit_logs', function (Blueprint $table) {
                if (Schema::hasColumn('audit_logs', 'previous_hash')) {
                    $table->dropColumn('previous_hash');
                }
                if (Schema::hasColumn('audit_logs', 'current_hash')) {
                    $table->dropColumn('current_hash');
                }
            });
        }

        if (Schema::hasTable('admin_activity_logs')) {
            $this->dropIndexIfExists('admin_activity_logs', 'admin_activity_anomaly_date_idx');
            $this->dropIndexIfExists('admin_activity_logs', 'admin_activity_anomaly_score_date_idx');

            Schema::table('admin_activity_logs', function (Blueprint $table) {
                if (Schema::hasColumn('admin_activity_logs', 'reviewed_by')) {
                    if (DB::connection()->getDriverName() === 'sqlite') {
                        $table->dropColumn('reviewed_by');
                    } else {
                        $table->dropConstrainedForeignId('reviewed_by');
                    }
                }
                if (Schema::hasColumn('admin_activity_logs', 'reviewed_at')) {
                    $table->dropColumn('reviewed_at');
                }
                if (Schema::hasColumn('admin_activity_logs', 'anomaly_score')) {
                    $table->dropColumn('anomaly_score');
                }
                if (Schema::hasColumn('admin_activity_logs', 'anomaly_type')) {
                    $table->dropColumn('anomaly_type');
                }
                if (Schema::hasColumn('admin_activity_logs', 'is_anomaly')) {
                    $table->dropColumn('is_anomaly');
                }
            });
        }
    }

    private function dropFinancialOnlyTables(): void
    {
        if (Schema::hasTable('accounting_events')) {
            Schema::drop('accounting_events');
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
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

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
        if (DB::connection()->getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

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
