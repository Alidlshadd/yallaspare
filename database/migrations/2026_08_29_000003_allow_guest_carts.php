<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A cart belongs to an account or to a browser.
 *
 * `carts.user_id` was made NOT NULL in February, and rightly so at the time:
 * there was no other kind of cart, and a null there could only be a bug. A
 * visitor can now fill a cart before deciding whether to have an account, so
 * the column goes back to nullable and a session token stands in its place.
 *
 * Exactly one of the two is set. Nothing in the schema can express that, so
 * App\Services\Cart\CartService is the only thing that creates carts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('carts')) {
            return;
        }

        if (! Schema::hasColumn('carts', 'session_token')) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->string('session_token', 64)->nullable()->unique()->after('user_id');
            });
        }

        $this->setUserIdNullable(true);
    }

    public function down(): void
    {
        if (! Schema::hasTable('carts')) {
            return;
        }

        // Guest carts cannot survive a column that will not hold them.
        DB::table('carts')->whereNull('user_id')->delete();

        $this->setUserIdNullable(false);

        if (Schema::hasColumn('carts', 'session_token')) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->dropUnique(['session_token']);
                $table->dropColumn('session_token');
            });
        }
    }

    private function setUserIdNullable(bool $nullable): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteCartsTable($nullable);

            return;
        }

        Schema::table('carts', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE `carts` MODIFY `user_id` BIGINT UNSIGNED '.($nullable ? 'NULL' : 'NOT NULL'));

        Schema::table('carts', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * SQLite cannot alter a column's nullability, so the table is rebuilt —
     * the same approach the migration that made this column NOT NULL used.
     */
    private function rebuildSqliteCartsTable(bool $nullable): void
    {
        $nullClause = $nullable ? 'NULL' : 'NOT NULL';

        DB::statement('PRAGMA foreign_keys=OFF');
        DB::statement(<<<SQL
            CREATE TABLE carts_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INTEGER {$nullClause},
                session_token VARCHAR(64) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        SQL);
        DB::statement('INSERT INTO carts_new (id, user_id, session_token, created_at, updated_at) SELECT id, user_id, session_token, created_at, updated_at FROM carts');
        DB::statement('DROP TABLE carts');
        DB::statement('ALTER TABLE carts_new RENAME TO carts');
        DB::statement('CREATE UNIQUE INDEX carts_session_token_unique ON carts (session_token)');
        DB::statement('PRAGMA foreign_keys=ON');
    }
};
