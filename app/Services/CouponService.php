<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CouponService
{
    public function normalizeCode(?string $code): string
    {
        $normalized = strtoupper(trim((string) $code));

        return preg_replace('/[^A-Z0-9_-]/', '', $normalized) ?? '';
    }

    /**
     * @return array{valid:bool,message:?string,coupon:?Coupon,code:string,type:string,discount:float,free_shipping:bool}
     */
    public function preview(?string $code, float $subtotal, ?User $user = null): array
    {
        $code = $this->normalizeCode($code);

        if ($code === '') {
            return $this->result(false, 'Enter a coupon code.', null, $code);
        }

        $coupon = $this->findCoupon($code);
        if (! $coupon) {
            return $this->result(false, 'Coupon code was not found.', null, $code);
        }

        $validation = $this->validateCoupon($coupon, $subtotal, $user);
        if ($validation !== null) {
            return $this->result(false, $validation, $coupon, $code);
        }

        $discount = $this->discountAmount($coupon, $subtotal);

        return $this->result(true, null, $coupon, $code, $discount, (string) $coupon->type === 'free_shipping');
    }

    public function recordUsage(Coupon $coupon, int $userId, int $orderId, float $discountAmount): void
    {
        if (! Schema::hasTable('coupon_usages')) {
            return;
        }

        DB::transaction(function () use ($coupon, $userId, $orderId, $discountAmount): void {
            // Checkout may run concurrently across web and mobile. Lock the coupon
            // row before incrementing so global and per-user limits cannot be
            // overspent by parallel requests.
            $lockedCoupon = Coupon::query()
                ->whereKey($coupon->id)
                ->lockForUpdate()
                ->firstOrFail();

            $validation = $this->validateCoupon($lockedCoupon, PHP_FLOAT_MAX, User::query()->find($userId));
            if ($validation === 'Coupon usage limit has been reached.' || $validation === 'You have already used this coupon.') {
                throw new \RuntimeException($validation);
            }

            $lockedCoupon->increment('used_count');

            CouponUsage::query()->create([
                'coupon_id' => $lockedCoupon->id,
                'user_id' => $userId,
                'order_id' => $orderId,
                'discount_amount' => $discountAmount,
                'used_at' => now(),
            ]);
        });
    }

    public function syncLegacySettingCoupon(): void
    {
        if (! Schema::hasTable('coupons')) {
            return;
        }

        $code = $this->normalizeCode((string) Setting::getValue('coupon_code', ''));
        if ($code === '') {
            return;
        }

        $coupon = Coupon::withTrashed()->updateOrCreate(
            ['code' => $code],
            [
                'name' => 'Admin Coupon',
                'type' => (string) Setting::getValue('coupon_type', 'percent'),
                'value' => (float) Setting::getValue('coupon_value', 0),
                'minimum_subtotal' => (float) Setting::getValue('coupon_min_order', 0) ?: null,
                'usage_limit' => (int) Setting::getValue('coupon_usage_limit', 0) ?: null,
                'starts_at' => $this->dateOrNull((string) Setting::getValue('coupon_starts_at', '')),
                'ends_at' => $this->dateOrNull((string) Setting::getValue('coupon_ends_at', ''), true),
                'is_active' => (string) Setting::getValue('coupon_enabled', '0') === '1',
            ]
        );

        if ($coupon->trashed()) {
            $coupon->restore();
        }
    }

    private function findCoupon(string $code): ?Coupon
    {
        if (Schema::hasTable('coupons')) {
            $coupon = Coupon::query()->where('code', $code)->first();
            if ($coupon) {
                return $coupon;
            }
        }

        $settingCode = $this->normalizeCode((string) Setting::getValue('coupon_code', ''));
        if ($settingCode !== $code) {
            return null;
        }

        $coupon = new Coupon([
            'code' => $settingCode,
            'name' => 'Admin Coupon',
            'type' => (string) Setting::getValue('coupon_type', 'percent'),
            'value' => (float) Setting::getValue('coupon_value', 0),
            'minimum_subtotal' => (float) Setting::getValue('coupon_min_order', 0) ?: null,
            'usage_limit' => (int) Setting::getValue('coupon_usage_limit', 0) ?: null,
            'used_count' => 0,
            'starts_at' => $this->dateOrNull((string) Setting::getValue('coupon_starts_at', '')),
            'ends_at' => $this->dateOrNull((string) Setting::getValue('coupon_ends_at', ''), true),
            'is_active' => (string) Setting::getValue('coupon_enabled', '0') === '1',
        ]);

        return $coupon;
    }

    private function validateCoupon(Coupon $coupon, float $subtotal, ?User $user): ?string
    {
        if (! $coupon->is_active) {
            return 'Coupon is not active.';
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return 'Coupon is not active yet.';
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return 'Coupon has expired.';
        }

        if ($coupon->minimum_subtotal !== null && $subtotal < (float) $coupon->minimum_subtotal) {
            return 'Order subtotal is below the coupon minimum.';
        }

        if ($coupon->exists && $coupon->usage_limit && (int) $coupon->used_count >= (int) $coupon->usage_limit) {
            return 'Coupon usage limit has been reached.';
        }

        if ($coupon->exists && $user && $coupon->usage_limit_per_user) {
            $usedByUser = CouponUsage::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();

            if ($usedByUser >= (int) $coupon->usage_limit_per_user) {
                return 'You have already used this coupon.';
            }
        }

        return null;
    }

    private function discountAmount(Coupon $coupon, float $subtotal): float
    {
        if ((string) $coupon->type === 'free_shipping') {
            return 0.0;
        }

        $value = max(0, (float) $coupon->value);
        if ((string) $coupon->type === 'percent') {
            return round(min($subtotal, $subtotal * (min($value, 100) / 100)), 2);
        }

        return round(min($subtotal, $value), 2);
    }

    private function dateOrNull(string $value, bool $endOfDay = false): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = Carbon::parse($value);

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function result(
        bool $valid,
        ?string $message,
        ?Coupon $coupon,
        string $code,
        float $discount = 0.0,
        bool $freeShipping = false
    ): array {
        return [
            'valid' => $valid,
            'message' => $message,
            'coupon' => $coupon,
            'code' => $code,
            'type' => (string) ($coupon?->type ?? ''),
            'discount' => $discount,
            'free_shipping' => $freeShipping,
        ];
    }
}
