<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'theme_preference')) {
                $table->string('theme_preference', 20)->default('light')->after('phone');
            }

            if (! Schema::hasColumn('users', 'locale_preference')) {
                $table->string('locale_preference', 10)->default('en')->after('theme_preference');
            }

            if (! Schema::hasColumn('users', 'notify_order_updates')) {
                $table->boolean('notify_order_updates')->default(true)->after('locale_preference');
            }

            if (! Schema::hasColumn('users', 'notify_promotions')) {
                $table->boolean('notify_promotions')->default(false)->after('notify_order_updates');
            }

            if (! Schema::hasColumn('users', 'notify_stock_alerts')) {
                $table->boolean('notify_stock_alerts')->default(true)->after('notify_promotions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'theme_preference',
                'locale_preference',
                'notify_order_updates',
                'notify_promotions',
                'notify_stock_alerts',
            ];

            $existing = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('users', $column)));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
