<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('title_en', 160);
            $table->string('title_ar', 160)->nullable();
            $table->string('title_ku', 160)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_ku')->nullable();
            $table->string('button_label_en', 60)->nullable();
            $table->string('button_label_ar', 60)->nullable();
            $table->string('button_label_ku', 60)->nullable();
            $table->string('button_url', 2048)->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->json('pages');
            $table->string('frequency', 20)->default('once_per_days');
            $table->unsignedSmallInteger('frequency_days')->default(7);
            $table->unsignedSmallInteger('delay_seconds')->default(3);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popups');
    }
};
