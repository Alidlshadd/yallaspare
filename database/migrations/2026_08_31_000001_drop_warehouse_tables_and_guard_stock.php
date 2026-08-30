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
 * The first version of this migration named the tables it expected to find and
 * missed four — orders, order_items and purchase_invoices all carry a
 * warehouse_id, and production stopped mid-way with `Cannot drop table
 * 'warehouses' referenced by foreign key`. So nothing is named here any more.
 * Every foreign key pointing at `warehouses`, and every column called
 * warehouse_id, is found in the live schema and removed in dependency order.
 * A list written by hand can be short; the schema cannot.
 *
 * It is also safe to run again. MySQL commits each DDL statement as it goes, so
 * a migration that fails half way leaves the finished half behind and is never
 * recorded as run — the next deploy starts it over, on a database that is
 * already partly changed. Every step here checks before it acts.
 *
 * MySQL only, the way allow_guest_checkout_orders already is. SQLite refuses to
 * drop a column a foreign key names, and unpicking that would mean restating
 * four tables that as many migrations have shaped.
 */
return new class extends Migration
{
    /**
     * Tables that exist only to serve warehouses.
     */
    private const TABLES = [
        'stock_transactions',
        'product_warehouse_stocks',
        'warehouses',
    ];

    private const CHECK_CONSTRAINT = 'products_stock_quantity_non_negative';

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Read the shape of things before anything is touched, so the columns
        // are known even after the keys that point at them are gone.
        $columnOwners = array_values(array_diff($this->tablesWithWarehouseColumn(), self::TABLES));

        $this->refuseUnlessWarehousesAreEmpty($columnOwners);

        // 1. Every key that points at `warehouses`, wherever it lives. Until
        //    these are gone the parent table cannot be dropped, which is
        //    exactly what stopped the first deploy.
        foreach ($this->foreignKeysReferencingWarehouses() as $key) {
            DB::statement("ALTER TABLE `{$key->table}` DROP FOREIGN KEY `{$key->name}`");
        }

        // 2. The columns themselves, on the tables that survive. MySQL takes a
        //    dropped column out of any index it was part of, so the composite
        //    indexes keep their remaining columns rather than disappearing.
        foreach ($columnOwners as $table) {
            if (Schema::hasColumn($table, 'warehouse_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropColumn('warehouse_id');
                });
            }
        }

        // 3. The tables that only ever existed for warehouses, children first.
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        $this->guardProductStock();
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // The tables are not rebuilt: they were empty, nothing wrote to them,
        // and the migration that first created them is still in place for a
        // database that has never run this one.
        if ($this->checkConstraintExists()) {
            DB::statement('ALTER TABLE `products` DROP CHECK `'.self::CHECK_CONSTRAINT.'`');
        }
    }

    /**
     * Stop rather than delete.
     *
     * These tables are empty everywhere they have been looked at, but "empty on
     * my machine" is not "empty on the server", and a dropped table takes its
     * rows with it. Everything is counted first, and everything that is not
     * empty is reported at once — one deploy run should tell the whole story
     * rather than reveal it one failure at a time.
     *
     * @param  array<int, string>  $columnOwners
     */
    private function refuseUnlessWarehousesAreEmpty(array $columnOwners): void
    {
        $holdings = [];

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = (int) DB::table($table)->count();

            if ($rows > 0) {
                $holdings[] = "`{$table}` holds {$rows} row(s)";
            }
        }

        foreach ($columnOwners as $table) {
            if (! Schema::hasColumn($table, 'warehouse_id')) {
                continue;
            }

            $assigned = (int) DB::table($table)->whereNotNull('warehouse_id')->count();

            if ($assigned > 0) {
                $holdings[] = "`{$table}` has {$assigned} row(s) assigned to a warehouse";
            }
        }

        if ($holdings !== []) {
            throw new RuntimeException(
                'Refusing to remove the warehouse tables: '.implode('; ', $holdings).'. '.
                'The feature was believed unused. Export what is there and remove this migration, '.
                'or empty it deliberately. Nothing has been changed.'
            );
        }
    }

    /**
     * Every foreign key in this database that points at `warehouses`.
     *
     * Read from the schema rather than guessed from Laravel's naming
     * convention: a key created by hand, or renamed, would be missed by a
     * guess and would block the drop all the same.
     *
     * @return array<int, object{table: string, name: string}>
     */
    private function foreignKeysReferencingWarehouses(): array
    {
        /** @var array<int, object{table: string, name: string}> $keys */
        $keys = DB::select(
            'SELECT DISTINCT TABLE_NAME AS `table`, CONSTRAINT_NAME AS `name`
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME = ?',
            ['warehouses']
        );

        return $keys;
    }

    /**
     * Every table in this database carrying a warehouse_id column.
     *
     * @return array<int, string>
     */
    private function tablesWithWarehouseColumn(): array
    {
        $rows = DB::select(
            'SELECT DISTINCT TABLE_NAME AS `table`
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND COLUMN_NAME = ?',
            ['warehouse_id']
        );

        return array_map(static fn (object $row): string => (string) $row->table, $rows);
    }

    /**
     * A stock that cannot go below zero, enforced by the database.
     *
     * Until now the only such rule lived on product_warehouse_stocks — one of
     * the tables being dropped here — while products.stock_quantity, the number
     * the whole shop actually reads, had nothing behind the application code.
     * Bulk stock tools are exactly where an off-by-one turns into a negative,
     * so the last line of defence belongs on the column that matters.
     */
    private function guardProductStock(): void
    {
        if (! Schema::hasTable('products') || $this->checkConstraintExists()) {
            return;
        }

        $negative = (int) DB::table('products')->where('stock_quantity', '<', 0)->count();

        if ($negative > 0) {
            throw new RuntimeException(
                "Cannot guard stock_quantity: {$negative} product(s) already hold a negative stock. Correct them first."
            );
        }

        DB::statement('ALTER TABLE `products` ADD CONSTRAINT `'.self::CHECK_CONSTRAINT.'` CHECK (`stock_quantity` >= 0)');
    }

    private function checkConstraintExists(): bool
    {
        $found = DB::select(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?',
            ['products', self::CHECK_CONSTRAINT]
        );

        return $found !== [];
    }
};
