<?php

namespace App\Services;

final class CheckoutTotals
{
    /**
     * Compute order totals from raw inputs.
     *
     * @param  iterable<array{quantity:int|float, unit_price:int|float}>  $items
     * @param  float  $shippingFee  Resolved shipping fee (caller decides source).
     * @param  array{valid:bool, discount:float, free_shipping:bool}|null  $couponPreview
     *         Output of CouponService::preview(), or null when no code submitted.
     * @return array{subtotal:float, shipping_fee:float, discount_amount:float, grand_total:float}
     */
    public function compute(iterable $items, float $shippingFee, ?array $couponPreview): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
        }
        $subtotal = round($subtotal, 2);

        $couponValid = (bool) ($couponPreview['valid'] ?? false);
        $couponDiscount = $couponValid ? (float) ($couponPreview['discount'] ?? 0) : 0.0;
        $freeShipping = $couponValid && (bool) ($couponPreview['free_shipping'] ?? false);
        $freeShippingDiscount = $freeShipping ? $shippingFee : 0.0;

        $discountAmount = round($couponDiscount + $freeShippingDiscount, 2);
        $grandTotal = round(max(0.0, $subtotal + $shippingFee - $discountAmount), 2);

        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'discount_amount' => $discountAmount,
            'grand_total' => $grandTotal,
        ];
    }
}
