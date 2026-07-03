<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Per-request random nonce. 16 bytes hex → 128 bits entropy,
        //    attribute-safe (base16 = [0-9a-f]).
        $nonce = bin2hex(random_bytes(16));

        // 2) Bind to Vite BEFORE $next(): @vite(...) reads Vite::cspNonce()
        //    during view render and auto-injects nonce="..." onto generated
        //    <script> and <link> tags.
        Vite::useCspNonce($nonce);

        // 3) Expose to Blade as $cspNonce for hand-edited inline <script> tags.
        view()->share('cspNonce', $nonce);

        // 4) Downstream chain runs with the nonce already registered.
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        // Explicitly disable the legacy XSS auditor: it is removed from modern
        // browsers and its filtering mode enabled XS-Leaks in older ones.
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('X-DNS-Prefetch-Control', 'off');
        $response->headers->set('X-Download-Options', 'noopen');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('Origin-Agent-Cluster', '?1');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), fullscreen=(self)'
        );

        // Common directives shared by BOTH enforced and Report-Only CSP.
        $commonDirectives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: blob:",
            "font-src 'self' data: https://fonts.bunny.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdnjs.cloudflare.com",
            "connect-src 'self'",
        ];

        // ENFORCED script-src (Faz 5B — nonce rollout complete).
        // - 'nonce-...' authorizes the per-request nonce tags (@vite output +
        //   hand-edited inline scripts + dashboard Chart.js).
        // - 'strict-dynamic' makes host-source allowlists (including 'self' and
        //   https://cdn.jsdelivr.net) IGNORED under CSP3; nonced scripts become
        //   roots and transitively trust what they insert. 'self' and the
        //   jsdelivr host stay listed only as a fallback for CSP2 browsers.
        // - 'unsafe-eval' REMAINS: Alpine 3's default build compiles x-*/@event
        //   expressions via new Function(); strict-dynamic does NOT authorize
        //   inline directive eval. Removing it requires Alpine's CSP build.
        // - 'unsafe-inline' is NOT listed — browsers ignore it when a nonce is
        //   present anyway; omitting it avoids misleading CSP evaluators.
        $enforcedScriptSrc = "script-src 'nonce-{$nonce}' 'strict-dynamic' 'unsafe-eval' 'self' https://cdn.jsdelivr.net";

        $csp = array_merge($commonDirectives, [
            $enforcedScriptSrc,
            // Keep violation reporting on the enforced policy so regressions
            // surface in the security log.
            'report-uri /csp-report',
            'report-to csp-endpoint',
        ]);

        if (app()->environment('production') && $request->isSecure()) {
            $csp[] = 'upgrade-insecure-requests';
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        $response->headers->set('Reporting-Endpoints', 'csp-endpoint="/csp-report"');

        if ($this->isSensitivePath($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    private function isSensitivePath(Request $request): bool
    {
        return $request->is(
            'login',
            'register',
            'forgot-password',
            'reset-password*',
            'verify-email*',
            'admin*',
            'account*',
            'profile*',
            'cart*',
            'checkout*',
            'user/account*',
            'user/settings*'
        );
    }
}
