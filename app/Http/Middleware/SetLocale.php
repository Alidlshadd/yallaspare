<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED_LOCALES = ['en', 'ar', 'ku'];

    /**
     * The visitor's language, kept outside the session.
     *
     * An address that matches no route never reaches this middleware or the
     * session, so its 404 page had no way to know the language and was always
     * English. This cookie is the answer the exception handler can read
     * without starting a session for every stray request. It holds two
     * letters and nothing else, which is why it is left unencrypted (see
     * EncryptCookies).
     */
    public const COOKIE = 'site_locale';

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

        $response = $next($request);

        // Whatever language the page ended up in — the session's, or a
        // signed-in customer's own preference applied further in. Pages only:
        // a cookie on an image or a download would stop it being cached.
        $shown = App::getLocale();

        if (
            in_array($shown, self::SUPPORTED_LOCALES, true)
            && $request->cookie(self::COOKIE) !== $shown
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
        ) {
            $response->headers->setCookie(cookie(self::COOKIE, $shown, 60 * 24 * 365));
        }

        return $response;
    }

    /**
     * The language for a request that never reached this middleware: the one
     * asked for in the address, else the one remembered in the cookie.
     */
    public static function forUnroutedRequest(Request $request): ?string
    {
        foreach ([$request->query('lang'), $request->cookies->get(self::COOKIE)] as $candidate) {
            if (is_string($candidate) && in_array($candidate, self::SUPPORTED_LOCALES, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
