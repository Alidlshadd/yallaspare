<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'ar', 'ku'];

    public function handle(Request $request, Closure $next): Response
    {
        $queryLocale = (string) $request->query('lang', '');
        if ($queryLocale !== '' && in_array($queryLocale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($queryLocale);

            return $next($request);
        }

        $locale = $request->session()->get('locale', config('app.locale', 'en'));

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'en';
            $request->session()->put('locale', $locale);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
