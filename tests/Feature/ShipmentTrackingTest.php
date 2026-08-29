<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderStatusService;
use App\Support\UserCommunication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShipmentTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_an_order_to_shipped_records_when_it_left(): void
    {
        $order = $this->makeOrder();

        app(OrderStatusService::class)->changeStatus($order, Order::STATUS_SHIPPED, $this->admin());

        $order->refresh();

        $this->assertNotNull($order->shipped_at);
        $this->assertNull($order->delivered_at);
    }

    public function test_delivery_is_stamped_and_the_shipping_date_is_left_alone(): void
    {
        $order = $this->makeOrder();
        $statuses = app(OrderStatusService::class);
        $admin = $this->admin();

        $statuses->changeStatus($order, Order::STATUS_SHIPPED, $admin);
        $shippedAt = $order->fresh()->shipped_at;

        $this->travel(2)->days();
        $statuses->changeStatus($order->fresh(), Order::STATUS_DELIVERED, $admin);

        $order->refresh();

        $this->assertEquals($shippedAt, $order->shipped_at);
        $this->assertNotNull($order->delivered_at);
        $this->assertTrue($order->delivered_at->greaterThan($order->shipped_at));
    }

    public function test_a_repeated_move_to_shipped_does_not_rewrite_the_original_date(): void
    {
        $order = $this->makeOrder();
        $statuses = app(OrderStatusService::class);
        $admin = $this->admin();

        $statuses->changeStatus($order, Order::STATUS_SHIPPED, $admin);
        $shippedAt = $order->fresh()->shipped_at;

        $this->travel(1)->day();
        $statuses->changeStatus($order->fresh(), Order::STATUS_SHIPPED, $admin);

        $this->assertEquals($shippedAt, $order->fresh()->shipped_at);
    }

    public function test_an_admin_can_record_the_carrier_and_tracking_number(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.update-shipment', $order), [
                'carrier' => 'Aramex',
                'tracking_number' => 'ARX-4471',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('Aramex', $order->carrier);
        $this->assertSame('ARX-4471', $order->tracking_number);
        $this->assertTrue($order->hasShipmentTracking());
    }

    public function test_a_tracking_number_without_a_carrier_is_refused(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.update-shipment', $order), [
                'tracking_number' => 'ARX-4471',
            ])
            ->assertSessionHasErrors('carrier');

        $this->assertNull($order->fresh()->tracking_number);
    }

    public function test_a_tracking_number_may_not_carry_markup(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.update-shipment', $order), [
                'carrier' => 'Aramex',
                'tracking_number' => '<script>alert(1)</script>',
            ])
            ->assertSessionHasErrors('tracking_number');

        $this->assertNull($order->fresh()->tracking_number);
    }

    public function test_clearing_both_fields_removes_the_tracking_details(): void
    {
        $order = $this->makeOrder(['carrier' => 'Aramex', 'tracking_number' => 'ARX-4471']);

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.update-shipment', $order), [
                'carrier' => '',
                'tracking_number' => '',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertNull($order->carrier);
        $this->assertNull($order->tracking_number);
        $this->assertFalse($order->hasShipmentTracking());
    }

    public function test_a_known_carrier_gets_a_tracking_link_and_an_unknown_one_does_not(): void
    {
        $known = $this->makeOrder(['carrier' => 'Aramex', 'tracking_number' => 'ARX 44/71']);
        $unknown = $this->makeOrder(['carrier' => 'Ali the driver', 'tracking_number' => 'ABC123']);

        $this->assertSame(
            'https://www.aramex.com/us/en/track/results?ShipmentNumber=ARX%2044%2F71',
            $known->trackingUrl()
        );
        $this->assertSame('Aramex', $known->carrierName());

        $this->assertNull($unknown->trackingUrl());
        $this->assertSame('Ali the driver', $unknown->carrierName());
    }

    public function test_the_customer_sees_the_carrier_and_number_on_their_order(): void
    {
        $customer = User::factory()->create();
        $order = $this->makeOrder([
            'user_id' => $customer->id,
            'status' => Order::STATUS_SHIPPED,
            'carrier' => 'Aramex',
            'tracking_number' => 'ARX-4471',
        ]);

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('Shipment Tracking')
            ->assertSee('Aramex')
            ->assertSee('ARX-4471')
            ->assertSee('Track on the carrier site');
    }

    public function test_the_customer_is_told_once_the_tracking_number_first_arrives(): void
    {
        Mail::fake();

        $customer = User::factory()->create(['notify_order_updates' => true, 'email_notifications' => true]);
        $order = $this->makeOrder(['user_id' => $customer->id, 'status' => Order::STATUS_SHIPPED]);

        $sentVia = UserCommunication::sendShipmentTracking($customer->fresh(), $order);

        $this->assertContains('email', $sentVia);
    }

    public function test_a_customer_cannot_touch_another_customers_shipment(): void
    {
        $order = $this->makeOrder();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->patch(route('admin.orders.update-shipment', $order), [
                'carrier' => 'Aramex',
                'tracking_number' => 'ARX-4471',
            ])
            ->assertForbidden();

        $this->assertNull($order->fresh()->tracking_number);
    }

    public function test_the_mobile_order_payload_carries_the_tracking_details(): void
    {
        $customer = User::factory()->create();
        $order = $this->makeOrder([
            'user_id' => $customer->id,
            'status' => Order::STATUS_SHIPPED,
            'carrier' => 'Aramex',
            'tracking_number' => 'ARX-4471',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/mobile/orders/'.$order->id);

        $response->assertOk();
        $this->assertSame('Aramex', $response->json('data.carrier'));
        $this->assertSame('ARX-4471', $response->json('data.tracking_number'));
        $this->assertStringContainsString('aramex.com', (string) $response->json('data.tracking_url'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    private function makeOrder(array $attributes = []): Order
    {
        $user = User::factory()->create();

        $order = new Order;
        $order->forceFill(array_merge([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.uniqid(),
            'subtotal_amount' => 50000,
            'shipping_fee' => 5000,
            'discount_amount' => 0,
            'grand_total' => 55000,
            'total_amount' => 55000,
            'status' => Order::STATUS_PROCESSING,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => 'Street 10',
            'delivery_city' => 'Erbil',
            'delivery_phone' => '07701234567',
        ], $attributes));
        $order->save();

        return $order;
    }
}
