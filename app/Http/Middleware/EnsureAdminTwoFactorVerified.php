<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.admin_two_factor.enabled')) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || ! $user->isAdminPanelUser()) {
            return $next($request);
        }

        if ($request->session()->get('admin_2fa.verified_user_id') === $user->id) {
            return $next($request);
        }

        return redirect()->route('admin.two-factor.challenge');
    }
}
