<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReturnRefundSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_refund_rejected(): void
    {
        [$admin, $return, $order] = $this->refundContext([
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.returns.update', $return), [
                'status' => ReturnRequest::STATUS_REFUNDED,
                'refund_amount' => 5000,
            ])
            ->assertRedirect();

        $this->assertSame(ReturnRequest::STATUS_REQUESTED, (string) $return->fresh()->status);
        $this->assertSame(Order::PAYMENT_PENDING, (string) $order->fresh()->payment_status);
    }

    public function test_excessive_refund_rejected(): void
    {
        [$admin, $return, $order] = $this->refundContext([
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_PAID,
            'grand_total' => 10000,
            'total_amount' => 10000,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.returns.update', $return), [
                'status' => ReturnRequest::STATUS_REFUNDED,
                'refund_amount' => 15000,
            ])
            ->assertRedirect();

        $this->assertSame(ReturnRequest::STATUS_REQUESTED, (string) $return->fresh()->status);
        $this->assertSame(Order::PAYMENT_PAID, (string) $order->fresh()->payment_status);
    }

    public function test_zero_refund_rejected(): void
    {
        [$admin, $return] = $this->refundContext([
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_PAID,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.returns.update', $return), [
                'status' => ReturnRequest::STATUS_REFUNDED,
                'refund_amount' => 0,
            ])
            ->assertRedirect();

        $this->assertSame(ReturnRequest::STATUS_REQUESTED, (string) $return->fresh()->status);
    }

    public function test_mobile_undelivered_return_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $order = $this->orderFor($user, [
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => Order::PAYMENT_PAID,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/orders/{$order->id}/return-request", [
            'reason' => 'Wrong part',
        ])->assertUnprocessable();

        $this->assertSame(0, ReturnRequest::query()->where('order_id', $order->id)->count());
    }

    public function test_bulk_refund_path_uses_same_validation_as_single_path(): void
    {
        [$admin, $validReturn, $validOrder] = $this->refundContext([
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_PAID,
        ], ['refund_amount' => 5000]);

        [, $invalidReturn, $invalidOrder] = $this->refundContext([
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_PENDING,
        ], ['refund_amount' => 5000]);

        $this->actingAs($admin)
            ->post(route('admin.returns.bulk-update'), [
                'return_ids' => [$validReturn->id, $invalidReturn->id],
                'status' => ReturnRequest::STATUS_REFUNDED,
                'admin_note' => 'Bulk refund',
            ])
            ->assertSessionHas('error');

        $this->assertSame(ReturnRequest::STATUS_REFUNDED, (string) $validReturn->fresh()->status);
        $this->assertSame(Order::PAYMENT_REFUNDED, (string) $validOrder->fresh()->payment_status);
        $this->assertSame(ReturnRequest::STATUS_REQUESTED, (string) $invalidReturn->fresh()->status);
        $this->assertSame(Order::PAYMENT_PENDING, (string) $invalidOrder->fresh()->payment_status);
    }

    private function refundContext(array $orderOverrides = [], array $returnOverrides = []): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => [User::PERMISSION_ORDERS_MANAGE, User::PERMISSION_FINANCE_MANAGE],
            'email_verified_at' => now(),
        ]);
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $order = $this->orderFor($customer, $orderOverrides);
        $return = ReturnRequest::query()->create(array_merge([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => 'refund',
            'status' => ReturnRequest::STATUS_REQUESTED,
            'reason' => 'Wrong part',
            'refund_amount' => 0,
            'requested_at' => now(),
        ], $returnOverrides));

        return [$admin, $return, $order];
    }

    private function orderFor(User $user, array $overrides = []): Order
    {
        return Order::query()->forceCreate(array_merge([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . uniqid(),
            'subtotal_amount' => 10000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 10000,
            'total_amount' => 10000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => 'Street',
            'delivery_city' => 'City',
            'delivery_phone' => '123456789',
        ], $overrides));
    }
}
