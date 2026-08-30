<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_metric_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('period_type', 16);
            $table->date('period_start');
            $table->string('metric_key', 48);
            $table->string('metric_config_hash', 64)->default('none');
            $table->json('metric_config')->nullable();
            $table->decimal('value', 20, 2);
            $table->date('captured_on');
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->unique(['period_type', 'period_start', 'metric_key', 'metric_config_hash', 'captured_on'], 'goal_snapshots_daily_unique');
            $table->index(['metric_key', 'captured_at'], 'goal_snapshots_metric_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_metric_snapshots');
    }
};
