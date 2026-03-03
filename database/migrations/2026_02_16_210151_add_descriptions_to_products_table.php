<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('products', function (Blueprint $table) {

    if (!Schema::hasColumn('products', 'description_en')) {
        $table->text('description_en')->nullable();
    }

    if (!Schema::hasColumn('products', 'description_ar')) {
        $table->text('description_ar')->nullable();
    }

    if (!Schema::hasColumn('products', 'description_ku')) {
        $table->text('description_ku')->nullable();
    }

});

    
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
