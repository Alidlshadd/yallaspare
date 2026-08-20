<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governorates', function (Blueprint $table): void {
            $table->id();

            // The lasting identity. Names get corrected and transliterations
            // get argued over; anything that points at a governorate points
            // here instead.
            $table->string('code', 32)->unique();

            $table->string('name_en', 64);
            $table->string('name_ar', 64);
            $table->string('name_ku', 64);

            $table->unsignedTinyInteger('delivery_days')->default(3);

            // Whole dinars. The Iraqi dinar has no subunit in circulation, so
            // there is nothing for a decimal place to hold.
            $table->unsignedInteger('shipping_fee')->default(0);

            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governorates');
    }
};
