<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user')->after('email');
            });
        }

        DB::table('users')->where('role', 'customer')->update(['role' => 'user']);
        DB::table('users')->where('role', 'manager')->update(['role' => 'dealer']);
        DB::table('users')->where('role', 'administrator')->update(['role' => 'admin']);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('role')->orWhere('role', '');
            })
            ->where('is_admin', true)
            ->update(['role' => 'admin']);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('role')->orWhere('role', '');
            })
            ->where('is_admin', false)
            ->update(['role' => 'user']);

        DB::table('users')
            ->whereNotIn('role', ['admin', 'user', 'dealer'])
            ->update(['role' => 'user']);

        DB::table('users')->where('role', 'admin')->update(['is_admin' => true]);
        DB::table('users')->where('role', '!=', 'admin')->update(['is_admin' => false]);

        try {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'user'");
        } catch (\Throwable $e) {
            // Ignore driver-specific ALTER syntax issues.
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'dealer')->update(['role' => 'manager']);
        DB::table('users')->where('role', 'user')->update(['role' => 'customer']);
        DB::table('users')->where('role', 'admin')->update(['is_admin' => true]);
        DB::table('users')->where('role', '!=', 'admin')->update(['is_admin' => false]);

        try {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'customer'");
        } catch (\Throwable $e) {
            // Ignore driver-specific ALTER syntax issues.
        }
    }
};
