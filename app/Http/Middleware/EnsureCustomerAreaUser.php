<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAreaUser
{
    /**
     * Keep admin-panel accounts out of customer-only commerce and account pages.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdminPanelUser()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
