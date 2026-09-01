<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Let an order line stand on its own.
 *
 * An order line held nothing but a product id, so what a customer bought was
 * only ever readable through the product still existing. That made the
 * catalogue and the sales history the same record: deleting a part rewrote
 * what an old invoice said it was, and the delete screen worked around it by
 * quietly refusing — a product that had ever been sold was flipped inactive
 * and reported as "archived" instead.
 *
 * The name and the code are copied onto the line here, the way the price
 * already was. After this an order line says what was sold, at what price, for
 * all time, whatever later happens to the catalogue.
 *
 * product_id stays, and stays useful — it just stops being load-bearing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_id');
            }

            if (! Schema::hasColumn('order_items', 'product_sku')) {
                $table->string('product_sku')->nullable()->after('product_name');
            }
        });

        $this->backfill();
        $this->allowNullProduct();
    }

    public function down(): void
    {
        // The columns are left in place: dropping them would throw away the
        // only copy of what was sold on any line whose product has since been
        // deleted, which is exactly what this migration exists to prevent.
    }

    /**
     * Copy what the catalogue still knows onto every line that has no copy yet.
     */
    private function backfill(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        DB::table('order_items')
            ->whereNull('product_name')
            ->whereNotNull('product_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $product = DB::table('products')
                        ->where('id', $row->product_id)
                        ->first(['name_en', 'sku']);

                    if (! $product) {
                        continue;
                    }

                    DB::table('order_items')
                        ->where('id', $row->id)
                        ->update([
                            'product_name' => $product->name_en,
                            'product_sku' => $product->sku,
                        ]);
                }
            });
    }

    /**
     * A line may outlive its product, so the column has to accept null.
     *
     * The delete itself clears the column before removing the product, so no
     * foreign key rule ever fires — but the column must be able to hold the
     * null for that to work, whichever rule is on it.
     *
     * MySQL does not roll DDL back, so a failure part-way through leaves the
     * table half-changed and this migration unrecorded. Every step below reads
     * the live schema and does nothing if it is already the way it should be,
     * which makes a second run a no-op rather than a second error.
     */
    private function allowNullProduct(): void
    {
        if (! Schema::hasColumn('order_items', 'product_id')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            // SQLite is rebuilt by the schema builder, foreign key and all.
            Schema::table('order_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('product_id')->nullable()->change();
            });

            return;
        }

        $existing = $this->foreignKey('order_items', 'product_id');

        if ($this->isNullable('order_items', 'product_id')
            && $existing !== null
            && strtoupper((string) $existing->rule) === 'SET NULL') {
            return;
        }

        if ($existing !== null) {
            DB::statement("ALTER TABLE `order_items` DROP FOREIGN KEY `{$existing->name}`");
        }

        if (! $this->isNullable('order_items', 'product_id')) {
            DB::statement('ALTER TABLE `order_items` MODIFY `product_id` BIGINT UNSIGNED NULL');
        }

        // Belt as well as braces: the delete clears the column itself, and if
        // some other path ever removes a product directly, the line survives
        // with its snapshot rather than being cascaded away.
        $name = $this->availableConstraintName('order_items_product_id_foreign');

        DB::statement(
            "ALTER TABLE `order_items`
             ADD CONSTRAINT `{$name}`
             FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL"
        );
    }

    /**
     * The foreign key on a column, with the rule it applies on delete.
     */
    private function foreignKey(string $table, string $column): ?object
    {
        return DB::selectOne(
            'SELECT k.CONSTRAINT_NAME AS name, r.DELETE_RULE AS rule
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS r
               ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
              AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
             WHERE k.CONSTRAINT_SCHEMA = DATABASE()
               AND k.TABLE_NAME = ?
               AND k.COLUMN_NAME = ?
             LIMIT 1',
            [$table, $column]
        );
    }

    private function isNullable(string $table, string $column): bool
    {
        $row = DB::selectOne(
            'SELECT IS_NULLABLE AS nullable
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            [$table, $column]
        );

        return strtoupper((string) ($row->nullable ?? '')) === 'YES';
    }

    /**
     * Foreign key names are unique across the whole schema in InnoDB, so the
     * preferred name may be spoken for by a table this migration never touches.
     */
    private function availableConstraintName(string $preferred): string
    {
        $name = $preferred;

        for ($suffix = 2; $suffix < 20; $suffix++) {
            $taken = DB::selectOne(
                'SELECT 1 AS found
                 FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND CONSTRAINT_NAME = ?',
                [$name]
            );

            if ($taken === null) {
                return $name;
            }

            $name = $preferred.'_'.$suffix;
        }

        return $preferred.'_'.uniqid();
    }
};
