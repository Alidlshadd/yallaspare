<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\OtpiqSmsService;
use App\Support\PhoneVerificationCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PhoneVerificationController extends Controller
{
    public function send(Request $request, OtpiqSmsService $sms): RedirectResponse
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

        $code = PhoneVerificationCode::generateFor($user);

        try {
            $sms->sendVerification($user, $code);
        } catch (Throwable $exception) {
            PhoneVerificationCode::forgetFor($user);

            Log::warning('Phone verification SMS could not be sent.', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'phone_verification' => __('user.phone_verification_send_failed'),
            ]);
        }

        return back()->with('phone_verification_sent', __('user.phone_verification_sent', [
            'minutes' => PhoneVerificationCode::expiresIn(),
        ]));
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_code' => ['required', 'string'],
        ]);

        $user = $request->user();
        $code = PhoneVerificationCode::normalize((string) $request->input('verification_code'));

        if (strlen($code) !== 6 || ! PhoneVerificationCode::verifyFor($user, $code)) {
            throw ValidationException::withMessages([
                'verification_code' => __('user.phone_verification_invalid'),
            ]);
        }

        $user->forceFill(['phone_verified_at' => now()])->save();

        return back()->with('phone_verification_success', __('user.phone_verification_complete'));
    }
}
