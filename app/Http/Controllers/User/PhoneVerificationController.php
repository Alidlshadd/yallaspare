<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    public function __construct(private readonly PhoneVerificationService $phoneVerification) {}

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->phone_verified_at !== null) {
            return back()->with('phone_verification_success', __('user.phone_already_verified'));
        }

        if (empty($user->phone_normalized)) {
            throw ValidationException::withMessages([
                'phone_verification' => __('user.phone_required_for_verification'),
            ]);
        }

        if (! $this->phoneVerification->sendCode($user)) {
            return back()->withErrors([
                'phone_verification' => __('user.phone_verification_send_failed'),
            ]);
        }

        return back()->with('phone_verification_sent', __('user.phone_verification_sent', [
            'minutes' => $this->phoneVerification->expiresInMinutes(),
        ]));
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $this->phoneVerification->confirmCode($user, (string) $request->input('verification_code'))) {
            throw ValidationException::withMessages([
                'verification_code' => __('user.phone_verification_invalid'),
            ]);
        }

        return back()->with('phone_verification_success', __('user.phone_verification_complete'));
    }
}
