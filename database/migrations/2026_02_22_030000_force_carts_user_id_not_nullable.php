<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('carts') || !Schema::hasColumn('carts', 'user_id')) {
            return;
        }

        // Ensure no NULLs before enforcing NOT NULL.
        if (DB::table('carts')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Cannot enforce NOT NULL: carts.user_id has NULL values.');
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteCartsTable(nullable: false);
            return;
        }

        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Hard enforce at MySQL level without DBAL.
        DB::statement('ALTER TABLE `carts` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('carts', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('carts') || !Schema::hasColumn('carts', 'user_id')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteCartsTable(nullable: true);
            return;
        }

        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE `carts` MODIFY `user_id` BIGINT UNSIGNED NULL');

        Schema::table('carts', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function rebuildSqliteCartsTable(bool $nullable): void
    {
        $nullClause = $nullable ? 'NULL' : 'NOT NULL';

        DB::statement('PRAGMA foreign_keys=OFF');
        DB::statement(<<<SQL
            CREATE TABLE carts_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INTEGER {$nullClause},
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        SQL);
        DB::statement('INSERT INTO carts_new (id, user_id, created_at, updated_at) SELECT id, user_id, created_at, updated_at FROM carts');
        DB::statement('DROP TABLE carts');
        DB::statement('ALTER TABLE carts_new RENAME TO carts');
        DB::statement('PRAGMA foreign_keys=ON');
    }
};
