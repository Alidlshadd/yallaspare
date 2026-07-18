<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\IraqiPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    private const GENERIC_RESET_LINK_STATUS = 'If an account matches these details, we sent a reset link to its registered email.';

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
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Keep accepting the previous `email` field for mobile clients and
        // older forms while the web UI moves to the broader `login` field.
        $request->merge([
            'login' => trim((string) ($request->input('login') ?? $request->input('email'))),
        ]);

        $validated = $request->validate([
            'login' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $login = is_string($value) ? trim($value) : '';

                    if (preg_match('/[\r\n]/', $login)
                        || (! filter_var($login, FILTER_VALIDATE_EMAIL) && IraqiPhoneNumber::digits($login) === null)) {
                        $fail(__('Enter a valid email address or Iraqi mobile number.'));
                    }
                },
            ],
        ]);

        $login = (string) $validated['login'];
        $email = $this->emailForLogin($login);
        $identifierType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        try {
            $status = $email === null
                ? Password::INVALID_USER
                : Password::sendResetLink(['email' => $email]);

            Log::debug('Password reset link request processed.', [
                'identifier_hash' => hash('sha256', mb_strtolower($login)),
                'identifier_type' => $identifierType,
                'email_domain' => $email !== null ? $this->emailDomain($email) : '',
                'broker' => config('auth.defaults.passwords'),
                'mailer' => config('mail.default'),
                'queue_connection' => config('queue.default'),
                'status' => $status,
            ]);
        } catch (Throwable $exception) {
            Log::error('Password reset link mail failed.', [
                'identifier_hash' => hash('sha256', mb_strtolower($login)),
                'identifier_type' => $identifierType,
                'email_domain' => $email !== null ? $this->emailDomain($email) : '',
                'broker' => config('auth.defaults.passwords'),
                'mailer' => config('mail.default'),
                'queue_connection' => config('queue.default'),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', __(self::GENERIC_RESET_LINK_STATUS));
    }

    private function emailForLogin(string $login): ?string
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return $login;
        }

        $normalizedPhone = IraqiPhoneNumber::digits($login);

        if ($normalizedPhone === null) {
            return null;
        }

        $candidates = [$normalizedPhone];

        if (str_starts_with($normalizedPhone, '964') && strlen($normalizedPhone) === 13) {
            $nationalNumber = substr($normalizedPhone, 3);
            $candidates[] = $nationalNumber;
            $candidates[] = '0'.$nationalNumber;
        }

        return User::query()
            ->whereIn('phone_normalized', array_unique($candidates))
            ->value('email');
    }

    private function emailDomain(string $email): string
    {
        $domain = strrchr($email, '@');

        return $domain === false ? '' : strtolower(substr($domain, 1));
    }
}
