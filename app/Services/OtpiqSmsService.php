<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class OtpiqSmsService
{
    /**
     * Send a verification code through the OTPiQ send API
     * (POST {base_url}/sms, documented at docs.otpiq.com).
     *
     * @throws OtpiqDeliveryException
     */
    public function sendVerification(User $user, string $code, ?string $provider = null): OtpiqSendResult
    {
        $apiKey = trim((string) config('services.otpiq.api_key'));

        if ($apiKey === '') {
            throw new OtpiqDeliveryException(
                OtpiqDeliveryException::CATEGORY_NOT_CONFIGURED,
                'OTPIQ_API_KEY is not configured.',
            );
        }

        $phoneNumber = $this->internationalPhoneNumber((string) $user->phone_normalized);
        $provider ??= (string) config('services.otpiq.provider', 'sms');

        if (! in_array($provider, ['auto', 'whatsapp-sms', 'telegram-sms', 'whatsapp-telegram-sms', 'sms', 'whatsapp', 'telegram'], true)) {
            throw new OtpiqDeliveryException(
                OtpiqDeliveryException::CATEGORY_NOT_CONFIGURED,
                'The configured OTPiQ provider is not supported.',
            );
        }

        if ($provider === 'whatsapp' && ! $this->whatsappAvailable()) {
            throw new OtpiqDeliveryException(
                OtpiqDeliveryException::CATEGORY_NOT_CONFIGURED,
                'OTPIQ WhatsApp verification is not configured.',
            );
        }

        $requestPayload = [
            'phoneNumber' => $phoneNumber,
            'smsType' => 'verification',
            'provider' => $provider,
            'verificationCode' => $code,
        ];

        if ($provider === 'whatsapp') {
            // Required WhatsApp fields per the OTPiQ send-SMS spec. The API has
            // no template-language field: language is fixed on the approved template.
            $requestPayload += [
                'whatsappAccountId' => (string) config('services.otpiq.whatsapp.account_id'),
                'whatsappPhoneId' => (string) config('services.otpiq.whatsapp.phone_id'),
                'templateName' => (string) config('services.otpiq.whatsapp.template_name'),
            ];
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout(15)
                // One controlled retry, only when the connection itself failed.
                ->retry(2, 250, fn (Throwable $e): bool => $e instanceof ConnectionException, throw: false)
                ->post($this->baseUrl().'/sms', $requestPayload);
        } catch (ConnectionException $exception) {
            throw new OtpiqDeliveryException(
                OtpiqDeliveryException::CATEGORY_CONNECTION,
                'Could not connect to OTPiQ.',
                $exception,
            );
        }

        if (! $response->successful()) {
            throw new OtpiqDeliveryException(
                $this->categorizeErrorResponse($response),
                'OTPiQ rejected the verification message with HTTP '.$response->status().'.',
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || empty($payload['smsId'])) {
            throw new OtpiqDeliveryException(
                OtpiqDeliveryException::CATEGORY_INVALID_RESPONSE,
                'OTPiQ returned an invalid response.',
            );
        }

        return OtpiqSendResult::fromResponse($payload);
    }

    public function smsAvailable(): bool
    {
        return filled(config('services.otpiq.api_key'));
    }

    /**
     * Local configuration check: every field WhatsApp delivery needs is set.
     */
    public function whatsappAvailable(): bool
    {
        return $this->smsAvailable()
            && (bool) config('services.otpiq.whatsapp.enabled', false)
            && filled(config('services.otpiq.whatsapp.account_id'))
            && filled(config('services.otpiq.whatsapp.phone_id'))
            && filled(config('services.otpiq.whatsapp.template_name'));
    }

    /**
     * Remote readiness check against GET {base_url}/whatsapp/resources:
     * confirms the configured account, phone number, and template actually
     * exist on OTPiQ and that the template is APPROVED. Cached briefly so the
     * admin page and test sends do not hammer the provider.
     *
     * @return array{checked: bool, account_found: ?bool, phone_found: ?bool, template_found: ?bool, template_approved: ?bool, template_language: ?string, language_matches: ?bool}
     */
    public function whatsappTemplateStatus(): array
    {
        $unknown = [
            'checked' => false,
            'account_found' => null,
            'phone_found' => null,
            'template_found' => null,
            'template_approved' => null,
            'template_language' => null,
            'language_matches' => null,
        ];

        if (! $this->whatsappAvailable()) {
            return $unknown;
        }

        $accountId = (string) config('services.otpiq.whatsapp.account_id');
        $phoneId = (string) config('services.otpiq.whatsapp.phone_id');
        $templateName = (string) config('services.otpiq.whatsapp.template_name');
        $templateLanguage = trim((string) config('services.otpiq.whatsapp.template_language', 'en'));

        $cacheKey = 'otpiq.whatsapp.readiness:'.sha1(implode('|', [$accountId, $phoneId, $templateName, $templateLanguage]));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($unknown, $accountId, $phoneId, $templateName, $templateLanguage): array {
            try {
                $response = Http::withToken(trim((string) config('services.otpiq.api_key')))
                    ->acceptJson()
                    ->connectTimeout(5)
                    ->timeout(10)
                    ->get($this->baseUrl().'/whatsapp/resources');
            } catch (Throwable) {
                return $unknown;
            }

            $data = $response->json('data');

            if (! $response->successful() || ! is_array($data)) {
                return $unknown;
            }

            $result = array_merge($unknown, [
                'checked' => true,
                'account_found' => false,
                'phone_found' => false,
                'template_found' => false,
                'template_approved' => false,
                'language_matches' => false,
            ]);

            foreach ((array) ($data['businesses'] ?? []) as $business) {
                foreach ((array) ($business['whatsappAccounts'] ?? []) as $account) {
                    if (! in_array($accountId, [(string) ($account['id'] ?? ''), (string) ($account['whatsappBusinessId'] ?? '')], true)) {
                        continue;
                    }

                    $result['account_found'] = true;

                    foreach ((array) ($account['phoneNumbers'] ?? []) as $phone) {
                        if (in_array($phoneId, [(string) ($phone['id'] ?? ''), (string) ($phone['phoneNumberId'] ?? '')], true)) {
                            $result['phone_found'] = true;
                        }
                    }

                    foreach ((array) ($account['templates'] ?? []) as $template) {
                        if ((string) ($template['name'] ?? '') !== $templateName) {
                            continue;
                        }

                        $result['template_found'] = true;
                        $language = (string) ($template['language'] ?? '');
                        $approved = strtoupper((string) ($template['status'] ?? '')) === 'APPROVED';

                        if ($approved) {
                            $result['template_approved'] = true;
                            $result['template_language'] = $language;

                            if ($templateLanguage === '' || strcasecmp($language, $templateLanguage) === 0) {
                                $result['language_matches'] = true;
                            }
                        }
                    }
                }
            }

            return $result;
        });
    }

    /**
     * WhatsApp is production-ready: config is complete and, when OTPiQ could
     * be reached, the template is confirmed APPROVED. An unreachable check
     * does not block (delivery errors are handled safely at send time).
     */
    public function whatsappReady(): bool
    {
        if (! $this->whatsappAvailable()) {
            return false;
        }

        $status = $this->whatsappTemplateStatus();

        return ! $status['checked'] || $status['template_approved'] === true;
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
            throw new OtpiqDeliveryException(
                OtpiqDeliveryException::CATEGORY_VALIDATION,
                'The phone number must use a valid international format.',
            );
        }

        return $phone;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.otpiq.base_url', 'https://api.otpiq.com/api'), '/');
    }

    /**
     * Map a failed OTPiQ response to a safe log category using the documented
     * error shapes. Never copies the response body into the category.
     */
    private function categorizeErrorResponse(Response $response): string
    {
        $status = $response->status();

        if ($status === 401) {
            return OtpiqDeliveryException::CATEGORY_UNAUTHORIZED;
        }

        if ($status === 429) {
            return OtpiqDeliveryException::CATEGORY_RATE_LIMITED;
        }

        if ($status >= 500) {
            return OtpiqDeliveryException::CATEGORY_SERVER_ERROR;
        }

        $body = $response->json();
        $error = is_array($body) ? strtolower((string) ($body['error'] ?? '')) : '';

        return match (true) {
            is_array($body) && array_key_exists('requiredCredit', $body) => OtpiqDeliveryException::CATEGORY_INSUFFICIENT_CREDIT,
            str_contains($error, 'insufficient credit') => OtpiqDeliveryException::CATEGORY_INSUFFICIENT_CREDIT,
            is_array($body) && array_key_exists('spendingThreshold', $body) => OtpiqDeliveryException::CATEGORY_SPENDING_THRESHOLD,
            str_contains($error, 'trial mode') => OtpiqDeliveryException::CATEGORY_TRIAL_MODE,
            default => OtpiqDeliveryException::CATEGORY_VALIDATION,
        };
    }
}
