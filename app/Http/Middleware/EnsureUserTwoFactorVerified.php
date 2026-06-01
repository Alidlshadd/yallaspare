<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && ! $user->isAdminPanelUser()
            && (string) ($user->two_factor_preference ?? 'off') === 'email'
            && $request->session()->get('user_2fa.verified_user_id') !== $user->id
        ) {
            return redirect()->route('user.two-factor.challenge');
        }

        return $next($request);
    }
}
