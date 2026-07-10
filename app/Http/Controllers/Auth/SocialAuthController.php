<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\PostLoginRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'apple'];

    public function redirect(string $provider): RedirectResponse
    {
        $provider = $this->normalizeProvider($provider);
        abort_unless($this->supportsProvider($provider), 404);

        if (! $this->providerConfigured($provider)) {
            return redirect()->route('login')->withErrors([
                'email' => __(':provider sign-in is not configured yet.', ['provider' => $this->providerName($provider)]),
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider, PostLoginRedirector $redirector): RedirectResponse
    {
        $provider = $this->normalizeProvider($provider);
        abort_unless($this->supportsProvider($provider), 404);

        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'email' => __('We could not complete :provider sign-in. Please try again.', ['provider' => $this->providerName($provider)]),
            ]);
        }

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            Log::warning('Social login callback failed', [
                'provider' => $provider,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => __('We could not complete :provider sign-in. Please try again.', ['provider' => $this->providerName($provider)]),
            ]);
        }

        $user = $this->findOrCreateUser($provider, $socialiteUser);

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => __('We could not connect this :provider account. Please use email and password or contact support.', ['provider' => $this->providerName($provider)]),
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $redirector->redirect($request, $user);
    }

    private function findOrCreateUser(string $provider, SocialiteUser $socialiteUser): ?User
    {
        $providerUserId = (string) $socialiteUser->getId();
        $email = $this->normalizeEmail($socialiteUser->getEmail());
        $trustedEmail = $this->hasTrustedEmail($provider, $socialiteUser);

        if ($providerUserId === '') {
            return null;
        }

        return DB::transaction(function () use ($provider, $socialiteUser, $providerUserId, $email, $trustedEmail): ?User {
            $socialAccount = SocialAccount::query()
                ->with('user')
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->lockForUpdate()
                ->first();

            if ($socialAccount) {
                $socialAccount->forceFill($this->socialAccountAttributes($socialiteUser, $email))->save();

                return $socialAccount->user;
            }

            if ($email === null || ! $trustedEmail) {
                return null;
            }

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if ($user && $user->isAdminPanelUser()) {
                return null;
            }

            if (! $user) {
                $user = new User();
                $user->name = $this->nameForUser($socialiteUser, $email);
                $user->email = $email;
                $user->password = Str::random(48);
                $user->email_verified_at = now();
                $user->save();
            } elseif (! $user->hasVerifiedEmail()) {
                $user->email_verified_at = now();
                $user->save();
            }

            $user->socialAccounts()->create(array_merge(
                [
                    'provider' => $provider,
                    'provider_user_id' => $providerUserId,
                ],
                $this->socialAccountAttributes($socialiteUser, $email)
            ));

            return $user;
        });
    }

    private function socialAccountAttributes(SocialiteUser $socialiteUser, ?string $email): array
    {
        return [
            'email' => $email,
            'name' => $socialiteUser->getName(),
            'avatar' => $socialiteUser->getAvatar(),
        ];
    }

    private function hasTrustedEmail(string $provider, SocialiteUser $socialiteUser): bool
    {
        $raw = $socialiteUser->getRaw();

        return match ($provider) {
            'google', 'apple' => filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            default => false,
        };
    }

    private function providerConfigured(string $provider): bool
    {
        $config = (array) config("services.{$provider}", []);

        if (! filled($config['client_id'] ?? null) || ! filled($config['redirect'] ?? null)) {
            return false;
        }

        if ($provider === 'apple') {
            $hasGeneratedSecret = filled($config['private_key'] ?? null)
                && filled($config['key_id'] ?? null)
                && filled($config['team_id'] ?? null);

            return filled($config['client_secret'] ?? null) || $hasGeneratedSecret;
        }

        return filled($config['client_secret'] ?? null);
    }

    private function nameForUser(SocialiteUser $socialiteUser, string $email): string
    {
        $name = trim((string) ($socialiteUser->getName() ?: $socialiteUser->getNickname()));

        return $name !== '' ? $name : Str::headline(Str::before($email, '@'));
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = Str::lower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function providerName(string $provider): string
    {
        return match ($provider) {
            'google' => 'Google',
            'apple' => 'Apple',
            default => Str::headline($provider),
        };
    }

    private function supportsProvider(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true);
    }

    private function normalizeProvider(string $provider): string
    {
        return Str::lower(trim($provider));
    }
}
