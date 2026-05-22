<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'notes')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->text('notes')->nullable()->after('delivery_phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'notes')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('notes');
            });
        }
    }
};
