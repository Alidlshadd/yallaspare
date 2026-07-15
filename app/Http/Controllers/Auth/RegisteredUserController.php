<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IraqiMobileNumber;
use App\Support\IraqiPhoneNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'country_code' => ['required', 'in:+964'],
            'phone' => ['required', 'string', 'max:30', new IraqiMobileNumber, User::uniquePhoneRule()],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => IraqiPhoneNumber::toE164($request->phone),
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Verification starts on the email channel; the account is kept even
        // when delivery fails — the user can resend or switch to SMS/WhatsApp
        // from the verification page.
        $request->session()->put(AccountVerificationController::CHANNEL_SESSION_KEY, 'email');

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            Log::error('Registration verification email could not be sent.', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('verification.notice')->withErrors([
                'code' => __('Your account was created, but the verification code could not be sent. Please try again.'),
            ]);
        }

        $request->session()->put(AccountVerificationController::LAST_SENT_SESSION_KEY, now()->timestamp);

        return redirect()->route('verification.notice')->with('status', __('We sent a 6-digit code to your email.'));
    }
}
