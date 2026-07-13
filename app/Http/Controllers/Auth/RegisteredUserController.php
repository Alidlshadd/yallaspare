<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IraqiMobileNumber;
use App\Services\PhoneVerificationService;
use App\Support\IraqiPhoneNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
    public function store(Request $request, PhoneVerificationService $phoneVerification): RedirectResponse
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

        // The account is kept even when SMS delivery fails; the user can
        // request a new code from the phone verification page.
        if (! $phoneVerification->sendCode($user)) {
            return redirect()->route('phone.verify')->withErrors([
                'code' => __('Your account was created, but the verification code could not be sent. Please try again.'),
            ]);
        }

        $request->session()->put(PhoneVerificationPromptController::LAST_SENT_SESSION_KEY, now()->timestamp);

        return redirect()->route('phone.verify')->with('status', __('user.phone_verification_sent', [
            'minutes' => $phoneVerification->expiresInMinutes(),
        ]));
    }
}
