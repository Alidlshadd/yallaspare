<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 32)->default('cash_on_delivery')->after('status');
            }

            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 32)->default('pending')->after('payment_method');
            }

            if (! Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_status');
            }
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('orders', 'payment_status')) {
            DB::statement("ALTER TABLE orders MODIFY payment_status VARCHAR(32) NOT NULL DEFAULT 'pending'");
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('provider', 32);
                $table->string('method', 32);
                $table->string('status', 32)->default('pending');
                $table->decimal('amount', 15, 2);
                $table->string('currency', 3)->default('IQD');
                $table->string('provider_payment_id')->nullable()->index();
                $table->string('provider_transaction_id')->nullable()->index();
                $table->string('provider_reference')->nullable();
                $table->text('redirect_url')->nullable();
                $table->text('return_url')->nullable();
                $table->json('provider_response')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('webhook_received_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->string('failure_reason')->nullable();
                $table->timestamps();

                $table->index(['provider', 'status']);
                $table->index(['order_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
