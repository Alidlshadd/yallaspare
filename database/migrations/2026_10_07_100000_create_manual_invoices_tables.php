<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // People the shop sells to in person or over the phone. Deliberately
        // not `users`: nobody here has an account, a password or an inbox.
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            // E.164, so one number is one row however it was typed.
            $table->string('phone', 20)->unique();
            $table->string('whatsapp', 20)->nullable();
            $table->string('city', 120)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('manual_invoices', function (Blueprint $table) {
            $table->id();
            // Assigned from the id straight after insert.
            $table->string('number', 32)->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // What the invoice says about the customer, kept on the invoice so
            // a later edit to the directory cannot rewrite a document that has
            // already been handed over.
            $table->string('customer_name', 160);
            $table->string('customer_phone', 20);
            $table->string('customer_whatsapp', 20)->nullable();
            $table->string('customer_city', 120)->nullable();
            $table->text('customer_address')->nullable();

            $table->string('status', 16)->default('draft');
            $table->string('payment_status', 16)->default('unpaid');
            $table->date('invoice_date');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();

            // The link sent to the customer. The hash is what a visitor is
            // looked up by; the token itself is stored encrypted only so staff
            // can send the same link again. Clearing both revokes it.
            $table->string('share_token_hash', 64)->nullable()->unique();
            $table->text('share_token')->nullable();
            $table->timestamp('shared_at')->nullable();

            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'invoice_date']);
            $table->index('payment_status');
            $table->index('customer_phone');
        });

        Schema::create('manual_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_invoice_id')->constrained('manual_invoices')->cascadeOnDelete();
            // Null for a service or a part that is not in the catalogue, and
            // nulled if the product is later deleted: the line keeps its own
            // description, code and price either way.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description', 255);
            $table->string('sku', 120)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_invoice_items');
        Schema::dropIfExists('manual_invoices');
        Schema::dropIfExists('customers');
    }
};
