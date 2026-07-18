<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use App\Support\VerificationRateLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PhoneVerificationPromptController extends Controller
{
    public const LAST_SENT_SESSION_KEY = 'phone_verification.last_sent_at';

    public function __construct(private readonly PhoneVerificationService $phoneVerification) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdminPanelUser()) {
            return redirect()->route('dashboard');
        }

        if (empty($user->phone_normalized)) {
            return redirect()->route('user.phone.setup');
        }

        if ($user->phone_verified_at !== null) {
            return $this->destination($request);
        }

        return view('auth.verify-phone', [
            'maskedPhone' => PhoneVerificationService::displayPhone((string) $user->phone_normalized),
            'resendCooldownSeconds' => $this->resendCooldownSeconds($request),
            'expiresInMinutes' => $this->phoneVerification->expiresInMinutes(),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user->phone_verified_at === null
            && ! $this->phoneVerification->confirmCode($user, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => __('user.phone_verification_invalid'),
            ]);
        }

        $request->session()->forget(self::LAST_SENT_SESSION_KEY);

        return $this->destination($request)
            ->with('success', __('user.phone_verification_complete'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->phone_verified_at !== null) {
            return $this->destination($request);
        }

        if (empty($user->phone_normalized)) {
            return redirect()->route('user.phone.setup');
        }

        if ($this->resendCooldownSeconds($request) > 0) {
            return back()->withErrors([
                'code' => __('Please wait a moment before requesting another verification code.'),
            ]);
        }

        if (! $this->phoneVerification->sendCode($user)) {
            return back()->withErrors([
                'code' => __('user.phone_verification_send_failed'),
            ]);
        }

        $request->session()->put(self::LAST_SENT_SESSION_KEY, now()->timestamp);

        return back()->with('status', __('user.phone_verification_sent', [
            'minutes' => $this->phoneVerification->expiresInMinutes(),
        ]));
    }

    private function destination(Request $request): RedirectResponse
    {
        // A verified phone alone activates the account, so there is no
        // follow-up email verification step here.
        return redirect()->intended(route('user.shop.home'));
    }

    private function resendCooldownSeconds(Request $request): int
    {
        $lastSentAt = (int) $request->session()->get(self::LAST_SENT_SESSION_KEY, 0);

        return max(
            $lastSentAt > 0 ? max(0, 60 - (now()->timestamp - $lastSentAt)) : 0,
            VerificationRateLimit::remainingSeconds($request),
        );
    }
}
