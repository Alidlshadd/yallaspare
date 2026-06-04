<?php

namespace App\Services\Orders;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminLogger;
use App\Support\UserCommunication;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public function changeStatus(Order $order, string $status, ?User $actor = null, ?string $note = null): ?Order
    {
        $updatedOrder = DB::transaction(function () use ($order, $status, $actor, $note): ?Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->with([
                    'items:id,order_id,product_id,quantity',
                    'user:id,name,email,phone,notify_order_updates,email_notifications,sms_notifications,whatsapp_notifications',
                ])
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = (string) $lockedOrder->status;
            if ($previousStatus === $status) {
                return null;
            }

            if (! Order::canTransition($previousStatus, $status)) {
                throw new \RuntimeException(__('Invalid status transition.'));
            }

            // Cancelling an order is a financial and inventory event. Restore
            // stock while holding row locks so concurrent checkout cannot race
            // against the cancellation flow.
            if (
                $status === Order::STATUS_CANCELLED
                && $previousStatus !== Order::STATUS_CANCELLED
                && $previousStatus !== Order::STATUS_DELIVERED
            ) {
                foreach ($lockedOrder->items as $item) {
                    if (! $item->product_id) {
                        continue;
                    }

                    $product = Product::query()
                        ->whereKey($item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $product) {
                        continue;
                    }

                    $quantity = (int) $item->quantity;
                    $stockBefore = (int) $product->stock_quantity;
                    $stockAfter = $stockBefore + $quantity;
                    $product->update(['stock_quantity' => $stockAfter]);

                    InventoryMovement::query()->create([
                        'product_id' => $product->id,
                        'user_id' => $actor?->id,
                        'type' => InventoryMovement::TYPE_IN,
                        'quantity' => $quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference' => $lockedOrder->order_number,
                        'note' => 'Order cancelled - stock restored',
                    ]);
                }
            }

            $lockedOrder->forceFill(['status' => $status])->save();

            if (
                $status === Order::STATUS_DELIVERED
                && strtolower((string) $lockedOrder->payment_method) === 'cash_on_delivery'
                && $lockedOrder->payment_status !== Order::PAYMENT_PAID
            ) {
                $lockedOrder->forceFill(['payment_status' => Order::PAYMENT_PAID])->save();
                AdminLogger::log('order.payment_auto_marked_paid', $lockedOrder, [
                    'reason' => 'cash_on_delivery_delivered',
                ]);
            }

            $lockedOrder->statusHistory()->create([
                'from_status' => $previousStatus,
                'to_status' => $status,
                'changed_by' => $actor?->id,
                'note' => $note,
                'created_at' => now(),
            ]);

            AdminLogger::log('order.status_changed', $lockedOrder, [
                'from' => $previousStatus,
                'to' => $status,
            ]);

            $lockedOrder->setAttribute('previous_status_for_notification', $previousStatus);

            return $lockedOrder;
        });

        if ($updatedOrder && $updatedOrder->user) {
            UserCommunication::sendOrderStatusUpdated(
                $updatedOrder->user,
                $updatedOrder,
                (string) $updatedOrder->getAttribute('previous_status_for_notification'),
                (string) $updatedOrder->status
            );
        }

        return $updatedOrder;
    }
}
