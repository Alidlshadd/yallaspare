<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the migration that removes the warehouse tables.
 *
 * Its first version named the tables it expected to find and missed three of
 * them, so production stopped half way through with `Cannot drop table
 * 'warehouses' referenced by foreign key`. The fix was to stop naming tables
 * and read them out of the live schema instead, and that is what these hold in
 * place.
 *
 * The destructive path is MySQL only and cannot be exercised here — the test
 * database is SQLite. What can be checked is the property whose absence caused
 * the outage: that nothing in the teardown depends on a list somebody has to
 * remember to update.
 */
class WarehouseTeardownMigrationTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_08_31_000001_drop_warehouse_tables_and_guard_stock.php';

    public function test_every_surviving_table_with_a_warehouse_key_is_found_in_the_schema_not_in_a_list(): void
    {
        // Tables that exist only for warehouses are dropped whole and are named
        // here on purpose. The ones this is about are the tables that survive
        // and merely carry a warehouse_id — orders, order_items,
        // purchase_invoices — the three the first version forgot.
        $survivors = array_values(array_diff(
            $this->tablesGivenAWarehouseForeignKey(),
            $this->tablesDroppedWhole()
        ));

        $this->assertGreaterThanOrEqual(
            3,
            count($survivors),
            'Expected several surviving tables to carry a warehouse_id; the parser below has probably stopped matching.'
        );

        $source = $this->migrationSource();

        foreach ($survivors as $table) {
            $this->assertStringNotContainsString(
                "'{$table}'",
                $source,
                "The teardown names `{$table}`. Naming tables is what made it miss purchase_invoices; ".
                'it has to find them in the schema instead.'
            );
        }
    }

    public function test_the_teardown_reads_the_schema_for_keys_and_columns(): void
    {
        $source = $this->migrationSource();

        $this->assertStringContainsString(
            'REFERENCED_TABLE_NAME',
            $source,
            'Foreign keys pointing at warehouses have to be discovered, not guessed from Laravel naming.'
        );

        $this->assertStringContainsString(
            'information_schema.COLUMNS',
            $source,
            'The tables carrying warehouse_id have to be discovered too.'
        );
    }

    public function test_the_parent_table_is_dropped_after_the_tables_that_point_at_it(): void
    {
        $tables = $this->tablesDroppedWhole();

        $this->assertContains('warehouses', $tables);
        $this->assertSame(
            'warehouses',
            end($tables),
            'warehouses is the parent; dropping it before its children is what MySQL refuses.'
        );
    }

    public function test_every_destructive_step_checks_before_it_acts(): void
    {
        // MySQL commits each DDL statement as it goes, so a failure half way
        // leaves the finished half behind and the migration unrecorded. The
        // next deploy runs it again on a database that is already partly
        // changed, and every step has to survive that.
        $source = $this->migrationSource();

        foreach ([
            'dropIfExists' => 'tables must be dropped only if present',
            "hasColumn(\$table, 'warehouse_id')" => 'columns must be dropped only if present',
            'checkConstraintExists' => 'the stock guard must not be added twice',
        ] as $needle => $why) {
            $this->assertStringContainsString($needle, $source, "Re-running is unsafe: {$why}.");
        }
    }

    public function test_it_does_nothing_at_all_on_a_database_that_is_not_mysql(): void
    {
        $this->assertNotSame('mysql', DB::connection()->getDriverName());

        $migration = require base_path(self::MIGRATION);

        // Twice, because a failed deploy runs it again.
        $migration->up();
        $migration->up();
        $migration->down();

        $this->assertTrue(true, 'The SQLite path must be a no-op rather than an error.');
    }

    /**
     * The tables the teardown removes entirely, in the order it removes them.
     *
     * @return array<int, string>
     */
    private function tablesDroppedWhole(): array
    {
        $migration = require base_path(self::MIGRATION);

        $tables = (new \ReflectionClass($migration))->getConstant('TABLES');

        $this->assertIsArray($tables, 'The teardown should keep its wholesale drops in one list.');

        return array_values($tables);
    }

    /**
     * Which tables the migration history gives a warehouse_id foreign key to.
     *
     * @return array<int, string>
     */
    private function tablesGivenAWarehouseForeignKey(): array
    {
        $owners = [];

        foreach (glob(base_path('database/migrations/*.php')) ?: [] as $file) {
            if (str_ends_with(str_replace('\\', '/', $file), self::MIGRATION)) {
                continue;
            }

            $contents = (string) file_get_contents($file);
            $table = null;

            foreach (preg_split('/\R/', $contents) ?: [] as $line) {
                if (preg_match("/Schema::(?:create|table)\(\s*'([a-z_]+)'/", $line, $matches) === 1) {
                    $table = $matches[1];
                }

                if ($table !== null && str_contains($line, "constrained('warehouses')")) {
                    $owners[] = $table;
                }
            }
        }

        return array_values(array_unique($owners));
    }

    private function migrationSource(): string
    {
        return (string) file_get_contents(base_path(self::MIGRATION));
    }
}
