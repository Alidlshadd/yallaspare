<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Money received against an invoice, one row per time it changed
        // hands. A refund is a row with a negative amount, so what was paid
        // and what was given back both stay on the record.
        Schema::create('manual_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_invoice_id')->constrained('manual_invoices')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('method', 40)->nullable();
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('paid_on');
        });

        Schema::table('manual_invoices', function (Blueprint $table) {
            // The sum of the payment rows, kept here so lists and totals do
            // not have to add them up. payment_status is derived from it.
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total');
            $table->timestamp('voided_at')->nullable()->after('finalized_by');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason', 500)->nullable()->after('voided_by');
        });

        // Until now "paid" was only a label. An invoice marked paid becomes
        // one payment for its full total, dated the day of the invoice, so
        // it stays paid under the new rules.
        $paid = DB::table('manual_invoices')->where('payment_status', 'paid')->where('total', '>', 0)->get(['id', 'total', 'invoice_date']);

        foreach ($paid as $invoice) {
            DB::table('manual_invoice_payments')->insert([
                'manual_invoice_id' => $invoice->id,
                'amount' => $invoice->total,
                'paid_on' => $invoice->invoice_date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('manual_invoices')->where('id', $invoice->id)->update(['paid_amount' => $invoice->total]);
        }
    }

    public function down(): void
    {
        Schema::table('manual_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['paid_amount', 'voided_at', 'void_reason']);
        });

        Schema::dropIfExists('manual_invoice_payments');
    }
};
