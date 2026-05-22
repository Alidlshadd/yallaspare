<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->after('discount_amount')->constrained('coupons')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code', 80)->nullable()->after('coupon_id');
            }

            if (! Schema::hasColumn('orders', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('cancellation_reason')->index();
            }
        });

        if (! Schema::hasTable('order_admin_notes')) {
            Schema::create('order_admin_notes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('note');
                $table->timestamps();

                $table->index(['order_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('return_requests')) {
            Schema::create('return_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('type', ['return', 'exchange', 'refund'])->default('return');
                $table->enum('status', ['requested', 'approved', 'rejected', 'received', 'refunded', 'closed'])->default('requested');
                $table->text('reason');
                $table->text('admin_note')->nullable();
                $table->decimal('refund_amount', 12, 2)->nullable();
                $table->timestamp('requested_at')->useCurrent();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'requested_at']);
                $table->index(['order_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('order_admin_notes');

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'coupon_id')) {
                $table->dropConstrainedForeignId('coupon_id');
            }

            foreach (['coupon_code', 'archived_at'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
