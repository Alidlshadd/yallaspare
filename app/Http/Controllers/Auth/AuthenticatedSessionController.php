<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\AdminTwoFactorController;
use App\Http\Requests\Auth\LoginRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user && ($user->login_alerts ?? true)) {
            $timezone = in_array($user->timezone_preference, ['Asia/Baghdad', 'UTC'], true)
                ? $user->timezone_preference
                : config('app.timezone', 'UTC');

            $signedInAt = CarbonImmutable::now($timezone)->format('Y-m-d H:i');

            $request->session()->flash('success', 'Login alert: sign-in detected at ' . $signedInAt . ' (' . $timezone . ').');
        }

        if ($user && $user->isAdminPanelUser()) {
            if (config('security.admin_two_factor.enabled')) {
                $mailAvailable = app(AdminTwoFactorController::class)->issueChallenge($request);

                $redirect = redirect()->route('admin.two-factor.challenge');

                if (! $mailAvailable) {
                    return $redirect->withErrors([
                        'code' => 'We could not send your admin verification code. Please contact support or try again shortly.',
                    ]);
                }

                return $redirect;
            }

            return redirect()->intended('/admin/dashboard');
        }

        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        $intendedUrl = (string) $request->session()->get('url.intended', '');
        if ($intendedUrl !== '' && str_starts_with($intendedUrl, url('/admin'))) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(route('user.shop.home'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->forget('admin_2fa');

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
