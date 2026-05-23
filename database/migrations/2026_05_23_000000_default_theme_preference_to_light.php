<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'theme_preference')) {
            return;
        }

        DB::table('users')
            ->whereNull('theme_preference')
            ->orWhere('theme_preference', '')
            ->orWhere('theme_preference', 'system')
            ->update(['theme_preference' => 'light']);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY theme_preference VARCHAR(20) NOT NULL DEFAULT 'light'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN theme_preference SET DEFAULT 'light'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'theme_preference')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY theme_preference VARCHAR(20) NOT NULL DEFAULT 'system'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN theme_preference SET DEFAULT 'system'");
        }
    }
};
