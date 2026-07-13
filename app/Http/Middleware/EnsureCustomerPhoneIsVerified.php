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

        if (
            $user
            && ! $user->isAdminPanelUser()
            && filled($user->phone_normalized)
            && $user->phone_verified_at === null
        ) {
            return redirect()->route('phone.verify');
        }

        return $next($request);
    }
}
