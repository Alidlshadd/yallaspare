<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user() ?? $request->user('sanctum');

        if (! $user || ! $user->isBanned()) {
            return $next($request);
        }

        $message = $this->banMessage($user);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    private function banMessage(User $user): string
    {
        $message = $user->isPermanentlyBanned()
            ? __('Your account has been permanently suspended.')
            : __('Your account is suspended until :date.', [
                'date' => $user->banned_until?->format('d M Y H:i'),
            ]);

        $reason = trim((string) $user->ban_reason);
        if ($reason !== '') {
            $message .= ' ' . __('Reason: :reason', ['reason' => $reason]);
        }

        return $message;
    }
}
