<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PostLoginRedirector;
use App\Support\SocialProviders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    /**
     * Apple's callback arrives as a cross-site form POST, so the SameSite=Lax
     * session cookie is not sent and Socialite's session-based state check
     * cannot run. We replace it with a server-side, single-use state + nonce
     * stored (hashed) in the cache for a short window.
     */
    private const APPLE_STATE_CACHE_PREFIX = 'social:apple:state:';
    private const APPLE_STATE_TTL_MINUTES = 10;

    public function redirectToGoogle(): RedirectResponse
    {
        if (! SocialProviders::googleEnabled()) {
            return $this->redirectToLoginWithAuthError(__('Google sign-in is not configured yet.'));
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request, PostLoginRedirector $redirector): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->redirectToLoginWithAuthError(__('We could not complete Google sign-in. Please try again.'));
        }

        try {
            $googleUser = Socialite::driver('google')->user();
            $user = $this->findOrCreateSocialUser('google_id', $googleUser, $this->hasTrustedGoogleEmail($googleUser));
        } catch (Throwable $exception) {
            Log::warning('Google sign-in callback failed', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->redirectToLoginWithAuthError(__('We could not complete Google sign-in. Please try again.'));
        }

        return $this->loginAndRedirect($request, $redirector, $user, __('We could not connect this Google account. Please use email and password or contact support.'));
    }

    public function redirectToApple(): RedirectResponse
    {
        if (! SocialProviders::appleEnabled()) {
            return $this->redirectToLoginWithAuthError(__('Apple sign-in is not configured yet.'));
        }

        $state = Str::random(40);
        $nonce = Str::random(40);

        Cache::put(
            self::APPLE_STATE_CACHE_PREFIX.hash('sha256', $state),
            hash('sha256', $nonce),
            now()->addMinutes(self::APPLE_STATE_TTL_MINUTES)
        );

        // stateless() keeps Socialite from writing a session state that could
        // never be validated on the cross-site POST callback; our own state
        // and nonce are carried through Apple instead.
        return Socialite::driver('apple')
            ->stateless()
            ->with(['state' => $state, 'nonce' => $nonce])
            ->redirect();
    }

    public function handleAppleCallback(Request $request, PostLoginRedirector $redirector): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->redirectToLoginWithAuthError(__('We could not complete Apple sign-in. Please try again.'));
        }

        // Single-use state: pull() deletes the entry, so an expired, unknown,
        // or replayed state is rejected before any token exchange happens.
        $state = (string) $request->input('state');
        $expectedNonceHash = $state === ''
            ? null
            : Cache::pull(self::APPLE_STATE_CACHE_PREFIX.hash('sha256', $state));

        if (! is_string($expectedNonceHash)) {
            return $this->redirectToLoginWithAuthError(__('We could not complete Apple sign-in. Please try again.'));
        }

        try {
            // The provider verifies the id_token signature against Apple's
            // JWKS plus the iss and exp/iat claims; aud and nonce are verified
            // below because the package skips them in stateless mode.
            $appleUser = Socialite::driver('apple')->stateless()->user();

            if (! $this->hasValidAppleNonce($appleUser, $expectedNonceHash) || ! $this->hasValidAppleAudience($appleUser)) {
                Log::warning('Apple sign-in rejected: nonce or audience validation failed.');

                return $this->redirectToLoginWithAuthError(__('We could not complete Apple sign-in. Please try again.'));
            }

            $user = $this->findOrCreateSocialUser('apple_id', $appleUser, $this->hasTrustedAppleEmail($appleUser));
        } catch (Throwable $exception) {
            Log::warning('Apple sign-in callback failed', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->redirectToLoginWithAuthError(__('We could not complete Apple sign-in. Please try again.'));
        }

        return $this->loginAndRedirect($request, $redirector, $user, __('We could not connect this Apple account. Please use email and password or contact support.'));
    }

    private function hasValidAppleNonce(SocialiteUser $appleUser, string $expectedNonceHash): bool
    {
        $nonce = (string) Arr::get($appleUser->getRaw(), 'nonce', '');

        return $nonce !== '' && hash_equals($expectedNonceHash, hash('sha256', $nonce));
    }

    private function hasValidAppleAudience(SocialiteUser $appleUser): bool
    {
        $audience = Arr::get($appleUser->getRaw(), 'aud');
        $audience = is_array($audience) ? $audience : [$audience];
        $clientId = (string) config('services.apple.client_id');

        return $clientId !== '' && in_array($clientId, $audience, true);
    }

    private function loginAndRedirect(Request $request, PostLoginRedirector $redirector, ?User $user, string $failureMessage): RedirectResponse
    {
        if (! $user) {
            return $this->redirectToLoginWithAuthError($failureMessage);
        }

        if ($user->isBanned()) {
            return $this->redirectToLoginWithAuthError(__('Your account has been suspended. Please contact support.'));
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $redirector->redirect($request, $user);
    }

    private function findOrCreateSocialUser(string $providerColumn, SocialiteUser $socialUser, bool $emailTrusted): ?User
    {
        $providerId = trim((string) $socialUser->getId());

        if ($providerId === '') {
            return null;
        }

        $email = $this->normalizeEmail($socialUser->getEmail());

        return DB::transaction(function () use ($socialUser, $providerColumn, $providerId, $email, $emailTrusted): ?User {
            // A user already linked to this provider id may sign in even when
            // the provider omits the email on later logins (Apple does this).
            // The stored email is never overwritten by the provider.
            $user = User::query()
                ->where($providerColumn, $providerId)
                ->lockForUpdate()
                ->first();

            if ($user) {
                $attributes = [];

                $avatar = $this->avatarUrl($socialUser);
                if ($avatar !== null) {
                    $attributes['avatar'] = $avatar;
                }

                // Only mark the address verified when the provider vouches for
                // the exact email this account currently uses.
                if (! $user->hasVerifiedEmail()
                    && $emailTrusted
                    && $email !== null
                    && Str::lower((string) $user->email) === $email) {
                    $attributes['email_verified_at'] = now();
                }

                if ($attributes !== []) {
                    $user->forceFill($attributes)->save();
                }

                return $user;
            }

            // Linking to an existing account or creating a new one requires a
            // present, provider-verified email.
            if ($email === null || ! $emailTrusted) {
                return null;
            }

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if ($user) {
                if ($user->{$providerColumn} !== null && $user->{$providerColumn} !== $providerId) {
                    return null;
                }

                $attributes = [$providerColumn => $providerId];

                $avatar = $this->avatarUrl($socialUser);
                if ($avatar !== null) {
                    $attributes['avatar'] = $avatar;
                }

                if (! $user->hasVerifiedEmail()) {
                    $attributes['email_verified_at'] = now();
                }

                $user->forceFill($attributes)->save();

                return $user;
            }

            // New accounts always get the default customer role via the
            // User::creating hook; nothing from the provider can raise it.
            $user = new User();
            $user->name = $this->nameForUser($socialUser, $email);
            $user->email = $email;
            $user->{$providerColumn} = $providerId;
            $user->avatar = $this->avatarUrl($socialUser);
            $user->email_verified_at = now();
            $user->password = Str::random(48);
            $user->save();

            return $user;
        });
    }

    private function hasTrustedGoogleEmail(SocialiteUser $googleUser): bool
    {
        $raw = $googleUser->getRaw();

        return filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function hasTrustedAppleEmail(SocialiteUser $appleUser): bool
    {
        $raw = $appleUser->getRaw();

        // Apple's id_token carries email_verified as bool or "true"/"false".
        // When the claim is absent the email still comes from a signature-
        // verified id_token, so treat it as trusted.
        if (! array_key_exists('email_verified', $raw)) {
            return true;
        }

        return filter_var($raw['email_verified'], FILTER_VALIDATE_BOOLEAN);
    }

    private function avatarUrl(SocialiteUser $socialUser): ?string
    {
        $avatar = trim((string) $socialUser->getAvatar());

        if ($avatar === '' || mb_strlen($avatar) > 2048) {
            return null;
        }

        return Str::startsWith($avatar, 'https://') && filter_var($avatar, FILTER_VALIDATE_URL) ? $avatar : null;
    }

    private function nameForUser(SocialiteUser $socialUser, string $email): string
    {
        $name = trim((string) ($socialUser->getName() ?: $socialUser->getNickname()));

        return $name !== '' ? $name : Str::headline(Str::before($email, '@'));
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = Str::lower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function redirectToLoginWithAuthError(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('auth_error', $message);
    }
}
