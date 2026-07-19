<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CUSTOMER_PHONE_INDEX = 'users_customer_phone_unique';

    public function up(): void
    {
        if (
            Schema::hasColumn('users', 'phone_normalized')
            && Schema::hasIndex('users', 'users_phone_normalized_unique')
        ) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_phone_normalized_unique');
            });
        }

        if (! Schema::hasColumn('users', 'customer_phone_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('customer_phone_unique', 40)
                    ->nullable()
                    ->virtualAs(
                        "CASE WHEN role IN ('super_admin', 'admin', 'product_manager', 'order_manager', "
                        ."'finance_manager', 'inventory_manager', 'settings_manager') "
                        .'THEN NULL ELSE phone_normalized END'
                    );
            });
        }

        if (! Schema::hasIndex('users', self::CUSTOMER_PHONE_INDEX)) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('customer_phone_unique', self::CUSTOMER_PHONE_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('users', self::CUSTOMER_PHONE_INDEX)) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(self::CUSTOMER_PHONE_INDEX);
            });
        }

        if (Schema::hasColumn('users', 'customer_phone_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('customer_phone_unique');
            });
        }

        if (
            Schema::hasColumn('users', 'phone_normalized')
            && ! Schema::hasIndex('users', 'users_phone_normalized_unique')
        ) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('phone_normalized', 'users_phone_normalized_unique');
            });
        }
    }
};
