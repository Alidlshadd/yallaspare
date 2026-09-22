<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Email\AdminEmailAlertService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderEmailAlertObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Order $order): void
    {
        try {
            app(AdminEmailAlertService::class)->orderPlaced($order);
        } catch (Throwable $exception) {
            Log::error('Could not prepare admin order alert', ['order_id' => $order->id, 'exception' => $exception::class]);
            app(AdminEmailAlertService::class)->systemError($exception);
        }
    }
}
