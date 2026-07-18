<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\AdminTwoFactorCode;
use App\Support\VerificationRateLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Throwable;

class AdminTwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminPanelUser()) {
            abort(403);
        }

        if (! config('security.admin_two_factor.enabled')) {
            return redirect()->intended('/admin/dashboard');
        }

        $mailAvailable = $this->ensureChallenge($request);

        return view('auth.admin-two-factor', [
            'mailAvailable' => $mailAvailable,
            'resendCooldownSeconds' => $this->resendCooldownSeconds($request),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminPanelUser()) {
            abort(403);
        }

        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $key = 'admin-2fa:' . $user->id . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Log::channel('security')->warning('security event', [
                'event' => 'auth.2fa_locked_attempt',
                'guard' => 'admin',
                'user_id' => $user->id,
                'route' => $request->route()?->getName() ?? $request->path(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            return back()->withErrors(['code' => __('Too many verification attempts. Please try again later.')]);
        }

        $challenge = $request->session()->get('admin_2fa.challenge');
        $expiresAt = (int) ($challenge['expires_at'] ?? 0);
        $hash = (string) ($challenge['hash'] ?? '');

        if ($expiresAt < now()->timestamp || ! Hash::check((string) $request->input('code'), $hash)) {
            RateLimiter::hit($key, 300);

            Log::channel('security')->warning('security event', [
                'event' => 'auth.2fa_failed',
                'guard' => 'admin',
                'user_id' => $user->id,
                'route' => $request->route()?->getName() ?? $request->path(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            return back()->withErrors(['code' => __('The verification code is invalid or expired.')]);
        }

        RateLimiter::clear($key);
        $request->session()->forget('admin_2fa.challenge');
        $request->session()->put('admin_2fa.verified_user_id', $user->id);

        return redirect()->intended('/admin/dashboard');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminPanelUser()) {
            abort(403);
        }

        if ($this->resendCooldownSeconds($request) > 0) {
            return back()->withErrors([
                'code' => __('Please wait a moment before requesting another verification code.'),
            ]);
        }

        if (! $this->issueChallenge($request)) {
            return back()->withErrors([
                'code' => __('We could not send a new verification code. Please contact support or try again shortly.'),
            ]);
        }

        return back()->with('status', __('A new verification code has been sent.'));
    }

    public function issueChallenge(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        $code = (string) random_int(100000, 999999);
        $ttl = max((int) config('security.admin_two_factor.code_ttl_minutes', 10), 1);

        $request->session()->put('admin_2fa.challenge', [
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl)->timestamp,
        ]);
        $request->session()->forget('admin_2fa.verified_user_id');

        try {
            $user->notify(new AdminTwoFactorCode($code, $ttl));
            $request->session()->put('admin_2fa.last_sent_at', now()->timestamp);

            return true;
        } catch (Throwable $e) {
            $request->session()->forget('admin_2fa.challenge');
            $mailer = (string) config('mail.default');
            $mailerConfig = (array) config("mail.mailers.{$mailer}", []);

            Log::error('Admin two-factor code email failed', [
                'user_id' => $user->id,
                'email_hash' => hash('sha256', strtolower((string) $user->email)),
                'mailer' => $mailer,
                'transport' => (string) ($mailerConfig['transport'] ?? $mailer),
                'host' => (string) ($mailerConfig['host'] ?? ''),
                'port' => (int) ($mailerConfig['port'] ?? 0),
                'encryption' => (string) ($mailerConfig['encryption'] ?? ''),
                'from' => (string) config('mail.from.address'),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function ensureChallenge(Request $request): bool
    {
        $challenge = $request->session()->get('admin_2fa.challenge');

        if (! is_array($challenge) || (int) ($challenge['expires_at'] ?? 0) < now()->timestamp) {
            return $this->issueChallenge($request);
        }

        return true;
    }

    private function resendCooldownSeconds(Request $request): int
    {
        $lastSentAt = (int) $request->session()->get('admin_2fa.last_sent_at', 0);

        return max(
            $lastSentAt > 0 ? max(0, 60 - (now()->timestamp - $lastSentAt)) : 0,
            VerificationRateLimit::remainingSeconds($request),
        );
    }
}
