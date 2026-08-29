<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

/**
 * Gives a way back in to an account that has never had one.
 *
 * Express checkout leaves `password` and `email` NULL on purpose: nobody
 * chose them. This is where the owner does, once, right after their first
 * order. The usual password screen cannot serve here because it asks for the
 * current password, and there is none — which is also why this only ever
 * runs while both columns are still NULL.
 */
class AccountCredentialsController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->password === null && $user->email === null, 403);

        $data = $request->validate([
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $attributes = ['password' => $data['password']];

        if (filled($data['email'] ?? null)) {
            // Left unverified: the account is already active on its phone,
            // and an address typed here has proved nothing yet.
            $attributes['email'] = $data['email'];
        }

        $user->forceFill($attributes)->save();

        return back()->with('credentials_saved', __('Your account is ready. You can sign in with your phone number and password from now on.'));
    }
}
