<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How far along the reminder cycle a cart is.
 *
 * Two columns rather than a table of sent messages: a cart only ever has one
 * cycle in flight, and the cycle re-arms by itself. `reminded_at` is compared
 * against the cart's last activity — the newest `cart_items.updated_at` — so a
 * customer who puts something else in the cart is treated as a fresh cart
 * without anything having to reset these columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('carts')) {
            return;
        }

        Schema::table('carts', function (Blueprint $table): void {
            if (! Schema::hasColumn('carts', 'reminder_stage')) {
                $table->unsignedTinyInteger('reminder_stage')->default(0)->after('session_token');
            }

            if (! Schema::hasColumn('carts', 'reminded_at')) {
                $table->timestamp('reminded_at')->nullable()->after('reminder_stage');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('carts')) {
            return;
        }

        $columns = array_values(array_filter(
            ['reminder_stage', 'reminded_at'],
            static fn (string $column): bool => Schema::hasColumn('carts', $column)
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('carts', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
