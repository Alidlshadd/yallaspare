<?php

namespace App\Services\Otpiq;

use App\Models\Setting;

class OtpiqInboundSettings
{
    public const ENABLED_SETTING_KEY = 'otpiq_inbound_whatsapp_enabled';

    public function enabled(): bool
    {
        $value = Setting::getValue(
            self::ENABLED_SETTING_KEY,
            config('services.otpiq.whatsapp_enabled', false)
        );

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function setEnabled(bool $enabled): void
    {
        Setting::setValue(self::ENABLED_SETTING_KEY, $enabled ? '1' : '0');
    }
}
