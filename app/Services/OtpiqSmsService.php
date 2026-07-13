<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OtpiqSmsService
{
    /**
     * @return array<string, mixed>
     */
    public function sendVerification(User $user, string $code): array
    {
        $apiKey = trim((string) config('services.otpiq.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('OTPIQ_API_KEY is not configured.');
        }

        $phoneNumber = $this->internationalPhoneNumber((string) $user->phone_normalized);
        $baseUrl = rtrim((string) config('services.otpiq.base_url', 'https://api.otpiq.com/api'), '/');

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->post($baseUrl.'/sms', [
                    'phoneNumber' => $phoneNumber,
                    'smsType' => 'verification',
                    'provider' => (string) config('services.otpiq.provider', 'sms'),
                    'verificationCode' => $code,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to OTPiQ.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('OTPiQ rejected the verification message with HTTP '.$response->status().'.');
        }

        $payload = $response->json();

        if (! is_array($payload) || empty($payload['smsId'])) {
            throw new RuntimeException('OTPiQ returned an invalid response.');
        }

        return $payload;
    }

    public function internationalPhoneNumber(string $normalizedPhone): string
    {
        $phone = ltrim($normalizedPhone);

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = (string) config('services.otpiq.default_country_code', '964').substr($phone, 1);
        }

        if (! preg_match('/^[0-9]{10,15}$/', $phone)) {
            throw new RuntimeException('The phone number must use a valid international format.');
        }

        return $phone;
    }
}
