<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogFailedLogin
{
    public function __construct(private readonly Request $request)
    {
    }

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
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
