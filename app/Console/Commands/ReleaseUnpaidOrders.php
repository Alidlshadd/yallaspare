<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\OrderStatusService;
use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Hand back the stock of orders whose online payment never came.
 *
 * An order paid online takes its stock when it is placed, before the customer
 * has paid, so two people cannot pay for the last part. If the customer then
 * walks away from the payment page, nothing ever gave that stock back: the
 * part sat reserved for good and showed as sold out.
 *
 * After the payment window an unpaid order is cancelled through the same path
 * as any other cancellation, which is what returns its stock, once.
 *
 * Three things keep it from cancelling an order that was in fact paid:
 *  - the provider is asked one last time first, so a payment whose
 *    confirmation never reached us is caught here and the order goes ahead;
 *  - if the provider cannot be reached the order is left for the next run —
 *    not knowing is not the same as not paid;
 *  - the decision to cancel is made on the locked order row, the same row a
 *    payment confirmation locks first (PaymentService::applyVerification), so
 *    a confirmation arriving at that instant either lands before, and the
 *    order is kept, or after, and finds a cancelled order it reports to staff
 *    instead of quietly reopening.
 *
 * Cash-on-delivery orders are never touched: they wait on a courier, not a
 * payment page.
 */
class ReleaseUnpaidOrders extends Command
{
    protected $signature = 'orders:release-unpaid {--dry-run : List the orders that would be released, and change nothing}';

    protected $description = 'Cancel orders whose online payment was not completed in time, returning their stock';

    public function handle(PaymentService $payments, OrderStatusService $statuses): int
    {
        // Never shorter than the time a provider gives the customer to pay
        // (ZainCash links last an hour), whatever the setting says.
        $minutes = max(60, (int) config('payments.unpaid_order_release_minutes', 90));
        $cutoff = now()->subMinutes($minutes);
        $dryRun = (bool) $this->option('dry-run');
        $released = 0;
        $kept = 0;

        Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->whereIn('payment_status', [Order::PAYMENT_PENDING_PAYMENT, Order::PAYMENT_FAILED])
            ->where('payment_method', '!=', PaymentService::METHOD_COD)
            ->where('created_at', '<=', $cutoff)
            ->select(['id', 'order_number'])
            ->chunkById(100, function ($orders) use ($payments, $statuses, $dryRun, &$released, &$kept): void {
                foreach ($orders as $order) {
                    if ($dryRun) {
                        $this->line("Would release {$order->order_number}");
                        $released++;

                        continue;
                    }

                    $this->release($order, $payments, $statuses) ? $released++ : $kept++;
                }
            });

        $this->info(($dryRun ? 'Would release ' : 'Released ').$released.' unpaid order(s); left '.$kept.' alone.');

        return self::SUCCESS;
    }

    private function release(Order $order, PaymentService $payments, OrderStatusService $statuses): bool
    {
        $payment = Payment::query()->where('order_id', $order->id)->latest('id')->first();

        // One last look at the provider. Outside any lock: it is a network
        // call, and its result is written under the proper locks by
        // verifyAndApply itself.
        if ($payment !== null && filled($payment->provider_payment_id) && $payment->status !== Payment::STATUS_PAID) {
            try {
                $payments->verifyAndApply($payment, 'expiry');
            } catch (\Throwable $exception) {
                Log::warning('Unpaid order kept: the provider could not be asked', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'error_type' => $exception::class,
                ]);

                return false;
            }
        }

        $stillUnpaid = fn (Order $locked): bool => Order::normalizedStatus((string) $locked->status) === Order::STATUS_PENDING
            && in_array($locked->payment_status, [Order::PAYMENT_PENDING_PAYMENT, Order::PAYMENT_FAILED], true)
            && ! Payment::query()->where('order_id', $locked->id)->where('status', Payment::STATUS_PAID)->exists();

        try {
            $cancelled = $statuses->changeStatus(
                $order,
                Order::STATUS_CANCELLED,
                null,
                'Payment was not completed in time; stock released',
                $stillUnpaid,
            );
        } catch (QueryException $exception) {
            // A database failure is not "this order may not be cancelled";
            // it is a fault, and is not to be swallowed as one.
            throw $exception;
        } catch (\RuntimeException) {
            // The status moved on while this ran: no longer ours to cancel.
            return false;
        }

        if ($cancelled === null) {
            return false;
        }

        // Close the books on the attempt. Under the order lock again, and
        // only if no payment landed in between: one that did has already
        // marked the order paid and told staff.
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->payment_status === Order::PAYMENT_PAID) {
                return;
            }

            $locked->forceFill(['payment_status' => Order::PAYMENT_FAILED])->save();

            Payment::query()
                ->where('order_id', $locked->id)
                ->where('status', Payment::STATUS_PENDING)
                ->update([
                    'status' => Payment::STATUS_CANCELLED,
                    'failed_at' => now(),
                    'failure_reason' => 'expired_unpaid',
                ]);
        });

        Log::info('Unpaid order released', ['order_id' => $order->id]);

        return true;
    }
}
