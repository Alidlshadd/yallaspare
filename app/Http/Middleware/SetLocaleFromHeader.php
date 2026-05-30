<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    /**
     * Map BCP 47 language tags (as supplied by Accept-Language) to the
     * locale codes the app uses internally. The app uses 'ku' as its
     * locale code while the BCP 47 tag for Sorani Kurdish is 'ckb'.
     */
    private const TAG_TO_LOCALE = [
        'en' => 'en',
        'ar' => 'ar',
        'ku' => 'ku',
        'ckb' => 'ku',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Accept-Language', '');

        $locale = $this->resolveLocale($header);
        if ($locale !== null) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    private function resolveLocale(string $header): ?string
    {
        if ($header === '') {
            return null;
        }

        $preferences = [];
        foreach (explode(',', $header) as $segment) {
            $parts = explode(';', trim($segment));
            $tag = strtolower(trim($parts[0]));
            $quality = 1.0;
            foreach (array_slice($parts, 1) as $param) {
                $param = trim($param);
                if (str_starts_with($param, 'q=')) {
                    $quality = (float) substr($param, 2);
                }
            }
            if ($tag !== '') {
                $preferences[] = ['tag' => $tag, 'quality' => $quality];
            }
        }

        usort($preferences, fn ($a, $b) => $b['quality'] <=> $a['quality']);

        foreach ($preferences as $pref) {
            $primary = explode('-', $pref['tag'])[0];
            if (isset(self::TAG_TO_LOCALE[$primary])) {
                return self::TAG_TO_LOCALE[$primary];
            }
        }

        return null;
    }
}
