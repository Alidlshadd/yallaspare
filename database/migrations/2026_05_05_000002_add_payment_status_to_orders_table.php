<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 32)->default('pending')->after('payment_method');
            }

            if (! Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }

            if (Schema::hasColumn('orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
