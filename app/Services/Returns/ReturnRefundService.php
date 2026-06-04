<?php

namespace App\Services\Returns;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Support\AdminLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReturnRefundService
{
    public function assertReturnCanBeRequested(Order $order): void
    {
        if (Order::normalizedStatus((string) $order->status) !== Order::STATUS_DELIVERED) {
            throw ValidationException::withMessages([
                'order' => __('Returns can be requested after the order is delivered.'),
            ]);
        }

        if ((string) $order->payment_status !== Order::PAYMENT_PAID) {
            throw ValidationException::withMessages([
                'order' => __('Returns can be requested only for paid orders.'),
            ]);
        }
    }

    public function updateStatus(
        ReturnRequest $return,
        string $status,
        ?string $adminNote,
        ?float $refundAmount,
        User $actor
    ): ReturnRequest {
        return DB::transaction(function () use ($return, $status, $adminNote, $refundAmount, $actor): ReturnRequest {
            $lockedReturn = ReturnRequest::query()
                ->whereKey($return->id)
                ->with('order')
                ->lockForUpdate()
                ->firstOrFail();

            $order = $lockedReturn->order;
            if ($order) {
                $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                $lockedReturn->setRelation('order', $order);
            }

            $amount = $refundAmount !== null
                ? round($refundAmount, 2)
                : (float) ($lockedReturn->refund_amount ?? 0);

            if ($status === ReturnRequest::STATUS_REFUNDED) {
                $this->assertRefundAllowed($lockedReturn, $amount);
            }

            $lockedReturn->update([
                'status' => $status,
                'admin_note' => trim((string) ($adminNote ?? '')) ?: null,
                'refund_amount' => $amount,
                'resolved_at' => in_array($status, [ReturnRequest::STATUS_REJECTED, ReturnRequest::STATUS_REFUNDED, ReturnRequest::STATUS_CLOSED], true)
                    ? now()
                    : null,
            ]);

            if ($status === ReturnRequest::STATUS_REFUNDED && $order) {
                $order->forceFill(['payment_status' => Order::PAYMENT_REFUNDED])->save();
            }

            AdminLogger::log('return_request.status_changed', $lockedReturn, [
                'status' => $status,
                'refund_amount' => $amount,
                'order_number' => $order?->order_number,
                'actor_role' => $actor->role,
            ]);

            return $lockedReturn->fresh(['order']);
        });
    }

    private function assertRefundAllowed(ReturnRequest $return, float $refundAmount): void
    {
        $order = $return->order;
        if (! $order) {
            throw ValidationException::withMessages([
                'refund_amount' => __('A refund requires a linked order.'),
            ]);
        }

        if (Order::normalizedStatus((string) $order->status) !== Order::STATUS_DELIVERED) {
            throw ValidationException::withMessages([
                'refund_amount' => __('Refunds require a delivered order.'),
            ]);
        }

        if ((string) $order->payment_status !== Order::PAYMENT_PAID) {
            throw ValidationException::withMessages([
                'refund_amount' => __('Refunds require a paid order.'),
            ]);
        }

        $paidAmount = $this->reconciledPaidAmount($order);
        if ($refundAmount <= 0 || $refundAmount > $paidAmount) {
            throw ValidationException::withMessages([
                'refund_amount' => __('Refund amount must be positive and within the reconciled paid amount.'),
            ]);
        }
    }

    private function reconciledPaidAmount(Order $order): float
    {
        $orderTotal = round((float) ($order->grand_total ?: $order->total_amount), 2);
        $method = strtolower((string) $order->payment_method);

        if ($method === 'cash_on_delivery' || ! Schema::hasTable('payments')) {
            return $orderTotal;
        }

        $paidByProvider = round((float) Payment::query()
            ->where('order_id', $order->id)
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount'), 2);

        if ($paidByProvider <= 0) {
            throw ValidationException::withMessages([
                'refund_amount' => __('Online refunds require a reconciled paid provider payment.'),
            ]);
        }

        return min($orderTotal, $paidByProvider);
    }
}
