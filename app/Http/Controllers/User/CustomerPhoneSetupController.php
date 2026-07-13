<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Auth\PhoneVerificationPromptController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IraqiMobileNumber;
use App\Services\PhoneVerificationService;
use App\Support\IraqiPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerPhoneSetupController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Users with an unverified phone may still come back here to correct
        // a mistyped number before requesting another verification code.
        if (filled($user?->phone_normalized) && $user->phone_verified_at !== null) {
            return redirect()->intended(route('user.shop.home'));
        }

        return view('auth.add-phone');
    }

    public function store(Request $request, PhoneVerificationService $phoneVerification): RedirectResponse
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

        if (! $phoneVerification->sendCode($user)) {
            return redirect()->route('phone.verify')
                ->with('success', __('Phone number added successfully.'))
                ->withErrors([
                    'code' => __('user.phone_verification_send_failed'),
                ]);
        }

        $request->session()->put(PhoneVerificationPromptController::LAST_SENT_SESSION_KEY, now()->timestamp);

        return redirect()->route('phone.verify')
            ->with('success', __('Phone number added successfully.'))
            ->with('status', __('user.phone_verification_sent', [
                'minutes' => $phoneVerification->expiresInMinutes(),
            ]));
    }
}
