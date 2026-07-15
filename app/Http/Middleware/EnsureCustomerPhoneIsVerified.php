<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerPhoneIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // One verified contact channel is enough: a customer who confirmed
        // their email is not forced through phone verification as well.
        if (
            $user
            && ! $user->isAdminPanelUser()
            && filled($user->phone_normalized)
            && $user->phone_verified_at === null
            && ! $user->hasVerifiedEmail()
        ) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
