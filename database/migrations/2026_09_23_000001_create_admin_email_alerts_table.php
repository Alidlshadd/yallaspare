<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_email_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 100);
            $table->string('type', 30);
            $table->string('recipient');
            $table->string('locale', 5)->default('en');
            $table->string('subject');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->string('error_code')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['event_key', 'recipient']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_email_alerts');
    }
};
