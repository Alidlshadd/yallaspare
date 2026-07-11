<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
    private const GOOGLE_NOT_CONFIGURED_MESSAGE = 'Google sign-in is not configured yet.';

    public function redirectToGoogle(): RedirectResponse
    {
        if (! $this->googleConfigured()) {
            return $this->redirectToLoginWithAuthError(self::GOOGLE_NOT_CONFIGURED_MESSAGE);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request, PostLoginRedirector $redirector): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->redirectToLoginWithAuthError('We could not complete Google sign-in. Please try again.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
            $user = $this->findOrCreateGoogleUser($googleUser);
        } catch (Throwable $exception) {
            Log::warning('Google sign-in callback failed', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->redirectToLoginWithAuthError('We could not complete Google sign-in. Please try again.');
        }

        if (! $user) {
            return $this->redirectToLoginWithAuthError('We could not connect this Google account. Please use email and password or contact support.');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $redirector->redirect($request, $user);
    }

    private function findOrCreateGoogleUser(SocialiteUser $googleUser): ?User
    {
        $googleId = trim((string) $googleUser->getId());
        $email = $this->normalizeEmail($googleUser->getEmail());

        if ($googleId === '' || $email === null || ! $this->hasTrustedGoogleEmail($googleUser)) {
            return null;
        }

        return DB::transaction(function () use ($googleUser, $googleId, $email): ?User {
            $user = User::query()
                ->where('google_id', $googleId)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->lockForUpdate()
                    ->first();
            }

            if ($user) {
                if ($user->google_id !== null && $user->google_id !== $googleId) {
                    return null;
                }

                $attributes = [
                    'google_id' => $googleId,
                    'avatar' => $googleUser->getAvatar(),
                ];

                if (! $user->hasVerifiedEmail()) {
                    $attributes['email_verified_at'] = now();
                }

                $user->forceFill($attributes)->save();

                return $user;
            }

            $user = new User();
            $user->name = $this->nameForUser($googleUser, $email);
            $user->email = $email;
            $user->google_id = $googleId;
            $user->avatar = $googleUser->getAvatar();
            $user->email_verified_at = now();
            $user->password = Str::random(48);
            $user->save();

            return $user;
        });
    }

    private function googleConfigured(): bool
    {
        $config = (array) config('services.google', []);

        return filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['redirect'] ?? null);
    }

    private function hasTrustedGoogleEmail(SocialiteUser $googleUser): bool
    {
        $raw = $googleUser->getRaw();

        return filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function nameForUser(SocialiteUser $googleUser, string $email): string
    {
        $name = trim((string) ($googleUser->getName() ?: $googleUser->getNickname()));

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
