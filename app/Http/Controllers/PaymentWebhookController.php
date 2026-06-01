<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentService $payments): JsonResponse
    {
        try {
            $payment = $payments->handleWebhook($provider, $request);
        } catch (\InvalidArgumentException) {
            return response()->json(['message' => 'Unsupported provider.'], 404);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Payment verification failed.'], 502);
        }

        if (! $payment) {
            return response()->json(['message' => 'Invalid callback.'], 400);
        }

        return response()->json(['message' => 'OK']);
    }
}
