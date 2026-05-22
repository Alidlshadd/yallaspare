<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addCurrencyColumns();
        $this->upgradeMoneyPrecision();
        $this->createAccountingEventsTable();
        $this->addTamperEvidentColumns();
        $this->addFraudSignalColumns();
        $this->backfillAndSeed();
        $this->createFinancialTriggers();
    }

    public function down(): void
    {
        // Do not rollback in production.
        // This migration adds financial integrity controls intended to be one-way.
    }

    private function addCurrencyColumns(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IQD')->after('price');
                }
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'currency_code')) {
                    $table->string('currency_code', 3)->nullable()->after('unit_price');
                }
            });
        }

        if (Schema::hasTable('price_histories')) {
            Schema::table('price_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('price_histories', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IQD')->after('new_price');
                }
            });
        }

        if (Schema::hasTable('stock_transactions')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_transactions', 'unit_cost')) {
                    $table->decimal('unit_cost', 15, 4)->nullable()->after('quantity_change');
                }
                if (!Schema::hasColumn('stock_transactions', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IQD')->after('unit_cost');
                }
            });
        }

        if (Schema::hasTable('discounts')) {
            Schema::table('discounts', function (Blueprint $table) {
                if (!Schema::hasColumn('discounts', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IQD')->after('value');
                }
            });
        }

        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                if (!Schema::hasColumn('coupons', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IQD')->after('value');
                }
            });
        }

        if (Schema::hasTable('coupon_usages')) {
            Schema::table('coupon_usages', function (Blueprint $table) {
                if (!Schema::hasColumn('coupon_usages', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IQD')->after('discount_amount');
                }
            });
        }

        if (Schema::hasTable('supplier_products')) {
            Schema::table('supplier_products', function (Blueprint $table) {
                if (!Schema::hasColumn('supplier_products', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IQD')->after('last_cost_price');
                }
            });
        }

        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_invoice_items', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IQD')->after('line_total');
                }
            });
        }
    }

    private function upgradeMoneyPrecision(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Run this migration in low traffic or through pt-online-schema-change for very large tables.
        $this->tryAlter("ALTER TABLE `products` MODIFY `price` DECIMAL(15,4) NOT NULL");
        $this->tryAlter("ALTER TABLE `products` MODIFY `dealer_price` DECIMAL(15,4) NULL DEFAULT NULL");
        $this->tryAlter("ALTER TABLE `products` MODIFY `cost_price` DECIMAL(15,4) NULL DEFAULT NULL");

        $this->tryAlter("ALTER TABLE `orders` MODIFY `total_amount` DECIMAL(15,4) NOT NULL");
        $this->tryAlter("ALTER TABLE `orders` MODIFY `subtotal_amount` DECIMAL(15,4) NULL DEFAULT NULL");
        $this->tryAlter("ALTER TABLE `orders` MODIFY `discount_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `orders` MODIFY `tax_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `orders` MODIFY `shipping_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");

        $this->tryAlter("ALTER TABLE `order_items` MODIFY `unit_price` DECIMAL(15,4) NOT NULL");
        $this->tryAlter("ALTER TABLE `order_items` MODIFY `subtotal` DECIMAL(15,4) NOT NULL");
        $this->tryAlter("ALTER TABLE `order_items` MODIFY `tax_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `order_items` MODIFY `discount_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");

        $this->tryAlter("ALTER TABLE `price_histories` MODIFY `old_price` DECIMAL(15,4) NULL DEFAULT NULL");
        $this->tryAlter("ALTER TABLE `price_histories` MODIFY `new_price` DECIMAL(15,4) NOT NULL");
        $this->tryAlter("ALTER TABLE `price_histories` MODIFY `old_dealer_price` DECIMAL(15,4) NULL DEFAULT NULL");
        $this->tryAlter("ALTER TABLE `price_histories` MODIFY `new_dealer_price` DECIMAL(15,4) NULL DEFAULT NULL");

        $this->tryAlter("ALTER TABLE `supplier_products` MODIFY `last_cost_price` DECIMAL(15,4) NULL DEFAULT NULL");
        $this->tryAlter("ALTER TABLE `purchase_invoices` MODIFY `subtotal` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `purchase_invoices` MODIFY `discount_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `purchase_invoices` MODIFY `tax_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `purchase_invoices` MODIFY `grand_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `purchase_invoice_items` MODIFY `unit_cost` DECIMAL(15,4) NOT NULL");
        $this->tryAlter("ALTER TABLE `purchase_invoice_items` MODIFY `line_discount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `purchase_invoice_items` MODIFY `tax_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `purchase_invoice_items` MODIFY `line_total` DECIMAL(15,4) NOT NULL");

        $this->tryAlter("ALTER TABLE `discounts` MODIFY `value` DECIMAL(15,4) NOT NULL");
        $this->tryAlter("ALTER TABLE `discounts` MODIFY `minimum_subtotal` DECIMAL(15,4) NULL DEFAULT NULL");
        $this->tryAlter("ALTER TABLE `coupons` MODIFY `value` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
        $this->tryAlter("ALTER TABLE `coupons` MODIFY `minimum_subtotal` DECIMAL(15,4) NULL DEFAULT NULL");
        $this->tryAlter("ALTER TABLE `coupon_usages` MODIFY `discount_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000");

        $this->tryAlter("ALTER TABLE `shipping_rate_rules` MODIFY `min_subtotal` DECIMAL(15,4) NULL DEFAULT NULL");
        $this->tryAlter("ALTER TABLE `shipping_rate_rules` MODIFY `max_subtotal` DECIMAL(15,4) NULL DEFAULT NULL");
        $this->tryAlter("ALTER TABLE `shipping_rate_rules` MODIFY `rate` DECIMAL(15,4) NOT NULL");
    }

    private function createAccountingEventsTable(): void
    {
        if (Schema::hasTable('accounting_events')) {
            return;
        }

        Schema::create('accounting_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->string('event_type', 80);
            $table->string('account_code', 64);
            $table->enum('entry_side', ['debit', 'credit']);
            $table->decimal('amount', 15, 4);
            $table->string('currency_code', 3);
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->char('previous_hash', 64)->nullable();
            $table->char('current_hash', 64)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['event_type', 'occurred_at'], 'accounting_events_type_date_idx');
            $table->index(['account_code', 'occurred_at'], 'accounting_events_account_date_idx');
            $table->index(['reference_type', 'reference_id'], 'accounting_events_reference_idx');
            $table->index(['currency_code', 'occurred_at'], 'accounting_events_currency_date_idx');
        });
    }

    private function addTamperEvidentColumns(): void
    {
        $tables = ['price_histories', 'stock_transactions', 'audit_logs'];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'previous_hash')) {
                    $table->char('previous_hash', 64)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'current_hash')) {
                    $table->char('current_hash', 64)->nullable()->after('previous_hash');
                }
            });

            $this->createIndexIfMissing($tableName, ['current_hash'], "{$tableName}_current_hash_idx");
            if ($tableName === 'price_histories' && Schema::hasColumn($tableName, 'changed_at')) {
                $this->createIndexIfMissing($tableName, ['changed_at'], "{$tableName}_changed_at_idx");
            }
            if ($tableName !== 'price_histories' && Schema::hasColumn($tableName, 'occurred_at')) {
                $this->createIndexIfMissing($tableName, ['occurred_at'], "{$tableName}_occurred_at_idx");
            }
        }
    }

    private function addFraudSignalColumns(): void
    {
        if (Schema::hasTable('admin_activity_logs')) {
            Schema::table('admin_activity_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_activity_logs', 'is_anomaly')) {
                    $table->boolean('is_anomaly')->default(false)->after('risk_score');
                }
                if (!Schema::hasColumn('admin_activity_logs', 'anomaly_type')) {
                    $table->string('anomaly_type')->nullable()->after('is_anomaly');
                }
                if (!Schema::hasColumn('admin_activity_logs', 'anomaly_score')) {
                    $table->unsignedTinyInteger('anomaly_score')->default(0)->after('anomaly_type');
                }
                if (!Schema::hasColumn('admin_activity_logs', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('anomaly_score');
                }
                if (!Schema::hasColumn('admin_activity_logs', 'reviewed_by')) {
                    $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('reviewed_at');
                }
            });

            $this->createIndexIfMissing('admin_activity_logs', ['is_anomaly', 'created_at'], 'admin_activity_anomaly_date_idx');
            $this->createIndexIfMissing('admin_activity_logs', ['anomaly_score', 'created_at'], 'admin_activity_anomaly_score_date_idx');
        }

        if (Schema::hasTable('price_histories')) {
            Schema::table('price_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('price_histories', 'is_anomaly')) {
                    $table->boolean('is_anomaly')->default(false)->after('reason');
                }
                if (!Schema::hasColumn('price_histories', 'anomaly_reason')) {
                    $table->string('anomaly_reason')->nullable()->after('is_anomaly');
                }
                if (!Schema::hasColumn('price_histories', 'anomaly_score')) {
                    $table->unsignedTinyInteger('anomaly_score')->default(0)->after('anomaly_reason');
                }
            });

            $this->createIndexIfMissing('price_histories', ['is_anomaly', 'changed_at'], 'price_histories_anomaly_date_idx');
            $this->createIndexIfMissing('price_histories', ['anomaly_score', 'changed_at'], 'price_histories_anomaly_score_date_idx');
        }

        if (Schema::hasTable('stock_transactions')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_transactions', 'is_bulk_adjustment')) {
                    $table->boolean('is_bulk_adjustment')->default(false)->after('transaction_type');
                }
                if (!Schema::hasColumn('stock_transactions', 'is_anomaly')) {
                    $table->boolean('is_anomaly')->default(false)->after('is_bulk_adjustment');
                }
                if (!Schema::hasColumn('stock_transactions', 'anomaly_reason')) {
                    $table->string('anomaly_reason')->nullable()->after('is_anomaly');
                }
                if (!Schema::hasColumn('stock_transactions', 'anomaly_score')) {
                    $table->unsignedTinyInteger('anomaly_score')->default(0)->after('anomaly_reason');
                }
            });

            $this->createIndexIfMissing('stock_transactions', ['is_anomaly', 'occurred_at'], 'stock_tx_anomaly_date_idx');
            $this->createIndexIfMissing('stock_transactions', ['is_bulk_adjustment', 'occurred_at'], 'stock_tx_bulk_adj_date_idx');
        }
    }

    private function backfillAndSeed(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'price_change_alert_threshold_percent'],
                ['value' => '25', 'created_at' => now(), 'updated_at' => now()]
            );
            DB::table('settings')->updateOrInsert(
                ['key' => 'bulk_stock_adjustment_threshold'],
                ['value' => '100', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        if (
            Schema::hasTable('order_items')
            && Schema::hasTable('orders')
            && Schema::hasColumn('order_items', 'currency_code')
            && Schema::hasColumn('orders', 'currency_code')
        ) {
            if (DB::connection()->getDriverName() === 'sqlite') {
                DB::statement(
                    "UPDATE order_items
                     SET currency_code = (SELECT orders.currency_code FROM orders WHERE orders.id = order_items.order_id)
                     WHERE currency_code IS NULL"
                );
            } else {
                DB::statement(
                    "UPDATE `order_items` oi
                     JOIN `orders` o ON o.id = oi.order_id
                     SET oi.currency_code = o.currency_code
                     WHERE oi.currency_code IS NULL"
                );
            }
        }
    }

    private function createFinancialTriggers(): void
    {
        if (Schema::hasTable('order_items')) {
            $this->createTriggerIfMissing(
                'trg_order_items_currency_consistency_bi',
                "CREATE TRIGGER `trg_order_items_currency_consistency_bi`
                 BEFORE INSERT ON `order_items`
                 FOR EACH ROW
                 BEGIN
                   DECLARE order_currency VARCHAR(3);
                   SELECT currency_code INTO order_currency FROM orders WHERE id = NEW.order_id LIMIT 1;
                   IF order_currency IS NULL THEN
                     SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Order currency missing';
                   END IF;
                   IF NEW.currency_code IS NULL OR NEW.currency_code = '' THEN
                     SET NEW.currency_code = order_currency;
                   END IF;
                   IF NEW.currency_code <> order_currency THEN
                     SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Currency mismatch with order';
                   END IF;
                 END"
            );

            $this->createTriggerIfMissing(
                'trg_order_items_currency_consistency_bu',
                "CREATE TRIGGER `trg_order_items_currency_consistency_bu`
                 BEFORE UPDATE ON `order_items`
                 FOR EACH ROW
                 BEGIN
                   DECLARE order_currency VARCHAR(3);
                   SELECT currency_code INTO order_currency FROM orders WHERE id = NEW.order_id LIMIT 1;
                   IF order_currency IS NULL THEN
                     SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Order currency missing';
                   END IF;
                   IF NEW.currency_code IS NULL OR NEW.currency_code = '' THEN
                     SET NEW.currency_code = order_currency;
                   END IF;
                   IF NEW.currency_code <> order_currency THEN
                     SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Currency mismatch with order';
                   END IF;
                 END"
            );
        }

        if (Schema::hasTable('price_histories')) {
            $this->createTriggerIfMissing(
                'trg_price_histories_financial_bi',
                "CREATE TRIGGER `trg_price_histories_financial_bi`
                 BEFORE INSERT ON `price_histories`
                 FOR EACH ROW
                 BEGIN
                   DECLARE prev_hash CHAR(64);
                   DECLARE threshold DECIMAL(15,4);
                   DECLARE pct DECIMAL(15,4);

                   IF NEW.currency_code IS NULL OR NEW.currency_code = '' THEN
                     SET NEW.currency_code = 'IQD';
                   END IF;

                   SELECT current_hash INTO prev_hash
                   FROM price_histories
                   ORDER BY id DESC
                   LIMIT 1;

                   SET NEW.previous_hash = IFNULL(prev_hash, REPEAT('0', 64));

                   SET NEW.current_hash = SHA2(CONCAT_WS('|',
                     NEW.previous_hash,
                     COALESCE(CAST(NEW.product_id AS CHAR), ''),
                     COALESCE(CAST(NEW.changed_by AS CHAR), ''),
                     COALESCE(CAST(NEW.old_price AS CHAR), ''),
                     COALESCE(CAST(NEW.new_price AS CHAR), ''),
                     COALESCE(CAST(NEW.old_dealer_price AS CHAR), ''),
                     COALESCE(CAST(NEW.new_dealer_price AS CHAR), ''),
                     COALESCE(NEW.currency_code, ''),
                     COALESCE(CAST(NEW.changed_at AS CHAR), '')
                   ), 256);

                   IF NEW.old_price IS NOT NULL AND NEW.old_price > 0 THEN
                     SELECT CAST(value AS DECIMAL(15,4)) INTO threshold
                     FROM settings
                     WHERE `key` = 'price_change_alert_threshold_percent'
                     LIMIT 1;
                     SET threshold = IFNULL(threshold, 25.0000);

                     SET pct = ABS((NEW.new_price - NEW.old_price) / NEW.old_price) * 100;
                     IF pct >= threshold THEN
                       SET NEW.is_anomaly = 1;
                       SET NEW.anomaly_reason = 'price_change_threshold_exceeded';
                       SET NEW.anomaly_score = LEAST(100, GREATEST(1, FLOOR(pct)));
                     END IF;
                   END IF;
                 END"
            );
        }

        if (Schema::hasTable('stock_transactions')) {
            $this->createTriggerIfMissing(
                'trg_stock_transactions_financial_bi',
                "CREATE TRIGGER `trg_stock_transactions_financial_bi`
                 BEFORE INSERT ON `stock_transactions`
                 FOR EACH ROW
                 BEGIN
                   DECLARE prev_hash CHAR(64);
                   DECLARE threshold INT;

                   IF NEW.currency_code IS NULL OR NEW.currency_code = '' THEN
                     SET NEW.currency_code = 'IQD';
                   END IF;

                   SELECT current_hash INTO prev_hash
                   FROM stock_transactions
                   ORDER BY id DESC
                   LIMIT 1;

                   SET NEW.previous_hash = IFNULL(prev_hash, REPEAT('0', 64));

                   SET NEW.current_hash = SHA2(CONCAT_WS('|',
                     NEW.previous_hash,
                     COALESCE(CAST(NEW.product_id AS CHAR), ''),
                     COALESCE(CAST(NEW.warehouse_id AS CHAR), ''),
                     COALESCE(CAST(NEW.performed_by AS CHAR), ''),
                     COALESCE(NEW.transaction_type, ''),
                     COALESCE(CAST(NEW.quantity_change AS CHAR), ''),
                     COALESCE(CAST(NEW.stock_before AS CHAR), ''),
                     COALESCE(CAST(NEW.stock_after AS CHAR), ''),
                     COALESCE(CAST(NEW.unit_cost AS CHAR), ''),
                     COALESCE(NEW.currency_code, ''),
                     COALESCE(CAST(NEW.occurred_at AS CHAR), '')
                   ), 256);

                   SELECT CAST(value AS UNSIGNED) INTO threshold
                   FROM settings
                   WHERE `key` = 'bulk_stock_adjustment_threshold'
                   LIMIT 1;
                   SET threshold = IFNULL(threshold, 100);

                   IF NEW.transaction_type IN ('adjustment_in', 'adjustment_out')
                      AND ABS(NEW.quantity_change) >= threshold THEN
                     SET NEW.is_bulk_adjustment = 1;
                     SET NEW.is_anomaly = 1;
                     SET NEW.anomaly_reason = 'bulk_stock_adjustment';
                     SET NEW.anomaly_score = LEAST(100, GREATEST(1, FLOOR((ABS(NEW.quantity_change) / threshold) * 20)));
                   END IF;
                 END"
            );
        }

        if (Schema::hasTable('audit_logs')) {
            $this->createTriggerIfMissing(
                'trg_audit_logs_financial_bi',
                "CREATE TRIGGER `trg_audit_logs_financial_bi`
                 BEFORE INSERT ON `audit_logs`
                 FOR EACH ROW
                 BEGIN
                   DECLARE prev_hash CHAR(64);

                   SELECT current_hash INTO prev_hash
                   FROM audit_logs
                   ORDER BY id DESC
                   LIMIT 1;

                   SET NEW.previous_hash = IFNULL(prev_hash, REPEAT('0', 64));

                   SET NEW.current_hash = SHA2(CONCAT_WS('|',
                     NEW.previous_hash,
                     COALESCE(NEW.event_type, ''),
                     COALESCE(NEW.actor_type, ''),
                     COALESCE(CAST(NEW.actor_id AS CHAR), ''),
                     COALESCE(NEW.subject_type, ''),
                     COALESCE(CAST(NEW.subject_id AS CHAR), ''),
                     COALESCE(NEW.ip_address, ''),
                     COALESCE(CAST(NEW.occurred_at AS CHAR), '')
                   ), 256);
                 END"
            );
        }

        if (Schema::hasTable('accounting_events')) {
            $this->createTriggerIfMissing(
                'trg_accounting_events_hash_bi',
                "CREATE TRIGGER `trg_accounting_events_hash_bi`
                 BEFORE INSERT ON `accounting_events`
                 FOR EACH ROW
                 BEGIN
                   DECLARE prev_hash CHAR(64);

                   SELECT current_hash INTO prev_hash
                   FROM accounting_events
                   ORDER BY id DESC
                   LIMIT 1;

                   SET NEW.previous_hash = IFNULL(prev_hash, REPEAT('0', 64));

                   SET NEW.current_hash = SHA2(CONCAT_WS('|',
                     NEW.previous_hash,
                     COALESCE(NEW.event_uuid, ''),
                     COALESCE(NEW.event_type, ''),
                     COALESCE(NEW.account_code, ''),
                     COALESCE(NEW.entry_side, ''),
                     COALESCE(CAST(NEW.amount AS CHAR), ''),
                     COALESCE(NEW.currency_code, ''),
                     COALESCE(NEW.reference_type, ''),
                     COALESCE(CAST(NEW.reference_id AS CHAR), ''),
                     COALESCE(CAST(NEW.order_id AS CHAR), ''),
                     COALESCE(CAST(NEW.occurred_at AS CHAR), '')
                   ), 256);
                 END"
            );
        }
    }

    private function createIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $indexName) {
            $tableBlueprint->index($columns, $indexName);
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

    private function createTriggerIfMissing(string $triggerName, string $sql): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if ($this->triggerExists($triggerName)) {
            return;
        }

        DB::unprepared($sql);
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

    private function tryAlter(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            // Keep migration idempotent across different starting schemas.
        }
    }
};
