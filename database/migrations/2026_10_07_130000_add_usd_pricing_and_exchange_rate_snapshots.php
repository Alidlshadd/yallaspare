<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dollar pricing, and the rate written down wherever a price is recorded.
     *
     * Nothing existing is rewritten. Every product already on file becomes a
     * dinar product through the column default and keeps the price it has;
     * orders and invoices already saved get empty columns, which read as
     * "priced in dinars, no rate involved".
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'price_currency')) {
                $table->string('price_currency', 3)->default('IQD')->after('price')->index();
            }
            // The price as the owner typed it. `price` and `dealer_price`
            // stay what they always were: dinars, now derived from these
            // for a dollar product.
            if (! Schema::hasColumn('products', 'price_usd')) {
                $table->decimal('price_usd', 12, 2)->nullable()->after('price_currency');
            }
            if (! Schema::hasColumn('products', 'dealer_price_usd')) {
                $table->decimal('dealer_price_usd', 12, 2)->nullable()->after('price_usd');
            }
            // What the shop paid for the part. The dinar column has been in
            // the schema, unused, since the early catalogue migrations; it
            // is only created here where an install somehow lacks it.
            if (! Schema::hasColumn('products', 'cost_price')) {
                $table->decimal('cost_price', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('products', 'cost_price_usd')) {
                $table->decimal('cost_price_usd', 12, 2)->nullable();
            }
        });

        // What the customer was last shown, so a change can be pointed out.
        Schema::table('cart_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('cart_items', 'seen_unit_price')) {
                $table->decimal('seen_unit_price', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('cart_items', 'seen_usd_rate')) {
                $table->decimal('seen_usd_rate', 12, 2)->nullable();
            }
        });

        Schema::table('order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_items', 'price_currency')) {
                $table->string('price_currency', 3)->nullable();
            }
            if (! Schema::hasColumn('order_items', 'usd_unit_price')) {
                $table->decimal('usd_unit_price', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('order_items', 'usd_rate_per_100')) {
                $table->decimal('usd_rate_per_100', 12, 2)->nullable();
            }
            // Cost per unit in dinars on the day of the sale, so profit on an
            // old order does not move when the cost is edited later. Empty
            // on orders placed before this, and on products with no cost.
            if (! Schema::hasColumn('order_items', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->nullable();
            }
        });

        Schema::table('manual_invoice_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('manual_invoice_items', 'usd_unit_price')) {
                $table->decimal('usd_unit_price', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('manual_invoice_items', 'usd_rate_per_100')) {
                $table->decimal('usd_rate_per_100', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('manual_invoice_items', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('manual_invoice_items', function (Blueprint $table): void {
            $table->dropColumn(['usd_unit_price', 'usd_rate_per_100', 'unit_cost']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['price_currency', 'usd_unit_price', 'usd_rate_per_100', 'unit_cost']);
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropColumn(['seen_unit_price', 'seen_usd_rate']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['price_currency']);
            // cost_price stays: it predates this migration.
            $table->dropColumn(['price_currency', 'price_usd', 'dealer_price_usd', 'cost_price_usd']);
        });
    }
};
