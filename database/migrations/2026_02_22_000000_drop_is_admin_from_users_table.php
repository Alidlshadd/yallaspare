<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        if ($this->indexExists('users', 'users_is_admin_idx')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_is_admin_idx');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        DB::table('users')
            ->whereIn('role', ['admin', 'super_admin'])
            ->update(['is_admin' => true]);

        DB::table('users')
            ->whereNotIn('role', ['admin', 'super_admin'])
            ->update(['is_admin' => false]);

        if (!$this->indexExists('users', 'users_is_admin_idx')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('is_admin', 'users_is_admin_idx');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            return DB::selectOne(
                'select 1 from pg_indexes where tablename = ? and indexname = ? limit 1',
                [$table, $index]
            ) !== null;
        }

        $database = $connection->getDatabaseName();

        return DB::selectOne(
            'select 1 from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? limit 1',
            [$database, $table, $index]
        ) !== null;
    }
};
