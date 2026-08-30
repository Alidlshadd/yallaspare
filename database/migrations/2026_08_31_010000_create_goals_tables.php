<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('period_type', 16);
            $table->date('period_start');
            $table->date('start_date');
            $table->date('deadline');
            $table->string('tracking_mode', 16);
            $table->string('metric_key', 48)->nullable();
            $table->json('metric_config')->nullable();
            $table->string('direction', 16)->default('increase');
            $table->decimal('baseline_value', 20, 2)->default(0);
            $table->decimal('target_value', 20, 2);
            $table->decimal('manual_value', 20, 2)->default(0);
            $table->string('unit', 24);
            $table->string('priority', 16)->default('medium');
            $table->unsignedInteger('reward_points')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['period_type', 'period_start'], 'goals_period_lookup_idx');
            $table->index(['tracking_mode', 'metric_key'], 'goals_tracking_metric_idx');
            $table->index('deadline', 'goals_deadline_idx');
        });

        Schema::create('goal_progress_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->decimal('value', 20, 2);
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['goal_id', 'recorded_at'], 'goal_updates_history_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_progress_updates');
        Schema::dropIfExists('goals');
    }
};
