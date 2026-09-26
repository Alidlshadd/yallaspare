<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogFailedLogin
{
    public function __construct(private readonly Request $request) {}

    public function handle(Failed $event): void
    {
        // Most attempts carry the address in the credentials. The web login
        // form attempts by primary key instead — an account made at express
        // checkout has no address to attempt with — so the resolved account
        // answers for it, and stays null when there genuinely is no address.
        $email = $event->credentials['email'] ?? $event->user?->getAttribute('email');
        $email = is_string($email) ? strtolower(trim($email)) : null;

        Log::channel('security')->warning('security event', [
            'event' => 'auth.failed',
            'guard' => (string) $event->guard,
            // Attempts often carry a password typed into the wrong box, and the
            // log outlives the attempt. The hash still ties every attempt at
            // one address together; the masked form is enough to read.
            'email_hash' => $email !== null ? hash('sha256', $email) : null,
            'email_masked' => $email !== null ? self::mask($email) : null,
            'user_id' => $event->user?->getAuthIdentifier(),
            'route' => $this->request->route()?->getName() ?? $this->request->path(),
            'ip' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 255),
        ]);
    }

    private static function mask(string $value): string
    {
        [$local, $domain] = array_pad(explode('@', $value, 2), 2, null);

        $shown = mb_substr((string) $local, 0, 2);

        return $domain === null ? $shown.'***' : $shown.'***@'.$domain;
    }
}
