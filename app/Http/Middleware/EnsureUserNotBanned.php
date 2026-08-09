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

        $message = $user->banMessage();

        // Token requests reach this middleware through the api stack, which has
        // no session to invalidate — answer them as JSON even when the client
        // forgot to send an Accept header.
        if ($request->expectsJson() || ! $request->hasSession()) {
            return response()->json(['message' => $message], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
