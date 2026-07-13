<?php

namespace App\Services;

use App\Models\User;
use App\Support\PhoneVerificationCode;
use Illuminate\Support\Facades\Log;
use Throwable;

class PhoneVerificationService
{
    public function __construct(private readonly OtpiqSmsService $sms) {}

    /**
     * Generate a fresh OTP for the user's saved phone number and deliver it
     * through OTPiQ. Any previously issued code becomes invalid. Returns
     * false when the code could not be delivered.
     */
    public function sendCode(User $user, ?string $provider = null): bool
    {
        if (empty($user->phone_normalized)) {
            return false;
        }

        $code = PhoneVerificationCode::generateFor($user);

        try {
            $this->sms->sendVerification($user, $code, $provider);
        } catch (Throwable $exception) {
            PhoneVerificationCode::forgetFor($user);

            // Never log the OTP, the API token, or the full phone number.
            Log::warning('Phone verification SMS could not be sent.', [
                'user_id' => $user->getKey(),
                'phone' => self::maskPhone((string) $user->phone_normalized),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Check a submitted OTP and mark the phone number as verified when it matches.
     */
    public function confirmCode(User $user, string $code): bool
    {
        $code = PhoneVerificationCode::normalize($code);

        if (strlen($code) !== 6 || ! PhoneVerificationCode::verifyFor($user, $code)) {
            return false;
        }

        $user->forceFill(['phone_verified_at' => now()])->save();

        return true;
    }

    public function expiresInMinutes(): int
    {
        return PhoneVerificationCode::expiresIn();
    }

    public function smsAvailable(): bool
    {
        return $this->sms->smsAvailable();
    }

    /**
     * Human-readable masked phone for UI display, e.g. "+964 770 *** **15".
     */
    public static function displayPhone(string $phone): string
    {
        if ($phone === '') {
            return __('No phone number');
        }

        if (preg_match('/^(964)(\d{3})(\d{5})(\d{2})$/', $phone, $matches)) {
            return '+'.$matches[1].' '.$matches[2].' *** **'.$matches[4];
        }

        return '+'.substr($phone, 0, 3).' *** *** '.substr($phone, -2);
    }

    /**
     * Compact masked phone for log context, e.g. "964******15".
     */
    public static function maskPhone(string $phone): string
    {
        if (strlen($phone) < 5) {
            return '***';
        }

        return substr($phone, 0, 3).str_repeat('*', strlen($phone) - 5).substr($phone, -2);
    }
}
