<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'user_id')) {
            DB::statement('ALTER TABLE `orders` MODIFY `user_id` BIGINT UNSIGNED NULL');
        }

        if (Schema::hasTable('inventory_movements') && Schema::hasColumn('inventory_movements', 'user_id')) {
            DB::statement('ALTER TABLE `inventory_movements` MODIFY `user_id` BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Guest orders may already exist, so this migration is intentionally not reverted automatically.
    }
};
