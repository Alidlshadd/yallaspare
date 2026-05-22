<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'catalog_layout') ? 'catalog_layout' : null,
                Schema::hasColumn('users', 'display_density') ? 'display_density' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'catalog_layout')) {
                $table->string('catalog_layout', 20)->default('grid')->after('notify_stock_alerts');
            }

            if (! Schema::hasColumn('users', 'display_density')) {
                $table->string('display_density', 20)->default('comfortable')->after('catalog_layout');
            }
        });
    }
};