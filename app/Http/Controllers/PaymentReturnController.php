<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, Payment $payment, PaymentService $payments): RedirectResponse|View
    {
        $payment->load('order');
        abort_unless($payment->order && $payment->order->user_id === $request->user()?->id, 403);

        if ($payment->provider === 'wayl') {
            $verificationFailed = false;

            try {
                $verified = $payments->verifyAndApply($payment, 'return');
            } catch (\Throwable $exception) {
                $verificationFailed = true;
                $verified = $payment->fresh(['order']);

                Log::warning('WAYL payment status verification failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'error_type' => $exception::class,
                ]);
            }

            return view('shop.payment-result', [
                'payment' => $verified,
                'order' => $verified->order,
                'verificationFailed' => $verificationFailed,
            ]);
        }

        $verified = $payments->verifyAndApply($payment, 'return');
        $order = $verified->order;

        if ($verified->isPaid() && $order) {
            return redirect()
                ->route('checkout.success', $order)
                ->with('success', __('Payment confirmed. Your order is now processing.'));
        }

        return redirect()
            ->route('account.orders.show', $order)
            ->with('error', __('Payment is not confirmed yet. We will update the order after the gateway confirms it.'));
    }
}
