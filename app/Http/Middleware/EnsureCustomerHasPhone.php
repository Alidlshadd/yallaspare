<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerHasPhone
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdminPanelUser() && empty($user->phone_normalized)) {
            return redirect()->route('user.phone.setup');
        }

        return $next($request);
    }
}
