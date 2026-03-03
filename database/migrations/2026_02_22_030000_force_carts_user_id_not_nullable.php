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

        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE `carts` MODIFY `user_id` BIGINT UNSIGNED NULL');

        Schema::table('carts', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
