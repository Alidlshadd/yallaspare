<?php

namespace App\Support;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * One welcome per account, sent when the account is first verified.
 *
 * Not at registration: an unverified address may belong to someone else, and
 * a welcome is the first thing a squatted address would receive. Every
 * verification path (email code, signed link, phone code, social sign-in)
 * saves the user, so the model's saved hook is the one place that sees all
 * of them.
 */
class WelcomeEmail
{
    public static function sendIfDue(User $user): void
    {
        if (
            $user->email === null
            || ! $user->hasVerifiedAccount()
            || $user->welcome_email_sent_at !== null
            || $user->isAdminPanelUser()
            || ! DbSchema::hasColumn('users', 'welcome_email_sent_at')
        ) {
            return;
        }

        // Claim the send in one statement, so two saves racing each other
        // (a double-clicked verify button) cannot both queue a welcome.
        $now = now();
        $claimed = User::query()
            ->whereKey($user->getKey())
            ->whereNull('welcome_email_sent_at')
            ->update(['welcome_email_sent_at' => $now]);

        if ($claimed !== 1) {
            return;
        }

        $user->forceFill(['welcome_email_sent_at' => $now])->syncOriginalAttribute('welcome_email_sent_at');

        try {
            Mail::to($user)->queue(new WelcomeMail($user));
        } catch (\Throwable $exception) {
            // A welcome is a courtesy; it must never break verification.
            Log::warning('Welcome email could not be queued', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
