<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * Carry a guest's cart into their account when they sign in.
 *
 * Signing in is not a reason to lose what you were buying, and it is the one
 * moment where the same person has two carts.
 */
class MergeGuestCartOnLogin
{
    public function __construct(private readonly CartService $carts) {}

    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->isAdminPanelUser()) {
            return;
        }

        try {
            $this->carts->mergeGuestCartInto($user);
        } catch (\Throwable $exception) {
            // A cart that will not merge is not a reason to refuse the login.
            Log::warning('Guest cart could not be merged on login.', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }
}
