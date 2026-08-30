<?php

namespace App\Console\Commands;

use App\Services\Cart\AbandonedCartReminder;
use Illuminate\Console\Command;

class RemindAbandonedCarts extends Command
{
    protected $signature = 'carts:remind-abandoned';

    protected $description = 'Write to customers who filled a cart and did not check out';

    public function handle(AbandonedCartReminder $reminder): int
    {
        if (! (bool) config('cart_reminders.enabled', true)) {
            $this->info('Abandoned cart reminders are switched off.');

            return self::SUCCESS;
        }

        $sent = $reminder->run();

        $this->info("Reminded {$sent} customer(s) about an abandoned cart.");

        return self::SUCCESS;
    }
}
