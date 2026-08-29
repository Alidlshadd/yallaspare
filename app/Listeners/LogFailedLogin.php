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
            'email' => $email,
            'user_id' => $event->user?->getAuthIdentifier(),
            'route' => $this->request->route()?->getName() ?? $this->request->path(),
            'ip' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 255),
        ]);
    }
}
