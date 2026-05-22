<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserPreferences
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $locale = $user->locale_preference;
            $timezone = $user->timezone_preference;
            $sessionTimeout = (int) ($user->session_timeout ?? 30);

            if (! $request->session()->has('locale') && in_array($locale, ['en', 'ar', 'ku'], true)) {
                app()->setLocale($locale);
            }

            if (in_array($timezone, ['Asia/Baghdad', 'UTC'], true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }

            if (in_array($sessionTimeout, [15, 30, 60, 120], true)) {
                config(['session.lifetime' => $sessionTimeout]);
            }
        }

        return $next($request);
    }
}
