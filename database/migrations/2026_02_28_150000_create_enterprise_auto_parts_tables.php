<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_brands')) {
            Schema::create('product_brands', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('country_code', 2)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('car_brands')) {
            Schema::create('car_brands', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('country_code', 2)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('car_models')) {
            Schema::create('car_models', function (Blueprint $table) {
                $table->id();
                $table->foreignId('car_brand_id')->constrained('car_brands')->restrictOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->unsignedSmallInteger('production_start_year')->nullable();
                $table->unsignedSmallInteger('production_end_year')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['car_brand_id', 'slug'], 'car_models_brand_slug_unq');
                $table->index(['car_brand_id', 'name'], 'car_models_brand_name_idx');
            });
        }

        if (!Schema::hasTable('engine_types')) {
            Schema::create('engine_types', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable();
                $table->string('name');
                $table->string('fuel_type', 30)->nullable();
                $table->unsignedSmallInteger('cc')->nullable();
                $table->decimal('liter', 4, 1)->unsigned()->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['code', 'name'], 'engine_types_code_name_unq');
                $table->index('name', 'engine_types_name_idx');
            });
        }

        if (!Schema::hasTable('vehicle_years')) {
            Schema::create('vehicle_years', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('year');
                $table->timestamps();
                $table->unique('year', 'vehicle_years_year_unq');
            });
        }

        if (!Schema::hasTable('product_compatibilities')) {
            Schema::create('product_compatibilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('car_brand_id')->constrained('car_brands')->restrictOnDelete();
                $table->foreignId('car_model_id')->nullable()->constrained('car_models')->nullOnDelete();
                $table->foreignId('engine_type_id')->nullable()->constrained('engine_types')->nullOnDelete();
                $table->foreignId('vehicle_year_id')->nullable()->constrained('vehicle_years')->nullOnDelete();
                $table->string('vin_prefix', 17)->nullable();
                $table->string('fitment_note')->nullable();
                $table->timestamps();

                $table->unique(
                    ['product_id', 'car_brand_id', 'car_model_id', 'engine_type_id', 'vehicle_year_id'],
                    'prod_compatibility_matrix_unq'
                );
                $table->index(['car_brand_id', 'car_model_id', 'vehicle_year_id'], 'prod_compat_vehicle_idx');
                $table->index(['product_id', 'vehicle_year_id'], 'prod_compat_product_year_idx');
            });
        }

        if (!Schema::hasTable('part_numbers')) {
            Schema::create('part_numbers', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['oem', 'aftermarket', 'interchange']);
                $table->string('number', 120);
                $table->string('normalized_number', 120);
                $table->foreignId('product_brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['type', 'normalized_number'], 'part_numbers_type_normalized_unq');
                $table->index(['number', 'type'], 'part_numbers_number_type_idx');
            });
        }

        if (!Schema::hasTable('product_part_numbers')) {
            Schema::create('product_part_numbers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('part_number_id')->constrained('part_numbers')->restrictOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->unique(['product_id', 'part_number_id'], 'prod_part_number_unq');
                $table->index(['part_number_id', 'product_id'], 'prod_part_number_lookup_idx');
            });
        }

        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name');
                $table->string('contact_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('country_code', 2)->default('IQ');
                $table->string('city');
                $table->string('district')->nullable();
                $table->string('address_line_1');
                $table->string('address_line_2')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'city'], 'warehouses_active_city_idx');
            });
        }

        if (!Schema::hasTable('product_warehouse_stocks')) {
            Schema::create('product_warehouse_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->integer('available_quantity')->default(0);
                $table->integer('reserved_quantity')->default(0);
                $table->integer('reorder_level')->default(0);
                $table->integer('reorder_quantity')->default(0);
                $table->timestamps();

                $table->unique(['product_id', 'warehouse_id'], 'prod_wh_stock_unq');
                $table->index(['warehouse_id', 'available_quantity'], 'prod_wh_stock_wh_qty_idx');
            });
        }

        if (!Schema::hasTable('stock_transactions')) {
            Schema::create('stock_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('transaction_type', [
                    'opening_balance',
                    'purchase_in',
                    'sale_out',
                    'return_in',
                    'return_out',
                    'adjustment_in',
                    'adjustment_out',
                    'transfer_in',
                    'transfer_out',
                    'reservation',
                    'release_reservation',
                ]);
                $table->integer('quantity_change');
                $table->integer('stock_before');
                $table->integer('stock_after');
                $table->string('reference_type', 50)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reference_no')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();

                $table->index(['product_id', 'warehouse_id', 'occurred_at'], 'stock_tx_product_wh_date_idx');
                $table->index(['reference_type', 'reference_id'], 'stock_tx_reference_idx');
                $table->index(['performed_by', 'occurred_at'], 'stock_tx_user_date_idx');
            });
        }

        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 40)->unique();
                $table->string('contact_person')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('country_code', 2)->default('IQ');
                $table->string('city')->nullable();
                $table->string('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'name'], 'suppliers_active_name_idx');
            });
        }

        if (!Schema::hasTable('supplier_products')) {
            Schema::create('supplier_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('supplier_sku', 120)->nullable();
                $table->decimal('last_cost_price', 12, 2)->nullable();
                $table->unsignedSmallInteger('lead_time_days')->nullable();
                $table->unsignedInteger('minimum_order_qty')->default(1);
                $table->boolean('is_preferred')->default(false);
                $table->timestamps();

                $table->unique(['supplier_id', 'product_id'], 'supplier_product_unq');
                $table->index(['product_id', 'is_preferred'], 'supplier_product_pref_idx');
            });
        }

        if (!Schema::hasTable('purchase_invoices')) {
            Schema::create('purchase_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number', 80)->unique();
                $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->enum('status', ['draft', 'approved', 'received', 'cancelled'])->default('draft');
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount_total', 14, 2)->default(0);
                $table->decimal('tax_total', 14, 2)->default(0);
                $table->decimal('grand_total', 14, 2)->default(0);
                $table->string('currency_code', 3)->default('IQD');
                $table->text('note')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['supplier_id', 'invoice_date'], 'purchase_invoices_supplier_date_idx');
                $table->index(['status', 'invoice_date'], 'purchase_invoices_status_date_idx');
            });
        }

        if (!Schema::hasTable('purchase_invoice_items')) {
            Schema::create('purchase_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_cost', 12, 2);
                $table->decimal('line_discount', 12, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('line_total', 12, 2);
                $table->timestamps();

                $table->index(['purchase_invoice_id', 'product_id'], 'purchase_invoice_items_invoice_product_idx');
            });
        }

        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('path');
                $table->string('disk', 50)->default('public');
                $table->string('alt_text')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['product_id', 'is_primary', 'sort_order'], 'product_images_listing_idx');
            });
        }

        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('variant_product_id')->constrained('products')->cascadeOnDelete();
                $table->string('attribute_snapshot')->nullable();
                $table->timestamps();

                $table->unique(['parent_product_id', 'variant_product_id'], 'product_variants_parent_variant_unq');
                $table->index(['variant_product_id', 'parent_product_id'], 'product_variants_variant_parent_idx');
            });
        }

        if (!Schema::hasTable('price_histories')) {
            Schema::create('price_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('old_price', 12, 2)->nullable();
                $table->decimal('new_price', 12, 2);
                $table->decimal('old_dealer_price', 12, 2)->nullable();
                $table->decimal('new_dealer_price', 12, 2)->nullable();
                $table->string('reason')->nullable();
                $table->timestamp('changed_at')->useCurrent();
                $table->timestamps();

                $table->index(['product_id', 'changed_at'], 'price_histories_product_date_idx');
                $table->index(['changed_by', 'changed_at'], 'price_histories_user_date_idx');
            });
        }

        if (!Schema::hasTable('discounts')) {
            Schema::create('discounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 80)->nullable()->unique();
                $table->enum('scope', ['catalog', 'product', 'category', 'shipping']);
                $table->enum('type', ['percent', 'fixed']);
                $table->decimal('value', 12, 2);
                $table->decimal('minimum_subtotal', 12, 2)->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'starts_at', 'ends_at'], 'discounts_active_window_idx');
                $table->index(['scope', 'type'], 'discounts_scope_type_idx');
            });
        }

        if (!Schema::hasTable('discount_product')) {
            Schema::create('discount_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['discount_id', 'product_id'], 'discount_product_unq');
            });
        }

        if (!Schema::hasTable('discount_category')) {
            Schema::create('discount_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['discount_id', 'category_id'], 'discount_category_unq');
            });
        }

        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 80)->unique();
                $table->string('name')->nullable();
                $table->enum('type', ['percent', 'fixed', 'free_shipping']);
                $table->decimal('value', 12, 2)->default(0);
                $table->decimal('minimum_subtotal', 12, 2)->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('usage_limit_per_user')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'starts_at', 'ends_at'], 'coupons_active_window_idx');
            });
        }

        if (!Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->timestamp('used_at')->useCurrent();
                $table->timestamps();

                $table->unique(['coupon_id', 'order_id'], 'coupon_usage_coupon_order_unq');
                $table->index(['coupon_id', 'user_id', 'used_at'], 'coupon_usage_coupon_user_date_idx');
            });
        }

        if (!Schema::hasTable('tax_classes')) {
            Schema::create('tax_classes', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tax_rates')) {
            Schema::create('tax_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tax_class_id')->constrained('tax_classes')->cascadeOnDelete();
                $table->string('country_code', 2)->default('IQ');
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->decimal('rate_percent', 6, 3);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tax_class_id', 'country_code', 'state', 'city'], 'tax_rates_location_idx');
                $table->index(['is_active', 'starts_at', 'ends_at'], 'tax_rates_active_window_idx');
            });
        }

        if (!Schema::hasTable('shipping_zones')) {
            Schema::create('shipping_zones', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('country_code', 2)->default('IQ');
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('postal_code_pattern')->nullable();
                $table->unsignedInteger('priority')->default(100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['country_code', 'state', 'city'], 'shipping_zones_location_idx');
                $table->index(['is_active', 'priority'], 'shipping_zones_active_priority_idx');
            });
        }

        if (!Schema::hasTable('shipping_methods')) {
            Schema::create('shipping_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 80)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'name'], 'shipping_methods_active_name_idx');
            });
        }

        if (!Schema::hasTable('shipping_rate_rules')) {
            Schema::create('shipping_rate_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
                $table->foreignId('shipping_method_id')->constrained('shipping_methods')->cascadeOnDelete();
                $table->decimal('min_subtotal', 12, 2)->nullable();
                $table->decimal('max_subtotal', 12, 2)->nullable();
                $table->unsignedInteger('min_weight_grams')->nullable();
                $table->unsignedInteger('max_weight_grams')->nullable();
                $table->decimal('rate', 12, 2);
                $table->string('currency_code', 3)->default('IQD');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['shipping_zone_id', 'shipping_method_id', 'is_active'], 'shipping_rate_rules_lookup_idx');
            });
        }

        if (!Schema::hasTable('admin_security_events')) {
            Schema::create('admin_security_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event_type');
                $table->unsignedTinyInteger('severity')->default(1);
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('context')->nullable();
                $table->timestamp('detected_at')->useCurrent();
                $table->timestamps();

                $table->index(['event_type', 'detected_at'], 'admin_security_events_type_date_idx');
                $table->index(['user_id', 'detected_at'], 'admin_security_events_user_date_idx');
                $table->index(['severity', 'detected_at'], 'admin_security_events_severity_date_idx');
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type');
                $table->string('actor_type')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();

                $table->index(['event_type', 'occurred_at'], 'audit_logs_event_date_idx');
                $table->index(['subject_type', 'subject_id'], 'audit_logs_subject_idx');
                $table->index(['actor_type', 'actor_id'], 'audit_logs_actor_idx');
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('guard_name')->default('web');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('guard_name')->default('web');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['role_id', 'permission_id'], 'role_permission_unq');
            });
        }

        if (!Schema::hasTable('user_role')) {
            Schema::create('user_role', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'role_id'], 'user_role_unq');
            });
        }
    }

    public function down(): void
    {
        // Do not rollback in production.
        // Down is only for disposable non-production environments.
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('admin_security_events');
        Schema::dropIfExists('shipping_rate_rules');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_classes');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('discount_category');
        Schema::dropIfExists('discount_product');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('price_histories');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('purchase_invoice_items');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('supplier_products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('product_warehouse_stocks');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('product_part_numbers');
        Schema::dropIfExists('part_numbers');
        Schema::dropIfExists('product_compatibilities');
        Schema::dropIfExists('vehicle_years');
        Schema::dropIfExists('engine_types');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('car_brands');
        Schema::dropIfExists('product_brands');
    }
};
