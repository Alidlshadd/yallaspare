<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    private const GENERIC_RESET_LINK_STATUS = 'If this email exists, we sent a reset link.';

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = (string) $request->input('email');

        try {
            $status = Password::sendResetLink(['email' => $email]);

            Log::debug('Password reset link request processed.', [
                'email_hash' => hash('sha256', mb_strtolower(trim($email))),
                'email_domain' => $this->emailDomain($email),
                'broker' => config('auth.defaults.passwords'),
                'mailer' => config('mail.default'),
                'queue_connection' => config('queue.default'),
                'status' => $status,
            ]);
        } catch (Throwable $exception) {
            Log::error('Password reset link mail failed.', [
                'email_hash' => hash('sha256', mb_strtolower(trim($email))),
                'email_domain' => $this->emailDomain($email),
                'broker' => config('auth.defaults.passwords'),
                'mailer' => config('mail.default'),
                'queue_connection' => config('queue.default'),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', __(self::GENERIC_RESET_LINK_STATUS));
    }

    private function emailDomain(string $email): string
    {
        $domain = strrchr($email, '@');

        return $domain === false ? '' : strtolower(substr($domain, 1));
    }
}
