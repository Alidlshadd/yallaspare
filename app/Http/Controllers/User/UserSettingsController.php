<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserSettingsController extends Controller
{
    public function edit(): View
    {
        return view('user.settings', [
            'user' => auth()->user(),
        ]);
    }

    public function appearance(): View
    {
        return view('user.settings-appearance', [
            'user' => auth()->user(),
        ]);
    }

    public function language(): View
    {
        return view('user.settings-language', [
            'user' => auth()->user(),
        ]);
    }

    public function notifications(): View
    {
        return view('user.settings-notifications', [
            'user' => auth()->user(),
        ]);
    }

    public function security(): View
    {
        return view('user.settings-security', [
            'user' => auth()->user(),
        ]);
    }

    public function communication(): View
    {
        return view('user.settings-communication', [
            'user' => auth()->user(),
        ]);
    }

    public function checkout(): View
    {
        return view('user.settings-checkout', [
            'user' => auth()->user(),
        ]);
    }

    public function accessibility(): View
    {
        return view('user.settings-accessibility', [
            'user' => auth()->user(),
        ]);
    }

    public function update(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'theme_preference' => $data['theme_preference'],
            'locale_preference' => $data['locale_preference'],
            'notify_order_updates' => $request->boolean('notify_order_updates'),
            'notify_promotions' => $request->boolean('notify_promotions'),
            'notify_stock_alerts' => $request->boolean('notify_stock_alerts'),
            'two_factor_preference' => (string) ($data['two_factor_preference'] ?? $user->two_factor_preference ?? 'off'),
            'login_alerts' => $request->boolean('login_alerts'),
            'session_timeout' => $data['session_timeout'],
            'email_notifications' => $request->boolean('email_notifications'),
            'sms_notifications' => $request->boolean('sms_notifications'),
            'whatsapp_notifications' => $request->boolean('whatsapp_notifications'),
            'marketing_consent' => $request->boolean('marketing_consent'),
            'currency_preference' => $data['currency_preference'],
            'timezone_preference' => $data['timezone_preference'],
            'date_format_preference' => $data['date_format_preference'],
            'default_contact_method' => $data['default_contact_method'],
            'default_delivery_note' => $data['default_delivery_note'] ?? null,
            'express_checkout' => $request->boolean('express_checkout'),
            'font_size_preference' => $data['font_size_preference'],
            'reduced_motion' => $request->boolean('reduced_motion'),
            'high_contrast_mode' => $request->boolean('high_contrast_mode'),
        ])->save();

        return redirect()
            ->route('user.settings.edit')
            ->with('success', __('Settings updated successfully.'));
    }

    public function updateAppearance(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'theme_preference' => $data['theme_preference'],
        ])->save();

        return redirect()
            ->route('user.settings.appearance')
            ->with('success', __('Appearance settings updated successfully.'));
    }

    public function updateLanguage(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'locale_preference' => $data['locale_preference'],
        ])->save();

        $request->session()->put('locale', $data['locale_preference']);

        return redirect()
            ->route('user.settings.language')
            ->with('success', __('Language settings updated successfully.'));
    }

    public function updateNotifications(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'notify_order_updates' => $request->boolean('notify_order_updates'),
            'notify_promotions' => $request->boolean('notify_promotions'),
            'notify_stock_alerts' => $request->boolean('notify_stock_alerts'),
        ])->save();

        return redirect()
            ->route('user.settings.notifications')
            ->with('success', __('Notification settings updated successfully.'));
    }

    public function updateSecurity(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'two_factor_preference' => (string) ($data['two_factor_preference'] ?? 'off'),
            'login_alerts' => $request->boolean('login_alerts'),
            'session_timeout' => $data['session_timeout'],
        ])->save();

        if ((string) ($data['two_factor_preference'] ?? 'off') === 'email') {
            $request->session()->put('user_2fa.verified_user_id', $user->id);
        } else {
            $request->session()->forget('user_2fa');
        }

        return redirect()
            ->route('user.settings.security')
            ->with('success', __('Security settings updated successfully.'));
    }

    public function globalSignOut(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! $user || ! Hash::check((string) $data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The current password is incorrect.'),
            ]);
        }

        $currentSessionId = $request->session()->getId();

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        $user->tokens()->delete();
        $user->forceFill(['remember_token' => null])->save();

        $request->session()->put('user_2fa.verified_user_id', $user->id);
        $request->session()->regenerateToken();

        return redirect()
            ->route('user.settings.security')
            ->with('success', __('Other devices have been signed out.'));
    }

    public function updateCommunication(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'email_notifications' => $request->boolean('email_notifications'),
            'sms_notifications' => $request->boolean('sms_notifications'),
            'whatsapp_notifications' => $request->boolean('whatsapp_notifications'),
            'marketing_consent' => $request->boolean('marketing_consent'),
        ])->save();

        return redirect()
            ->route('user.settings.communication')
            ->with('success', __('Communication settings updated successfully.'));
    }

    public function updateCheckout(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'default_contact_method' => $data['default_contact_method'],
            'default_delivery_note' => $data['default_delivery_note'] ?? null,
            'express_checkout' => $request->boolean('express_checkout'),
        ])->save();

        return redirect()
            ->route('user.settings.checkout')
            ->with('success', __('Checkout settings updated successfully.'));
    }

    public function updateAccessibility(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'font_size_preference' => $data['font_size_preference'],
            'reduced_motion' => $request->boolean('reduced_motion'),
            'high_contrast_mode' => $request->boolean('high_contrast_mode'),
        ])->save();

        return redirect()
            ->route('user.settings.accessibility')
            ->with('success', __('Accessibility settings updated successfully.'));
    }
}
