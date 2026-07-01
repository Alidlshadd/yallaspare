<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\UserTwoFactorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Throwable;

class UserTwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $this->requiresChallenge($request)) {
            return redirect()->intended(route('user.shop.home'));
        }

        $mailAvailable = $this->ensureChallenge($request);

        $challenge = $request->session()->get('user_2fa.challenge');
        $codeExpiresAt = (int) ($challenge['expires_at'] ?? 0);

        return view('auth.user-two-factor', [
            'mailAvailable' => $mailAvailable,
            'resendCooldownSeconds' => $this->resendCooldownSeconds($request),
            'maskedEmail' => $this->maskedEmail((string) $user->email),
            'codeExpiresAt' => $codeExpiresAt,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $key = 'user-2fa:' . $user->id . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Log::channel('security')->warning('security event', [
                'event' => 'auth.2fa_locked_attempt',
                'guard' => 'user',
                'user_id' => $user->id,
                'route' => $request->route()?->getName() ?? $request->path(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            return back()->withErrors(['code' => __('Too many verification attempts. Please try again later.')]);
        }

        $challenge = $request->session()->get('user_2fa.challenge');
        $expiresAt = (int) ($challenge['expires_at'] ?? 0);
        $hash = (string) ($challenge['hash'] ?? '');

        if ($expiresAt < now()->timestamp || ! Hash::check((string) $request->input('code'), $hash)) {
            RateLimiter::hit($key, 300);

            Log::channel('security')->warning('security event', [
                'event' => 'auth.2fa_failed',
                'guard' => 'user',
                'user_id' => $user->id,
                'route' => $request->route()?->getName() ?? $request->path(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            return back()->withErrors(['code' => __('The verification code is invalid or expired.')]);
        }

        RateLimiter::clear($key);
        $request->session()->forget('user_2fa.challenge');
        $request->session()->put('user_2fa.verified_user_id', $user->id);

        return redirect()->intended(route('user.shop.home'));
    }

    public function resend(Request $request): RedirectResponse
    {
        if (! $this->requiresChallenge($request)) {
            return redirect()->intended(route('user.shop.home'));
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
        $ttl = max((int) config('security.user_two_factor.code_ttl_minutes', 10), 1);

        $request->session()->put('user_2fa.challenge', [
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl)->timestamp,
        ]);
        $request->session()->forget('user_2fa.verified_user_id');

        try {
            $user->notify(new UserTwoFactorCode($code, $ttl));
            $request->session()->put('user_2fa.last_sent_at', now()->timestamp);

            return true;
        } catch (Throwable $e) {
            $request->session()->forget('user_2fa.challenge');

            Log::error('User two-factor code email failed', [
                'user_id' => $user->id,
                'email_hash' => hash('sha256', strtolower((string) $user->email)),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function requiresChallenge(Request $request): bool
    {
        $user = $request->user();

        return $user
            && ! $user->isAdminPanelUser()
            && (string) ($user->two_factor_preference ?? 'off') === 'email'
            && $request->session()->get('user_2fa.verified_user_id') !== $user->id;
    }

    private function ensureChallenge(Request $request): bool
    {
        $challenge = $request->session()->get('user_2fa.challenge');

        if (! is_array($challenge) || (int) ($challenge['expires_at'] ?? 0) < now()->timestamp) {
            return $this->issueChallenge($request);
        }

        return true;
    }

    private function resendCooldownSeconds(Request $request): int
    {
        $lastSentAt = (int) $request->session()->get('user_2fa.last_sent_at', 0);

        if ($lastSentAt < 1) {
            return 0;
        }

        return max(0, 60 - (now()->timestamp - $lastSentAt));
    }

    private function maskedEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email, 2);

        return substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 1)) . '@' . $domain;
    }
}
