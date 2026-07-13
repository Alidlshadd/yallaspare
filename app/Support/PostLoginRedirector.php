<?php

namespace App\Support;

use App\Http\Controllers\Auth\AdminTwoFactorController;
use App\Http\Controllers\Auth\UserTwoFactorController;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostLoginRedirector
{
    public function redirect(Request $request, ?User $user): RedirectResponse
    {
        if ($user && ($user->login_alerts ?? true)) {
            $timezone = in_array($user->timezone_preference, ['Asia/Baghdad', 'UTC'], true)
                ? $user->timezone_preference
                : config('app.timezone', 'UTC');

            $signedInAt = CarbonImmutable::now($timezone)->format('Y-m-d H:i');

            $request->session()->flash('success', __('Login alert: sign-in detected at :time (:timezone).', ['time' => $signedInAt, 'timezone' => $timezone]));
        }

        if ($user && $user->isAdminPanelUser()) {
            if (config('security.admin_two_factor.enabled')) {
                $mailAvailable = app(AdminTwoFactorController::class)->issueChallenge($request);

                $redirect = redirect()->route('admin.two-factor.challenge');

                if (! $mailAvailable) {
                    return $redirect->withErrors([
                        'code' => __('We could not send your admin verification code. Please contact support or try again shortly.'),
                    ]);
                }

                return $redirect;
            }

            return redirect()->intended('/admin/dashboard');
        }

        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user && empty($user->phone_normalized)) {
            return redirect()->route('user.phone.setup');
        }

        if ($user && (string) ($user->two_factor_preference ?? 'off') === 'email') {
            $mailAvailable = app(UserTwoFactorController::class)->issueChallenge($request);
            $redirect = redirect()->route('user.two-factor.challenge');

            if (! $mailAvailable) {
                return $redirect->withErrors([
                    'code' => __('We could not send your verification code. Please contact support or try again shortly.'),
                ]);
            }

            return $redirect;
        }

        $intendedUrl = (string) $request->session()->get('url.intended', '');
        if ($intendedUrl !== '' && str_starts_with($intendedUrl, url('/admin'))) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(route('user.shop.home'));
    }
}
