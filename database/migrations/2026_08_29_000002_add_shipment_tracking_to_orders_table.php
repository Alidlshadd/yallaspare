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
            $table->string('carrier', 64)->nullable()->after('delivery_days');
            $table->string('tracking_number', 64)->nullable()->after('carrier');

            // When the parcel left and when it arrived. The status history
            // records every change, but reading a date off the order is what
            // the customer screen, the return window and any report need.
            $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');

            // Support looks orders up by the number the carrier gave.
            $table->index('tracking_number');
        });

        $this->backfillFromStatusHistory();
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['tracking_number']);
            $table->dropColumn(['carrier', 'tracking_number', 'shipped_at', 'delivered_at']);
        });
    }

    /**
     * Orders that already shipped or arrived have the moment recorded in their
     * status history. Copy it across so existing orders are not blank.
     */
    private function backfillFromStatusHistory(): void
    {
        if (! Schema::hasTable('order_status_histories')) {
            return;
        }

        foreach (['shipped' => 'shipped_at', 'delivered' => 'delivered_at'] as $status => $column) {
            // A correlated subquery rather than an update with a join: SQLite
            // rewrites the join form into something whose alias the SET clause
            // cannot see, and the test suite runs on SQLite.
            $firstMove = '(select min(created_at) from order_status_histories'
                .' where order_status_histories.order_id = orders.id'
                ." and order_status_histories.to_status = '".$status."')";

            DB::table('orders')
                ->whereNull($column)
                ->whereExists(function ($query) use ($status): void {
                    $query->selectRaw('1')
                        ->from('order_status_histories')
                        ->whereColumn('order_status_histories.order_id', 'orders.id')
                        ->where('order_status_histories.to_status', $status);
                })
                ->update([$column => DB::raw($firstMove)]);
        }
    }
};
