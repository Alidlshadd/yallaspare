<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Memoized schema existence checks. Positive results are cached across
 * requests; negative results are re-checked on every new application
 * instance so freshly run migrations are picked up immediately.
 *
 * IMPORTANT: after `php artisan migrate:rollback` (a table/column being
 * REMOVED) you must run `php artisan cache:clear`, otherwise the stale
 * positive entry keeps reporting the dropped table/column as present.
 * deploy/deploy.sh already runs cache:clear right after migrations.
 */
final class DbSchema
{
    /** @var array<string, bool> */
    private array $tables = [];

    /** @var array<string, bool> */
    private array $columns = [];

    public static function hasTable(string $table): bool
    {
        return self::instance()->tableExists($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return self::instance()->columnExists($table, $column);
    }

    public static function flush(): void
    {
        app()->forgetInstance(self::class);
    }

    private static function instance(): self
    {
        $app = app();

        if (! $app->bound(self::class)) {
            $app->instance(self::class, new self());
        }

        return $app->make(self::class);
    }

    private function tableExists(string $table): bool
    {
        if (isset($this->tables[$table])) {
            return $this->tables[$table];
        }

        $key = 'db_schema.table.' . $table;
        $exists = Cache::get($key) === true;

        if (! $exists) {
            $exists = Schema::hasTable($table);

            if ($exists) {
                Cache::forever($key, true);
            }
        }

        return $this->tables[$table] = $exists;
    }

    private function columnExists(string $table, string $column): bool
    {
        $memoKey = $table . '.' . $column;

        if (isset($this->columns[$memoKey])) {
            return $this->columns[$memoKey];
        }

        $key = 'db_schema.column.' . $memoKey;
        $exists = Cache::get($key) === true;

        if (! $exists) {
            $exists = Schema::hasColumn($table, $column);

            if ($exists) {
                Cache::forever($key, true);
            }
        }

        return $this->columns[$memoKey] = $exists;
    }
}
