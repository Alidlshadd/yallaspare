<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CleanupAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old audit logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = 60;
        $cutoff = now()->subDays($retentionDays);

        // Prefer spatie/laravel-activitylog table when present, otherwise fallback to custom audit_logs.
        $table = null;
        if (Schema::hasTable('activity_log')) {
            $table = 'activity_log';
        } elseif (Schema::hasTable('audit_logs')) {
            $table = 'audit_logs';
        }

        // If neither table exists, exit gracefully to avoid crashes during Artisan boot.
        if ($table === null) {
            $this->warn('No audit log table found (activity_log or audit_logs). Nothing to clean.');

            return self::SUCCESS;
        }

        // Bulk delete for efficiency; returns 0 if the table is empty.
        $deleted = DB::table($table)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} audit log(s) from {$table} older than {$retentionDays} day(s).");

        return self::SUCCESS;
    }
}
