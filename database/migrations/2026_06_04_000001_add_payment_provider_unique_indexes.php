<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->unique(['provider', 'provider_payment_id'], 'payments_provider_payment_unique');
            $table->unique(['provider', 'provider_transaction_id'], 'payments_provider_transaction_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_provider_payment_unique');
            $table->dropUnique('payments_provider_transaction_unique');
        });
    }
};
