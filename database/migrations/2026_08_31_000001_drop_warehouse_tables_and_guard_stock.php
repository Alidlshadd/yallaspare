<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Take out the multi-warehouse tables, and put the rule they carried where the
 * stock actually lives.
 *
 * The warehouse feature was only ever half built: the tables and the reading
 * side existed, but nothing in the application could create a warehouse or put
 * stock in one, so they have stood empty since February. The shop keeps stock
 * as one number on the product, and that stays the only stock there is.
 *
 * The migration that created these tables is left alone. Rewriting history
 * would put this repository out of step with the migration log on the server,
 * which is a worse problem than the one being solved.
 *
 * MySQL only, the way allow_guest_checkout_orders already is. SQLite refuses to
 * drop a column a foreign key names, and dropping `warehouses` while
 * inventory_movements still points at it makes every insert fail — so undoing
 * that would mean restating a table four migrations have shaped, a copy that
 * drifts from the real thing the moment anyone forgets it exists. The test
 * database keeps three empty tables and one unused nullable column that no
 * code reads; the shop's own database is cleaned properly.
 */
return new class extends Migration
{
    /**
     * Tables that exist only to serve warehouses, innermost first so a foreign
     * key never blocks the drop.
     */
    private const TABLES = [
        'stock_transactions',
        'product_warehouse_stocks',
        'warehouses',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->refuseIfAnyTableHoldsRows();

        if (Schema::hasTable('inventory_movements') && Schema::hasColumn('inventory_movements', 'warehouse_id')) {
            Schema::table('inventory_movements', function (Blueprint $table): void {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
            });
        }

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        $this->guardProductStock(true);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // The tables are not rebuilt: they were empty, nothing wrote to them,
        // and the migration that first created them is still in place for a
        // database that has never run this one.
        $this->guardProductStock(false);
    }

    /**
     * Stop rather than delete.
     *
     * These tables are empty everywhere they have been looked at, but "empty on
     * my machine" is not "empty on the server", and a dropped table takes its
     * rows with it. If anything is in there, the deployment stops and somebody
     * looks at it before the data is gone.
     */
    private function refuseIfAnyTableHoldsRows(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = (int) DB::table($table)->count();

            if ($rows > 0) {
                throw new RuntimeException(
                    "Refusing to drop `{$table}`: it holds {$rows} row(s). ".
                    'The warehouse feature was believed unused. Export what is there and remove this migration, or empty the table deliberately.'
                );
            }
        }
    }

    /**
     * A stock that cannot go below zero, enforced by the database.
     *
     * Until now the only such rule lived on product_warehouse_stocks — the
     * table being dropped here — while products.stock_quantity, the number the
     * whole shop actually reads, had nothing behind the application code. Bulk
     * stock tools are exactly where an off-by-one turns into a negative, so the
     * last line of defence belongs on the column that matters.
     */
    private function guardProductStock(bool $add): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (! $add) {
            DB::statement('ALTER TABLE `products` DROP CHECK `products_stock_quantity_non_negative`');

            return;
        }

        $negative = (int) DB::table('products')->where('stock_quantity', '<', 0)->count();

        if ($negative > 0) {
            throw new RuntimeException(
                "Cannot guard stock_quantity: {$negative} product(s) already hold a negative stock. Correct them first."
            );
        }

        DB::statement('ALTER TABLE `products` ADD CONSTRAINT `products_stock_quantity_non_negative` CHECK (`stock_quantity` >= 0)');
    }
};
