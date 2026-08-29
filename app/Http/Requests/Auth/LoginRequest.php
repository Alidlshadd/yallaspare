<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->input('email'));
        $user = $this->userForLogin($login, (string) $this->input('password'));

        // Attempted by primary key, not by email: an account made at express
        // checkout has no email address, and a null there would ask the user
        // provider for "the account whose email is NULL" — matching a
        // stranger's credential-less account rather than nobody at all.
        if (
            ! $user ||
            ! Auth::attempt(['id' => $user->getKey(), 'password' => (string) $this->input('password')], $this->boolean('remember'))
        ) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Checked after the attempt, never before: revealing that an account is
        // banned to someone who has not proven the password would turn the ban
        // state into an enumeration oracle. Auth::attempt has already opened a
        // session by this point, so tear it back down.
        if ($user->isBanned()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => $user->banMessage(),
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    private function userForLogin(string $login, string $password): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return User::query()
                ->where('email', $login)
                ->first();
        }

        $phoneCandidates = User::phoneLookupCandidates($login);
        if ($phoneCandidates === []) {
            return null;
        }

        $passwordMatches = User::query()
            ->whereIn('phone_normalized', $phoneCandidates)
            ->get()
            ->filter(fn (User $user): bool => Hash::check($password, (string) $user->password));

        // If two shared-phone accounts also use the same password, phone alone
        // cannot identify the intended account. Require email login in that case.
        return $passwordMatches->count() === 1 ? $passwordMatches->first() : null;
    }
}
