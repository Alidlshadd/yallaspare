<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $payment->load('order');
        abort_unless($payment->order && $payment->order->user_id === $request->user()?->id, 403);

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
