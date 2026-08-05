<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otpiq_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type')->nullable();
            $table->unsignedInteger('attempt_number')->nullable();
            $table->string('webhook_timestamp')->nullable();
            $table->boolean('signature_verified')->default(false);
            $table->string('processing_status', 24)->default('received')->index();
            $table->string('sender_phone')->nullable()->index();
            $table->string('sender_name')->nullable();
            $table->string('external_message_id')->nullable()->index();
            $table->string('message_type')->nullable();
            $table->text('message_text')->nullable();
            $table->json('raw_payload');
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->index(['processing_status', 'received_at'], 'otpiq_events_status_received_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otpiq_webhook_events');
    }
};
