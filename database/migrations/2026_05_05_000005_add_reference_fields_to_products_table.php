<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'oem_number')) {
                $table->string('oem_number', 120)->nullable()->after('sku');
            }

            if (! Schema::hasColumn('products', 'part_number')) {
                $table->string('part_number', 120)->nullable()->after('oem_number');
            }

            if (! Schema::hasColumn('products', 'warranty')) {
                $table->string('warranty', 160)->nullable()->after('part_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            foreach (['warranty', 'part_number', 'oem_number'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
