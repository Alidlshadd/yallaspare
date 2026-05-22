<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'cancellation_requested_at')) {
                $table->timestamp('cancellation_requested_at')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancellation_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }

            if (Schema::hasColumn('orders', 'cancellation_requested_at')) {
                $table->dropColumn('cancellation_requested_at');
            }
        });
    }
};
