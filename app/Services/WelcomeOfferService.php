<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

class WelcomeOfferService
{
    public function campaign(): ?array
    {
        if ((string) Setting::getValue('welcome_offer_enabled', '0') !== '1') {
            return null;
        }

        return [
            'type' => (string) Setting::getValue('welcome_offer_type', 'percent'),
            'value' => (float) Setting::getValue('welcome_offer_value', 20),
            'minimum_subtotal' => (float) Setting::getValue('welcome_offer_minimum_subtotal', 0),
            'maximum_discount' => (float) Setting::getValue('welcome_offer_maximum_discount', 0),
            'valid_days' => (int) Setting::getValue('welcome_offer_valid_days', 30),
        ];
    }

    public function grant(User $user): void
    {
        $campaign = $this->campaign();
        if (! $campaign || $user->isAdminPanelUser()) {
            return;
        }

        // Snapshot the promise at registration, across web, social and mobile.
        $user->welcome_offer = $campaign + [
            'expires_at' => $campaign['valid_days'] > 0
                ? now()->addDays($campaign['valid_days'])->toIso8601String() : null,
        ];
    }

    public function available(?User $user): ?array
    {
        if (! $user || ! $this->campaign() || $user->isAdminPanelUser() || $user->isBanned()
            || ! $user->welcome_offer || $user->welcome_offer_used_at || $user->orders()->exists()) {
            return null;
        }

        $offer = $user->welcome_offer;
        if (! empty($offer['expires_at']) && Carbon::parse($offer['expires_at'])->lte(now())) {
            return null;
        }

        return $offer;
    }

    public function preview(?User $user, float $subtotal, bool $hasCoupon = false): array
    {
        $offer = $this->available($user);
        $valid = $offer !== null && ! $hasCoupon && $subtotal > 0
            && $subtotal >= (float) $offer['minimum_subtotal'];
        $discount = 0.0;
        if ($valid) {
            $discount = match ($offer['type']) {
                'percent' => $subtotal * min(100, max(0, (float) $offer['value'])) / 100,
                'fixed' => (float) $offer['value'],
                default => 0.0,
            };
            if ((float) $offer['maximum_discount'] > 0) {
                $discount = min($discount, (float) $offer['maximum_discount']);
            }
        }

        return [
            'valid' => $valid,
            'discount' => round(max(0, min($subtotal, $discount)), 2),
            'free_shipping' => $valid && $offer['type'] === 'free_shipping',
            'offer' => $offer,
        ];
    }

    public function label(array $offer): string
    {
        $value = rtrim(rtrim(number_format((float) $offer['value'], 2, '.', ''), '0'), '.');

        return match ($offer['type']) {
            'free_shipping' => __('welcome.free_shipping'),
            'fixed' => __('welcome.fixed', ['amount' => $value, 'currency' => Setting::getValue('currency_code', 'IQD')]),
            default => __('welcome.percent', ['value' => $value]),
        };
    }
}
