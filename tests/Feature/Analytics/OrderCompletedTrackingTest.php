<?php

namespace Tests\Feature\Analytics;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderCompletedTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_order_records_order_completed_event(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $order = new Order();
        $order->forceFill([
            'user_id' => $user->id,
            'order_number' => 'TEST-' . uniqid(),
            'total_amount' => 49.99,
            'subtotal_amount' => 49.99,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 49.99,
            'status' => Order::STATUS_PENDING,
            'payment_status' => Order::PAYMENT_PENDING,
            'payment_method' => 'cash_on_delivery',
            'delivery_address' => '123 Test Street',
            'delivery_city' => 'Erbil',
            'delivery_phone' => '07500000000',
        ])->save();

        $this->assertSame(1, DB::table('analytics_events')
            ->where('event_type', 'order_completed')
            ->where('user_id', $user->id)
            ->count());

        $event = DB::table('analytics_events')
            ->where('event_type', 'order_completed')
            ->first();
        $metadata = json_decode((string) $event->metadata, true);
        $this->assertSame($order->id, $metadata['order_id']);
        $this->assertSame($order->order_number, $metadata['order_number']);
    }
}
