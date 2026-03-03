<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'dealer_status')) {
                $table->string('dealer_status')->default('active')->after('role');
            }

            if (!Schema::hasColumn('users', 'dealer_discount')) {
                $table->decimal('dealer_discount', 5, 2)->default(0)->after('dealer_status');
            }
        });

        DB::table('users')
            ->whereNotIn('dealer_status', ['active', 'inactive', 'suspended'])
            ->update(['dealer_status' => 'active']);

        DB::table('users')
            ->where('dealer_discount', '<', 0)
            ->update(['dealer_discount' => 0]);

        DB::table('users')
            ->where('dealer_discount', '>', 100)
            ->update(['dealer_discount' => 100]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dealer_discount')) {
                $table->dropColumn('dealer_discount');
            }

            if (Schema::hasColumn('users', 'dealer_status')) {
                $table->dropColumn('dealer_status');
            }
        });
    }
};
