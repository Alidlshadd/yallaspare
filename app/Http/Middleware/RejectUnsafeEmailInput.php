<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectUnsafeEmailInput
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->flatten($request->except(['password', 'password_confirmation', 'current_password'])) as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            // Defense-in-depth for Laravel email-validation CRLF advisories:
            // any field intended to carry an email address must never contain
            // header/control line breaks, regardless of framework validator state.
            if ($this->isEmailCarrierField($key) && preg_match('/[\r\n]/', $value) === 1) {
                abort(422, __('Invalid email address.'));
            }
        }

        return $next($request);
    }

    private function isEmailCarrierField(string $key): bool
    {
        $name = strtolower((string) str($key)->afterLast('.'));

        return str_contains($name, 'email')
            || in_array($name, ['login', 'recipient', 'to', 'from', 'reply_to', 'reply-to', 'cc', 'bcc'], true);
    }

    private function flatten(array $input, string $prefix = ''): array
    {
        $flat = [];
        foreach ($input as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $flat += $this->flatten($value, $path);
                continue;
            }
            $flat[$path] = $value;
        }

        return $flat;
    }
}
