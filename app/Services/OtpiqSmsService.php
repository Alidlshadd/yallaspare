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
    public function sendVerification(User $user, string $code, ?string $provider = null): array
    {
        $apiKey = trim((string) config('services.otpiq.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('OTPIQ_API_KEY is not configured.');
        }

        $phoneNumber = $this->internationalPhoneNumber((string) $user->phone_normalized);
        $baseUrl = rtrim((string) config('services.otpiq.base_url', 'https://api.otpiq.com/api'), '/');
        $provider ??= (string) config('services.otpiq.provider', 'sms');

        if (! in_array($provider, ['auto', 'whatsapp-sms', 'telegram-sms', 'whatsapp-telegram-sms', 'sms', 'whatsapp', 'telegram'], true)) {
            throw new RuntimeException('The configured OTPiQ provider is not supported.');
        }

        if ($provider === 'whatsapp' && ! $this->whatsappAvailable()) {
            throw new RuntimeException('OTPIQ WhatsApp verification is not configured.');
        }

        $requestPayload = [
            'phoneNumber' => $phoneNumber,
            'smsType' => 'verification',
            'provider' => $provider,
            'verificationCode' => $code,
        ];

        if ($provider === 'whatsapp') {
            $requestPayload += [
                'whatsappAccountId' => (string) config('services.otpiq.whatsapp_account_id'),
                'whatsappPhoneId' => (string) config('services.otpiq.whatsapp_phone_id'),
                'templateName' => (string) config('services.otpiq.whatsapp_template_name'),
            ];
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->post($baseUrl.'/sms', $requestPayload);
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

    public function smsAvailable(): bool
    {
        return filled(config('services.otpiq.api_key'));
    }

    public function whatsappAvailable(): bool
    {
        return $this->smsAvailable()
            && (bool) config('services.otpiq.whatsapp_enabled', false)
            && filled(config('services.otpiq.whatsapp_account_id'))
            && filled(config('services.otpiq.whatsapp_phone_id'))
            && filled(config('services.otpiq.whatsapp_template_name'));
    }

    public function internationalPhoneNumber(string $normalizedPhone): string
    {
        $phone = ltrim($normalizedPhone);

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = (string) config('services.otpiq.default_country_code', '964').substr($phone, 1);
        } elseif (strlen($phone) === 10 && str_starts_with($phone, '7')) {
            $phone = (string) config('services.otpiq.default_country_code', '964').$phone;
        }

        if (! preg_match('/^[0-9]{10,15}$/', $phone)) {
            throw new RuntimeException('The phone number must use a valid international format.');
        }

        return $phone;
    }
}
