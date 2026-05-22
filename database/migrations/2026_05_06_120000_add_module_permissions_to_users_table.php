<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'permissions')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->json('permissions')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'permissions')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('permissions');
        });
    }
};
