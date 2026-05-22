<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $isFullUpdate = $this->routeIs('user.settings.update');
        $isAppearance = $this->routeIs('user.settings.appearance.update');
        $isLanguage = $this->routeIs('user.settings.language.update');
        $isNotifications = $this->routeIs('user.settings.notifications.update');
        $isSecurity = $this->routeIs('user.settings.security.update');
        $isCommunication = $this->routeIs('user.settings.communication.update');
        $isCheckout = $this->routeIs('user.settings.checkout.update');
        $isAccessibility = $this->routeIs('user.settings.accessibility.update');

        return [
            'theme_preference' => [($isFullUpdate || $isAppearance) ? 'required' : 'nullable', Rule::in(['light', 'dark', 'system'])],
            'locale_preference' => [($isFullUpdate || $isLanguage) ? 'required' : 'nullable', Rule::in(['en', 'ar', 'ku'])],
            'notify_order_updates' => [($isFullUpdate || $isNotifications) ? 'nullable' : 'sometimes', 'boolean'],
            'notify_promotions' => [($isFullUpdate || $isNotifications) ? 'nullable' : 'sometimes', 'boolean'],
            'notify_stock_alerts' => [($isFullUpdate || $isNotifications) ? 'nullable' : 'sometimes', 'boolean'],
            'two_factor_preference' => ['nullable', Rule::in(['off'])],
            'login_alerts' => [($isFullUpdate || $isSecurity) ? 'nullable' : 'sometimes', 'boolean'],
            'session_timeout' => [($isFullUpdate || $isSecurity) ? 'required' : 'nullable', Rule::in(['15', '30', '60', '120'])],
            'email_notifications' => [($isFullUpdate || $isCommunication) ? 'nullable' : 'sometimes', 'boolean'],
            'sms_notifications' => [($isFullUpdate || $isCommunication) ? 'nullable' : 'sometimes', 'boolean'],
            'whatsapp_notifications' => [($isFullUpdate || $isCommunication) ? 'nullable' : 'sometimes', 'boolean'],
            'marketing_consent' => [($isFullUpdate || $isCommunication) ? 'nullable' : 'sometimes', 'boolean'],
            'currency_preference' => [$isFullUpdate ? 'required' : 'nullable', Rule::in(['USD', 'IQD'])],
            'timezone_preference' => [$isFullUpdate ? 'required' : 'nullable', Rule::in(['Asia/Baghdad', 'UTC'])],
            'date_format_preference' => [$isFullUpdate ? 'required' : 'nullable', Rule::in(['dmy', 'mdy', 'ymd'])],
            'default_contact_method' => [($isFullUpdate || $isCheckout) ? 'required' : 'nullable', Rule::in(['phone', 'email', 'whatsapp'])],
            'default_delivery_note' => [($isFullUpdate || $isCheckout) ? 'nullable' : 'sometimes', 'string', 'max:255'],
            'express_checkout' => [($isFullUpdate || $isCheckout) ? 'nullable' : 'sometimes', 'boolean'],
            'font_size_preference' => [($isFullUpdate || $isAccessibility) ? 'required' : 'nullable', Rule::in(['default', 'large', 'xl'])],
            'reduced_motion' => [($isFullUpdate || $isAccessibility) ? 'nullable' : 'sometimes', 'boolean'],
            'high_contrast_mode' => [($isFullUpdate || $isAccessibility) ? 'nullable' : 'sometimes', 'boolean'],
        ];
    }
}
