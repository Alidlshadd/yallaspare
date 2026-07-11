<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'csp-report',
        // Apple sends the OAuth callback as a cross-site form POST, so no
        // CSRF token (or session cookie) accompanies the request.
        'auth/apple/callback',
    ];
}
