<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_provider_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32)->index();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_id')->nullable()->index();
            $table->string('event_type', 40)->index();
            $table->string('http_method', 10);
            $table->string('endpoint', 500);
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->string('result', 80)->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('safe_message')->nullable();
            $table->json('request_metadata')->nullable();
            $table->json('response_metadata')->nullable();
            $table->timestamps();

            $table->index(['provider', 'created_at'], 'provider_logs_provider_created_idx');
            $table->index(['provider', 'event_type', 'created_at'], 'provider_logs_event_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_provider_logs');
    }
};
