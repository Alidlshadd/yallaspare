<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience_type', 20);
            $table->string('audience_role', 40)->nullable();
            $table->string('purpose', 30)->default('promotional');
            $table->string('subject', 160);
            $table->text('message');
            $table->string('action_url', 2048)->nullable();
            $table->string('action_text', 80)->nullable();
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['audience_type', 'audience_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_broadcasts');
    }
};
