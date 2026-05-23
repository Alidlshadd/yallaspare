<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\AdminTwoFactorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

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

        $this->ensureChallenge($request);

        return view('auth.admin-two-factor');
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
            return back()->withErrors(['code' => __('Too many verification attempts. Please try again later.')]);
        }

        $challenge = $request->session()->get('admin_2fa.challenge');
        $expiresAt = (int) ($challenge['expires_at'] ?? 0);
        $hash = (string) ($challenge['hash'] ?? '');

        if ($expiresAt < now()->timestamp || ! Hash::check((string) $request->input('code'), $hash)) {
            RateLimiter::hit($key, 300);

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

        $this->issueChallenge($request);

        return back()->with('status', __('A new verification code has been sent.'));
    }

    public function issueChallenge(Request $request): void
    {
        $code = (string) random_int(100000, 999999);
        $ttl = max((int) config('security.admin_two_factor.code_ttl_minutes', 10), 1);

        $request->session()->put('admin_2fa.challenge', [
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl)->timestamp,
        ]);
        $request->session()->forget('admin_2fa.verified_user_id');

        $request->user()->notify(new AdminTwoFactorCode($code, $ttl));
    }

    private function ensureChallenge(Request $request): void
    {
        $challenge = $request->session()->get('admin_2fa.challenge');

        if (! is_array($challenge) || (int) ($challenge['expires_at'] ?? 0) < now()->timestamp) {
            $this->issueChallenge($request);
        }
    }
}
