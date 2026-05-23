<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IntrusionPrevention
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.intrusion_prevention.enabled')) {
            return $next($request);
        }

        $ip = $request->ip() ?: 'unknown';
        $blockKey = $this->blockKey($ip);

        if (Cache::has($blockKey)) {
            abort(429, 'Request blocked by security policy.');
        }

        $score = $this->score($request);

        if ($score > 0) {
            $hitKey = $this->hitKey($ip);
            $hits = (int) Cache::get($hitKey, 0) + $score;
            Cache::put($hitKey, $hits, now()->addMinutes((int) config('security.intrusion_prevention.window_minutes', 10)));

            Log::warning('Intrusion prevention signal detected', [
                'ip' => $ip,
                'path' => $request->path(),
                'method' => $request->method(),
                'score' => $score,
                'hits' => $hits,
            ]);

            if ($hits >= (int) config('security.intrusion_prevention.max_score', 8)) {
                Cache::put(
                    $blockKey,
                    true,
                    now()->addMinutes((int) config('security.intrusion_prevention.block_minutes', 30))
                );

                abort(429, 'Request blocked by security policy.');
            }
        }

        return $next($request);
    }

    private function score(Request $request): int
    {
        if ($this->isExcluded($request)) {
            return 0;
        }

        $payload = strtolower($request->path() . ' ' . $request->getQueryString() . ' ' . json_encode($request->except([
            'password',
            'password_confirmation',
            'current_password',
            '_token',
        ]), JSON_THROW_ON_ERROR));

        $score = 0;
        foreach ((array) config('security.intrusion_prevention.patterns', []) as $pattern) {
            if (@preg_match($pattern, $payload) === 1) {
                $score += 3;
            }
        }

        foreach ((array) config('security.intrusion_prevention.probe_paths', []) as $probe) {
            if (str_contains($payload, strtolower((string) $probe))) {
                $score += 2;
            }
        }

        return $score;
    }

    private function isExcluded(Request $request): bool
    {
        foreach ((array) config('security.intrusion_prevention.excluded_paths', []) as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    private function hitKey(string $ip): string
    {
        return 'ips:hits:' . sha1($ip);
    }

    private function blockKey(string $ip): string
    {
        return 'ips:block:' . sha1($ip);
    }
}
