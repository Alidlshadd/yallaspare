<?php

namespace App\Support;

use App\Models\User;
use App\Services\OtpiqSmsService;

/**
 * Builds the delivery-channel option list shared by the account verification
 * screen and the customer two-factor challenge. Availability reflects what
 * the user can actually receive right now (saved phone, OTPiq status).
 */
class VerificationChannels
{
    /**
     * @return array<string, array{label: string, description: string, destination: string, available: bool, unavailable_message: string, action_url: ?string}>
     */
    public static function optionsFor(User $user, OtpiqSmsService $sms): array
    {
        $hasPhone = filled($user->phone_normalized);
        $smsAvailable = $hasPhone && $sms->smsAvailable();
        $whatsappAvailable = $hasPhone && $sms->whatsappAvailable();

        return [
            'email' => [
                'label' => __('Email'),
                'description' => __('Receive the code in your email inbox.'),
                'destination' => self::maskedEmail((string) $user->email),
                'available' => filled($user->email),
                'unavailable_message' => __('Email verification is currently unavailable.'),
                'action_url' => null,
            ],
            'sms' => [
                'label' => __('SMS'),
                'description' => __('Receive the code as a text message.'),
                'destination' => self::maskedPhone((string) $user->phone_normalized),
                'available' => $smsAvailable,
                'unavailable_message' => $hasPhone
                    ? __('SMS verification is currently unavailable.')
                    : __('Add a phone number to use SMS verification.'),
                'action_url' => $hasPhone ? null : route('user.phone.setup'),
            ],
            'whatsapp' => [
                'label' => __('WhatsApp'),
                'description' => __('Receive the code in WhatsApp.'),
                'destination' => self::maskedPhone((string) $user->phone_normalized),
                'available' => $whatsappAvailable,
                'unavailable_message' => $hasPhone
                    ? __('WhatsApp verification is currently unavailable.')
                    : __('Add a phone number to use WhatsApp verification.'),
                'action_url' => $hasPhone ? null : route('user.phone.setup'),
            ],
        ];
    }

    public static function maskedEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email, 2);

        return substr($name, 0, 2).str_repeat('*', max(strlen($name) - 2, 1)).'@'.$domain;
    }

    public static function maskedPhone(string $phone): string
    {
        if ($phone === '') {
            return __('No phone number');
        }

        if (preg_match('/^(964)(\d{3})(\d{5})(\d{2})$/', $phone, $matches)) {
            return '+'.$matches[1].' '.$matches[2].' *** **'.$matches[4];
        }

        return '+'.substr($phone, 0, 3).' *** *** '.substr($phone, -2);
    }
}
