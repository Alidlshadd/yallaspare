<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserTwoFactorCode;
use App\Services\OtpiqSmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class UserTwoFactorController extends Controller
{
    public function __construct(private readonly OtpiqSmsService $sms)
    {
    }

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $this->requiresChallenge($request)) {
            return redirect()->intended(route('user.shop.home'));
        }

        $deliveryAvailable = $this->ensureChallenge($request);
        $challenge = $request->session()->get('user_2fa.challenge');
        $codeExpiresAt = (int) ($challenge['expires_at'] ?? 0);
        $currentChannel = $this->challengeChannel($request);
        $channelOptions = $this->channelOptions($user);

        return view('auth.user-two-factor', [
            'deliveryAvailable' => $deliveryAvailable,
            'resendCooldownSeconds' => $this->resendCooldownSeconds($request),
            'currentChannel' => $currentChannel,
            'currentChannelLabel' => $channelOptions[$currentChannel]['label'],
            'maskedDestination' => $channelOptions[$currentChannel]['destination'],
            'channelOptions' => $channelOptions,
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

        $key = 'user-2fa:'.$user->id.'|'.$request->ip();
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

        $channel = $this->challengeChannel($request);

        if (! $this->issueChallenge($request, $channel)) {
            return back()->withErrors([
                'code' => __('We could not send a new verification code. Please contact support or try again shortly.'),
            ]);
        }

        return back()->with('status', __('A new verification code has been sent via :channel.', [
            'channel' => $this->channelOptions($request->user())[$channel]['label'],
        ]));
    }

    public function changeChannel(Request $request): RedirectResponse
    {
        if (! $this->requiresChallenge($request)) {
            return redirect()->intended(route('user.shop.home'));
        }

        $validated = $request->validate([
            'channel' => ['required', Rule::in(['email', 'sms', 'whatsapp'])],
        ]);

        $channel = (string) $validated['channel'];
        $options = $this->channelOptions($request->user());

        if (! ($options[$channel]['available'] ?? false)) {
            throw ValidationException::withMessages([
                'channel' => __('user.phone_channel_requires_verified_phone'),
            ]);
        }

        if ($channel === $this->challengeChannel($request)) {
            return back()->with('status', __(':channel is already selected.', [
                'channel' => $options[$channel]['label'],
            ]));
        }

        if (! $this->issueChallenge($request, $channel)) {
            return back()->withErrors([
                'channel' => __('user.two_factor_channel_send_failed'),
            ]);
        }

        return back()->with('status', __('A new verification code has been sent via :channel.', [
            'channel' => $options[$channel]['label'],
        ]));
    }

    public function issueChallenge(Request $request, string $channel = 'email'): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        $options = $this->channelOptions($user);
        if (! isset($options[$channel]) || ! $options[$channel]['available']) {
            return false;
        }

        $code = (string) random_int(100000, 999999);
        $ttl = max((int) config('security.user_two_factor.code_ttl_minutes', 10), 1);

        try {
            if ($channel === 'email') {
                $user->notify(new UserTwoFactorCode($code, $ttl));
            } else {
                $this->sms->sendVerification($user, $code, $channel);
            }
        } catch (Throwable $exception) {
            Log::error('User two-factor code delivery failed', [
                'user_id' => $user->id,
                'delivery_channel' => $channel,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        $request->session()->put('user_2fa.challenge', [
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl)->timestamp,
            'channel' => $channel,
        ]);
        $request->session()->forget('user_2fa.verified_user_id');
        $request->session()->put('user_2fa.last_sent_at', now()->timestamp);

        return true;
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
            $channel = is_array($challenge) ? (string) ($challenge['channel'] ?? 'email') : 'email';
            $options = $this->channelOptions($request->user());

            if (! ($options[$channel]['available'] ?? false)) {
                $channel = 'email';
            }

            return $this->issueChallenge($request, $channel);
        }

        return true;
    }

    private function challengeChannel(Request $request): string
    {
        $challenge = $request->session()->get('user_2fa.challenge');
        $channel = is_array($challenge) ? (string) ($challenge['channel'] ?? 'email') : 'email';

        return in_array($channel, ['email', 'sms', 'whatsapp'], true) ? $channel : 'email';
    }

    /**
     * @return array<string, array{label: string, description: string, destination: string, available: bool}>
     */
    private function channelOptions(User $user): array
    {
        $phoneAvailable = filled($user->phone_normalized) && $user->phone_verified_at !== null;

        return [
            'email' => [
                'label' => __('Email'),
                'description' => __('Receive the code in your email inbox.'),
                'destination' => $this->maskedEmail((string) $user->email),
                'available' => filled($user->email),
            ],
            'sms' => [
                'label' => __('SMS'),
                'description' => __('Receive the code as a text message.'),
                'destination' => $this->maskedPhone((string) $user->phone_normalized),
                'available' => $phoneAvailable,
            ],
            'whatsapp' => [
                'label' => __('WhatsApp'),
                'description' => __('Receive the code in WhatsApp.'),
                'destination' => $this->maskedPhone((string) $user->phone_normalized),
                'available' => $phoneAvailable,
            ],
        ];
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

        return substr($name, 0, 2).str_repeat('*', max(strlen($name) - 2, 1)).'@'.$domain;
    }

    private function maskedPhone(string $phone): string
    {
        if ($phone === '') {
            return __('No verified phone');
        }

        return str_repeat('•', max(strlen($phone) - 4, 4)).substr($phone, -4);
    }
}
