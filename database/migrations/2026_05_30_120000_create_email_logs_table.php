<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_hash', 64)->index();
            $table->string('recipient_domain', 191)->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('mailer', 80)->nullable();
            $table->string('mailable_class', 191)->nullable();
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('recipient_domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
