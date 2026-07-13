<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Auth\UserTwoFactorController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IraqiMobileNumber;
use App\Support\IraqiPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerPhoneSetupController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (filled($request->user()?->phone_normalized)) {
            return redirect()->intended(route('user.shop.home'));
        }

        return view('auth.add-phone');
    }

    public function store(Request $request, UserTwoFactorController $twoFactor): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'country_code' => ['required', Rule::in(['+964'])],
            'phone' => ['required', 'string', 'max:30', new IraqiMobileNumber(), User::uniquePhoneRule($user->id)],
        ]);

        $user->forceFill([
            'phone' => IraqiPhoneNumber::toE164($validated['phone']),
            'phone_verified_at' => null,
        ])->save();

        if ((string) ($user->two_factor_preference ?? 'off') === 'email') {
            $sent = $twoFactor->issueChallenge($request, 'email');
            $redirect = redirect()->route('user.two-factor.challenge');

            return $sent
                ? $redirect->with('status', __('Phone number saved. We sent a verification code to your email.'))
                : $redirect->withErrors(['code' => __('We could not send your verification code. Please try again shortly.')]);
        }

        return redirect()->intended(route('user.shop.home'))
            ->with('success', __('Phone number added successfully.'));
    }
}
