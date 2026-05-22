<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 12, 2)->default(0)->after('order_number');
            }

            if (! Schema::hasColumn('orders', 'shipping_fee')) {
                $table->decimal('shipping_fee', 12, 2)->default(0)->after('subtotal_amount');
            }

            if (! Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('shipping_fee');
            }

            if (! Schema::hasColumn('orders', 'grand_total')) {
                $table->decimal('grand_total', 12, 2)->default(0)->after('discount_amount');
            }
        });

        DB::table('orders')
            ->where(function ($query): void {
                $query->whereNull('subtotal_amount')
                    ->orWhere('subtotal_amount', 0);
            })
            ->update([
                'subtotal_amount' => DB::raw('total_amount'),
                'shipping_fee' => 5000,
                'discount_amount' => 0,
                'grand_total' => DB::raw('total_amount + 5000'),
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            foreach (['grand_total', 'discount_amount', 'shipping_fee', 'subtotal_amount'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
