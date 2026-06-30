<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneAnalyticsEvents extends Command
{
    protected $signature = 'analytics:prune {--days=365 : Retain rows newer than this many days}';

    protected $description = 'Delete analytics_events rows older than the configured retention window';

    public function handle(): int
    {
        if (! Schema::hasTable('analytics_events')) {
            $this->warn('analytics_events table is missing. Nothing to prune.');

            return self::SUCCESS;
        }

        $days = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days);

        $deleted = DB::table('analytics_events')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} analytics event(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
