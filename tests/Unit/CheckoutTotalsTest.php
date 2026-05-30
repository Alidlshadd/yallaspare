<?php

namespace Tests\Unit;

use App\Services\CheckoutTotals;
use Tests\TestCase;

class CheckoutTotalsTest extends TestCase
{
    private CheckoutTotals $totals;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totals = new CheckoutTotals();
    }

    public function test_empty_items_returns_zero_subtotal_with_shipping_only(): void
    {
        $result = $this->totals->compute([], 5000.0, null);

        $this->assertSame(0.0, $result['subtotal']);
        $this->assertSame(5000.0, $result['shipping_fee']);
        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertSame(5000.0, $result['grand_total']);
    }

    public function test_items_only_no_coupon(): void
    {
        $result = $this->totals->compute(
            [['quantity' => 2, 'unit_price' => 12500]],
            5000.0,
            null,
        );

        $this->assertSame(25000.0, $result['subtotal']);
        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertSame(30000.0, $result['grand_total']);
    }

    public function test_coupon_discount_applied(): void
    {
        $coupon = ['valid' => true, 'discount' => 2000.0, 'free_shipping' => false];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 10000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(2000.0, $result['discount_amount']);
        $this->assertSame(13000.0, $result['grand_total']);
    }

    public function test_coupon_free_shipping_zeroes_shipping_line(): void
    {
        $coupon = ['valid' => true, 'discount' => 0.0, 'free_shipping' => true];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 10000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(5000.0, $result['discount_amount']);
        $this->assertSame(10000.0, $result['grand_total']);
    }

    public function test_coupon_discount_plus_free_shipping_combine(): void
    {
        $coupon = ['valid' => true, 'discount' => 2000.0, 'free_shipping' => true];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 10000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(7000.0, $result['discount_amount']);
        $this->assertSame(8000.0, $result['grand_total']);
    }

    public function test_grand_total_clamped_to_zero_when_discount_exceeds_total(): void
    {
        $coupon = ['valid' => true, 'discount' => 100000.0, 'free_shipping' => false];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 1000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(0.0, $result['grand_total']);
    }

    public function test_invalid_coupon_ignored(): void
    {
        $coupon = ['valid' => false, 'discount' => 9999.0, 'free_shipping' => true];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 10000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertSame(15000.0, $result['grand_total']);
    }
}
