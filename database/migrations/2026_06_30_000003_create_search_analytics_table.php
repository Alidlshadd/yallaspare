<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_analytics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('keyword', 80)->unique();
            $table->unsignedInteger('search_count')->default(1);
            $table->timestamp('last_searched_at');
            $table->timestamps();

            $table->index('search_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_analytics');
    }
};
