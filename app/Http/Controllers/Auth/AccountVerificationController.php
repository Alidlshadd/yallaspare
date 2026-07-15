<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpiqSmsService;
use App\Services\PhoneVerificationService;
use App\Support\EmailVerificationCode;
use App\Support\VerificationChannels;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * One verification activates the account: the code is emailed by default and
 * the user may switch delivery to SMS or WhatsApp. Confirming an email code
 * marks the email verified; confirming an SMS/WhatsApp code marks the phone
 * verified. Either one satisfies the customer verification gates.
 */
class AccountVerificationController extends Controller
{
    public const CHANNEL_SESSION_KEY = 'account_verification.channel';
    public const LAST_SENT_SESSION_KEY = 'account_verification.last_sent_at';

    public function __construct(
        private readonly PhoneVerificationService $phoneVerification,
        private readonly OtpiqSmsService $sms,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($this->isVerified($user)) {
            return $this->destination($user);
        }

        $options = $this->channelOptions($user);
        $channel = $this->currentChannel($request, $options);

        return view('auth.verify-account', [
            'currentChannel' => $channel,
            'currentChannelLabel' => $options[$channel]['label'],
            'maskedDestination' => $options[$channel]['destination'],
            'channelOptions' => $options,
            'resendCooldownSeconds' => $this->resendCooldownSeconds($request),
            'expiresInMinutes' => $channel === 'email'
                ? EmailVerificationCode::expiresIn()
                : $this->phoneVerification->expiresInMinutes(),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->isVerified($user)) {
            return $this->destination($user);
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $channel = $this->currentChannel($request, $this->channelOptions($user));
        $code = EmailVerificationCode::normalize((string) $request->input('code'));

        if ($channel === 'email') {
            if (strlen($code) !== 6 || ! EmailVerificationCode::verifyFor($user, $code)) {
                throw ValidationException::withMessages([
                    'code' => __('The verification code is invalid or expired.'),
                ]);
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        } elseif (! $this->phoneVerification->confirmCode($user, $code)) {
            throw ValidationException::withMessages([
                'code' => __('The verification code is invalid or expired.'),
            ]);
        }

        $request->session()->forget([self::CHANNEL_SESSION_KEY, self::LAST_SENT_SESSION_KEY]);

        return $this->destination($user)->with('success', __('Your account has been verified.'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->isVerified($user)) {
            return $this->destination($user);
        }

        if ($this->resendCooldownSeconds($request) > 0) {
            return back()->withErrors([
                'code' => __('Please wait a moment before requesting another verification code.'),
            ]);
        }

        $channel = $this->currentChannel($request, $this->channelOptions($user));

        if (! $this->sendVia($request, $user, $channel)) {
            return back()->withErrors([
                'code' => __('We could not send a new verification code. Please contact support or try again shortly.'),
            ]);
        }

        return back()->with('status', __('A new verification code has been sent via :channel.', [
            'channel' => $this->channelOptions($user)[$channel]['label'],
        ]));
    }

    public function changeChannel(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->isVerified($user)) {
            return $this->destination($user);
        }

        $validated = $request->validate([
            'channel' => ['required', Rule::in(['email', 'sms', 'whatsapp'])],
        ]);

        $channel = (string) $validated['channel'];
        $options = $this->channelOptions($user);

        if (! ($options[$channel]['available'] ?? false)) {
            throw ValidationException::withMessages([
                'channel' => (string) ($options[$channel]['unavailable_message'] ?? __('user.two_factor_channel_send_failed')),
            ]);
        }

        if ($channel === $this->currentChannel($request, $options)) {
            return back()->with('status', __(':channel is already selected.', [
                'channel' => $options[$channel]['label'],
            ]));
        }

        if (! $this->sendVia($request, $user, $channel)) {
            return back()->withErrors([
                'channel' => __('user.two_factor_channel_send_failed'),
            ]);
        }

        $request->session()->put(self::CHANNEL_SESSION_KEY, $channel);

        return back()->with('status', __('A new verification code has been sent via :channel.', [
            'channel' => $options[$channel]['label'],
        ]));
    }

    private function sendVia(Request $request, User $user, string $channel): bool
    {
        if ($channel === 'email') {
            try {
                $user->sendEmailVerificationNotification();
            } catch (Throwable $exception) {
                Log::error('Account verification email could not be sent.', [
                    'user_id' => $user->getKey(),
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                return false;
            }
        } elseif (! $this->phoneVerification->sendCode($user, $channel)) {
            return false;
        }

        $request->session()->put(self::LAST_SENT_SESSION_KEY, now()->timestamp);

        return true;
    }

    private function isVerified(User $user): bool
    {
        if ($user->isAdminPanelUser()) {
            return $user->hasVerifiedEmail();
        }

        return $user->hasVerifiedEmail() || $user->phone_verified_at !== null;
    }

    private function destination(User $user): RedirectResponse
    {
        if ($user->isAdminPanelUser()) {
            return redirect()->route('dashboard');
        }

        return redirect()->intended(route('user.shop.home'));
    }

    /**
     * @return array<string, array{label: string, description: string, destination: string, available: bool, unavailable_message: string, action_url: ?string}>
     */
    private function channelOptions(User $user): array
    {
        $options = VerificationChannels::optionsFor($user, $this->sms);

        // Admin accounts are activated by email only; phone channels never
        // satisfy the admin verification gate.
        if ($user->isAdminPanelUser()) {
            foreach (['sms', 'whatsapp'] as $channel) {
                $options[$channel]['available'] = false;
                $options[$channel]['action_url'] = null;
            }
        }

        return $options;
    }

    /**
     * @param array<string, array{available: bool}> $options
     */
    private function currentChannel(Request $request, array $options): string
    {
        $channel = (string) $request->session()->get(self::CHANNEL_SESSION_KEY, 'email');

        if (! in_array($channel, ['email', 'sms', 'whatsapp'], true) || ! ($options[$channel]['available'] ?? false)) {
            return 'email';
        }

        return $channel;
    }

    private function resendCooldownSeconds(Request $request): int
    {
        $lastSentAt = (int) $request->session()->get(self::LAST_SENT_SESSION_KEY, 0);

        if ($lastSentAt < 1) {
            return 0;
        }

        return max(0, 60 - (now()->timestamp - $lastSentAt));
    }
}
