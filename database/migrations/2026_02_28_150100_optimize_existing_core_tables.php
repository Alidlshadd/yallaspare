<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'account_status')) {
                    $table->enum('account_status', ['active', 'inactive', 'locked'])->default('active')->after('role');
                }
                if (!Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('remember_token');
                }
                if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                    $table->unsignedSmallInteger('failed_login_attempts')->default(0)->after('last_login_at');
                }
                if (!Schema::hasColumn('users', 'locked_until')) {
                    $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
                }
                if (!Schema::hasColumn('users', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            $this->createIndexIfMissing('users', ['email', 'account_status'], 'users_email_status_idx');
            $this->createIndexIfMissing('users', ['role', 'account_status'], 'users_role_status_idx');
            $this->createIndexIfMissing('users', ['deleted_at'], 'users_deleted_at_idx');
        }

        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (!Schema::hasColumn('categories', 'parent_id')) {
                    $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
                }
                if (!Schema::hasColumn('categories', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0);
                }
                if (!Schema::hasColumn('categories', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!Schema::hasColumn('categories', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            $this->createIndexIfMissing('categories', ['parent_id', 'is_active', 'sort_order'], 'categories_tree_idx');
            $this->createIndexIfMissing('categories', ['deleted_at'], 'categories_deleted_at_idx');
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'product_brand_id')) {
                    $table->foreignId('product_brand_id')->nullable()->constrained('product_brands')->nullOnDelete()->after('category_id');
                }
                if (!Schema::hasColumn('products', 'supplier_id')) {
                    $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->after('product_brand_id');
                }
                if (!Schema::hasColumn('products', 'tax_class_id')) {
                    $table->foreignId('tax_class_id')->nullable()->constrained('tax_classes')->nullOnDelete()->after('supplier_id');
                }
                if (!Schema::hasColumn('products', 'cost_price')) {
                    $table->decimal('cost_price', 12, 2)->nullable()->after('dealer_price');
                }
                if (!Schema::hasColumn('products', 'weight_grams')) {
                    $table->unsignedInteger('weight_grams')->nullable()->after('cost_price');
                }
                if (!Schema::hasColumn('products', 'barcode')) {
                    $table->string('barcode', 100)->nullable()->after('sku');
                }
                if (!Schema::hasColumn('products', 'product_type')) {
                    $table->enum('product_type', ['simple', 'variable'])->default('simple')->after('barcode');
                }
                if (!Schema::hasColumn('products', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            $this->createIndexIfMissing('products', ['product_brand_id', 'is_active'], 'products_brand_active_idx');
            $this->createIndexIfMissing('products', ['supplier_id', 'is_active'], 'products_supplier_active_idx');
            $this->createIndexIfMissing('products', ['tax_class_id', 'is_active'], 'products_tax_class_active_idx');
            $this->createIndexIfMissing('products', ['price', 'is_active'], 'products_price_active_idx');
            $this->createIndexIfMissing('products', ['dealer_price', 'is_active'], 'products_dealer_price_active_idx');
            $this->createIndexIfMissing('products', ['deleted_at'], 'products_deleted_at_idx');
            $this->createIndexIfMissing('products', ['barcode'], 'products_barcode_idx');

        }

        if (Schema::hasTable('carts')) {
            $this->createIndexIfMissing('carts', ['user_id'], 'carts_user_idx');
        }

        if (Schema::hasTable('cart_items')) {
            $this->createIndexIfMissing('cart_items', ['cart_id', 'product_id'], 'cart_items_cart_product_idx');
            $this->createIndexIfMissing('cart_items', ['product_id', 'created_at'], 'cart_items_product_date_idx');
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'warehouse_id')) {
                    $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->after('user_id');
                }
                if (!Schema::hasColumn('orders', 'coupon_id')) {
                    $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete()->after('warehouse_id');
                }
                if (!Schema::hasColumn('orders', 'subtotal_amount')) {
                    $table->decimal('subtotal_amount', 12, 2)->nullable()->after('total_amount');
                }
                if (!Schema::hasColumn('orders', 'discount_amount')) {
                    $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal_amount');
                }
                if (!Schema::hasColumn('orders', 'tax_amount')) {
                    $table->decimal('tax_amount', 12, 2)->default(0)->after('discount_amount');
                }
                if (!Schema::hasColumn('orders', 'shipping_amount')) {
                    $table->decimal('shipping_amount', 12, 2)->default(0)->after('tax_amount');
                }
                if (!Schema::hasColumn('orders', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IQD')->after('shipping_amount');
                }
                if (!Schema::hasColumn('orders', 'payment_status')) {
                    $table->enum('payment_status', ['pending', 'paid', 'partially_paid', 'failed', 'refunded'])->default('pending')->after('payment_method');
                }
                if (!Schema::hasColumn('orders', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('payment_status');
                }
                if (!Schema::hasColumn('orders', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('paid_at');
                }
                if (!Schema::hasColumn('orders', 'cancelled_by')) {
                    $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->after('cancelled_at');
                }
                if (!Schema::hasColumn('orders', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            $this->createIndexIfMissing('orders', ['status', 'payment_status', 'created_at'], 'orders_status_payment_date_idx');
            $this->createIndexIfMissing('orders', ['warehouse_id', 'status', 'created_at'], 'orders_warehouse_status_date_idx');
            $this->createIndexIfMissing('orders', ['coupon_id', 'created_at'], 'orders_coupon_date_idx');
            $this->createIndexIfMissing('orders', ['deleted_at'], 'orders_deleted_at_idx');
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'warehouse_id')) {
                    $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->after('product_id');
                }
                if (!Schema::hasColumn('order_items', 'tax_amount')) {
                    $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal');
                }
                if (!Schema::hasColumn('order_items', 'discount_amount')) {
                    $table->decimal('discount_amount', 12, 2)->default(0)->after('tax_amount');
                }
            });

            $this->createIndexIfMissing('order_items', ['order_id', 'product_id'], 'order_items_order_product_idx');
            $this->createIndexIfMissing('order_items', ['product_id', 'created_at'], 'order_items_product_date_idx');
        }

        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_movements', 'warehouse_id')) {
                    $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->after('product_id');
                }
                if (!Schema::hasColumn('inventory_movements', 'performed_at')) {
                    $table->timestamp('performed_at')->nullable()->after('note');
                }
            });

            $this->createIndexIfMissing('inventory_movements', ['warehouse_id', 'product_id', 'created_at'], 'inv_mov_warehouse_product_date_idx');
        }

        if (Schema::hasTable('order_status_histories')) {
            Schema::table('order_status_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('order_status_histories', 'ip_address')) {
                    $table->string('ip_address', 45)->nullable()->after('note');
                }
                if (!Schema::hasColumn('order_status_histories', 'user_agent')) {
                    $table->text('user_agent')->nullable()->after('ip_address');
                }
            });

            $this->createIndexIfMissing('order_status_histories', ['order_id', 'to_status', 'created_at'], 'order_status_histories_lookup_idx');
        }

        if (Schema::hasTable('admin_activity_logs')) {
            Schema::table('admin_activity_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_activity_logs', 'ip_address')) {
                    $table->string('ip_address', 45)->nullable()->after('subject_id');
                }
                if (!Schema::hasColumn('admin_activity_logs', 'user_agent')) {
                    $table->text('user_agent')->nullable()->after('ip_address');
                }
                if (!Schema::hasColumn('admin_activity_logs', 'risk_score')) {
                    $table->unsignedTinyInteger('risk_score')->default(0)->after('user_agent');
                }
            });

            $this->createIndexIfMissing('admin_activity_logs', ['risk_score', 'created_at'], 'admin_activity_logs_risk_date_idx');
            $this->createIndexIfMissing('admin_activity_logs', ['ip_address', 'created_at'], 'admin_activity_logs_ip_date_idx');
        }

        if (Schema::hasTable('settings')) {
            $this->seedDefaultOperationalSettings();
        }
    }

    public function down(): void
    {
        // Do not rollback in production.
        // This migration intentionally keeps core tables unchanged on down() to prevent data loss.
    }

    private function createIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            return DB::selectOne(
                'select 1 from pg_indexes where tablename = ? and indexname = ? limit 1',
                [$table, $index]
            ) !== null;
        }

        $database = $connection->getDatabaseName();

        return DB::selectOne(
            'select 1 from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? limit 1',
            [$database, $table, $index]
        ) !== null;
    }

    private function seedDefaultOperationalSettings(): void
    {
        $defaults = [
            'currency_code' => 'IQD',
            'currency_precision' => '0',
            'inventory_reservation_timeout_minutes' => '30',
            'max_failed_admin_attempts' => '5',
            'admin_lockout_minutes' => '30',
            'default_tax_class' => 'standard',
            'default_shipping_zone' => 'iraq_general',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
};
