<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Support\UserCommunication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Writes to customers who filled a cart and then stopped.
 *
 * The cycle is driven entirely by two facts already in the database: when the
 * cart was last touched, and when we last wrote about it. Nothing has to reset
 * anything — a customer who adds another part moves the cart's last activity
 * forward, which puts `carts.reminded_at` behind it, and the cart counts as
 * fresh again. See Cart::lastActivityAt().
 *
 * Only customers who gave marketing consent are ever written to. Guest carts
 * are unreachable by design: a cart with no account has a browser token and
 * nothing else, no address of any kind to write to.
 */
class AbandonedCartReminder
{
    /**
     * Send every reminder that has come due.
     *
     * @return int how many customers were written to
     */
    public function run(): int
    {
        if (! (bool) config('cart_reminders.enabled', true)) {
            return 0;
        }

        $stages = $this->stages();

        if ($stages === []) {
            return 0;
        }

        $oldestAllowed = Carbon::now()->subDays(max(1, (int) config('cart_reminders.max_age_days', 7)));
        $limit = max(1, (int) config('cart_reminders.max_per_run', 200));
        $sent = 0;

        $this->candidates()->chunkById(100, function ($carts) use ($stages, $oldestAllowed, $limit, &$sent): ?bool {
            foreach ($carts as $cart) {
                if ($sent >= $limit) {
                    return false;
                }

                if ($this->remind($cart, $stages, $oldestAllowed)) {
                    $sent++;
                }
            }

            return null;
        });

        return $sent;
    }

    /**
     * Carts that could conceivably be written to. Whether one is actually due
     * is a question about timestamps, answered per cart below.
     *
     * @return Builder<Cart>
     */
    private function candidates(): Builder
    {
        return Cart::query()
            ->whereNotNull('user_id')
            ->whereHas('items')
            ->whereHas('user', function ($query): void {
                $query->where('marketing_consent', true)
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->whereNotNull('email_verified_at');
            })
            ->with(['items.product', 'user'])
            ->orderBy('id');
    }

    /**
     * @param  array<int, float>  $stages
     */
    private function remind(Cart $cart, array $stages, Carbon $oldestAllowed): bool
    {
        $lastActivity = $cart->lastActivityAt();

        if ($lastActivity === null || $lastActivity->lt($oldestAllowed)) {
            return false;
        }

        $stage = $this->stageReached($cart, $lastActivity);

        if ($stage >= count($stages)) {
            return false;
        }

        $due = $lastActivity->copy()->addMinutes((int) round($stages[$stage] * 60));

        if (Carbon::now()->lt($due)) {
            return false;
        }

        if (! $this->claim($cart, $stage + 1)) {
            return false;
        }

        $user = $cart->user;
        $channels = $user ? UserCommunication::sendAbandonedCart($user, $cart) : [];

        if ($channels === []) {
            // Nothing went out — a disabled channel, a mailer that refused.
            // Put the cart back where it was so the next run tries again
            // rather than counting a message nobody received.
            $this->releaseClaim($cart);

            return false;
        }

        return true;
    }

    /**
     * How many reminders this cart has had for its current contents.
     *
     * A `reminded_at` older than the cart's last activity belongs to an
     * earlier cycle: the customer has touched the cart since, so the count
     * starts over.
     */
    private function stageReached(Cart $cart, Carbon $lastActivity): int
    {
        if ($cart->reminded_at === null || $cart->reminded_at->lt($lastActivity)) {
            return 0;
        }

        return max(0, (int) $cart->reminder_stage);
    }

    /**
     * Take the cart before writing to it, and only if nothing else has.
     *
     * Two overlapping runs would otherwise both see the same due cart and
     * both write about it. The stamp is what is claimed, so the loser's
     * update matches no row.
     */
    private function claim(Cart $cart, int $stage): bool
    {
        $claimed = Cart::query()
            ->whereKey($cart->getKey())
            ->when(
                $cart->reminded_at === null,
                fn ($query) => $query->whereNull('reminded_at'),
                fn ($query) => $query->where('reminded_at', $cart->reminded_at)
            )
            ->update([
                'reminder_stage' => $stage,
                'reminded_at' => Carbon::now(),
            ]);

        return $claimed === 1;
    }

    private function releaseClaim(Cart $cart): void
    {
        Cart::query()
            ->whereKey($cart->getKey())
            ->update([
                'reminder_stage' => (int) $cart->reminder_stage,
                'reminded_at' => $cart->reminded_at,
            ]);
    }

    /**
     * The configured delays, in hours, in the order they fire.
     *
     * @return array<int, float>
     */
    private function stages(): array
    {
        $stages = array_values(array_filter(
            array_map(
                static fn ($hours): float => (float) $hours,
                (array) config('cart_reminders.stages', [])
            ),
            static fn (float $hours): bool => $hours > 0
        ));

        sort($stages);

        return $stages;
    }
}
