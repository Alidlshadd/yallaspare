<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('discounts')) {
            return;
        }

        if (!Schema::hasColumn('discounts', 'brand_names')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->json('brand_names')->nullable()->after('is_active');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE discounts MODIFY COLUMN scope ENUM('catalog','product','category','shipping','brand') NOT NULL");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('discounts')) {
            return;
        }

        if (Schema::hasColumn('discounts', 'brand_names')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->dropColumn('brand_names');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE discounts MODIFY COLUMN scope ENUM('catalog','product','category','shipping') NOT NULL");
        }
    }
};
