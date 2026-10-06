<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phone numbers were already stored in E.164 and are left exactly as
        // they are. Every customer so far could only have had an Iraqi number,
        // so Iraq is the right country for the rows that exist.
        Schema::table('customers', function (Blueprint $table) {
            $table->char('country', 2)->default('IQ')->after('whatsapp');
        });

        Schema::table('manual_invoices', function (Blueprint $table) {
            $table->char('customer_country', 2)->default('IQ')->after('customer_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('manual_invoices', function (Blueprint $table) {
            $table->dropColumn('customer_country');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
