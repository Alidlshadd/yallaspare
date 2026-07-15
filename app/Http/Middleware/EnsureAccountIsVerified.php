<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces the framework "verified" middleware. Customers activate their
 * account by confirming ONE contact channel (email OR phone); admin panel
 * users still need a verified email.
 */
class EnsureAccountIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user instanceof MustVerifyEmail) {
            return $next($request);
        }

        $verified = $user->isAdminPanelUser()
            ? $user->hasVerifiedEmail()
            : ($user->hasVerifiedEmail() || $user->phone_verified_at !== null);

        if (! $verified) {
            return $request->expectsJson()
                ? abort(403, 'Your account is not verified.')
                : redirect()->guest(route('verification.notice'));
        }

        return $next($request);
    }
}
