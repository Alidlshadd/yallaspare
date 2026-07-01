<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key', 60);
            $table->string('locale', 8);
            $table->string('subject', 255);
            $table->longText('body_html');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['template_key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
