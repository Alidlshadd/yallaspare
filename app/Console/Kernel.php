<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('logs:cleanup')
            ->monthlyOn(1, '03:00')
            ->withoutOverlapping();

        $schedule->command('analytics:prune')
            ->dailyAt('03:00')
            ->withoutOverlapping();

        // Hourly rather than to the minute: the delays are measured in hours,
        // so an hour of slack costs nothing. The window is in Baghdad time,
        // not the application's UTC, because it exists to keep a marketing
        // mail from arriving at four in the morning where the customer is.
        $schedule->command('carts:remind-abandoned')
            ->hourly()
            ->timezone('Asia/Baghdad')
            ->between('08:00', '21:00')
            ->withoutOverlapping();

        // Everything else here can be fixed by deploying again. This is the
        // one that cannot, so it runs before the day's traffic and is given
        // room not to collide with itself on a slow night.
        $schedule->command('db:backup')
            ->dailyAt('02:00')
            ->withoutOverlapping(120)
            ->runInBackground();

        // Failed jobs are kept long enough to be read and retried, then let
        // go, so the table does not grow without end.
        $schedule->command('queue:prune-failed --hours=336')
            ->weeklyOn(1, '04:00')
            ->withoutOverlapping();

        $schedule->command('queue:alert-failed')
            ->hourly()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
