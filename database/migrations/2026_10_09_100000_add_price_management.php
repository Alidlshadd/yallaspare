<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Price management: finer dollar prices, and a record of every change.
     *
     * No product's price is changed here. Dollar amounts already on file
     * keep their value; the columns only gain room for two more decimals,
     * which a price set in dinars ("17,000") needs in order to come back as
     * exactly that many dinars once it is stored in dollars.
     */
    public function up(): void
    {
        // SQLite keeps whatever precision it is given and cannot alter a
        // column in place; only a real decimal column needs widening.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('products', function (Blueprint $table): void {
                $table->decimal('price_usd', 14, 4)->nullable()->change();
                $table->decimal('dealer_price_usd', 14, 4)->nullable()->change();
                $table->decimal('cost_price_usd', 14, 4)->nullable()->change();
            });

            Schema::table('order_items', function (Blueprint $table): void {
                $table->decimal('usd_unit_price', 14, 4)->nullable()->change();
            });

            Schema::table('manual_invoice_items', function (Blueprint $table): void {
                $table->decimal('usd_unit_price', 14, 4)->nullable()->change();
            });
        }

        if (! Schema::hasTable('product_price_changes')) {
            Schema::create('product_price_changes', function (Blueprint $table): void {
                $table->id();
                // The product may be deleted later; its name and code are
                // kept on the row so the history still says what it was.
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->string('product_name')->nullable();
                $table->string('product_sku', 120)->nullable();
                // One id for every row written by the same operation.
                $table->uuid('batch_id')->index();
                // conversion | edit | bulk
                $table->string('action', 20)->index();
                $table->string('old_currency', 3);
                $table->string('new_currency', 3);
                $table->decimal('old_price_iqd', 15, 2)->nullable();
                $table->decimal('new_price_iqd', 15, 2)->nullable();
                $table->decimal('old_price_usd', 14, 4)->nullable();
                $table->decimal('new_price_usd', 14, 4)->nullable();
                $table->decimal('old_dealer_price_iqd', 15, 2)->nullable();
                $table->decimal('new_dealer_price_iqd', 15, 2)->nullable();
                $table->decimal('old_dealer_price_usd', 14, 4)->nullable();
                $table->decimal('new_dealer_price_usd', 14, 4)->nullable();
                // The rate old dinar prices were divided by; conversions only.
                $table->decimal('conversion_rate_per_100', 12, 2)->nullable();
                // The selling rate in force when the change was made.
                $table->decimal('selling_rate_per_100', 12, 2)->nullable();
                $table->string('note')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        // New products start on dollars from now on. Only the starting
        // choice on the form: existing products are not reinterpreted.
        DB::table('settings')->updateOrInsert(
            ['key' => 'default_price_currency'],
            ['value' => 'USD', 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_changes');

        DB::table('settings')->where('key', 'default_price_currency')->update(['value' => 'IQD']);
    }
};
