<?php

namespace App\Services;

use App\Models\BackInStockSubscription;
use App\Models\Product;
use App\Support\UserCommunication;
use Illuminate\Support\Facades\Schema;

class BackInStockNotificationService
{
    /**
     * Queue restock notifications for every customer still waiting for the product.
     *
     * A subscription is marked as notified only after at least one configured
     * delivery channel accepts the message. Failed or disabled deliveries stay
     * pending so an administrator can retry them from Product Requests.
     */
    public function notify(Product $product): int
    {
        if ((int) $product->stock_quantity <= 0 || ! Schema::hasTable('back_in_stock_subscriptions')) {
            return 0;
        }

        $sent = 0;

        BackInStockSubscription::query()
            ->where('product_id', $product->id)
            ->whereNull('notified_at')
            ->with('user')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($product, &$sent): void {
                foreach ($subscriptions as $subscription) {
                    $claimed = BackInStockSubscription::query()
                        ->whereKey($subscription->id)
                        ->whereNull('notified_at')
                        ->update([
                            'notified_at' => now(),
                            'updated_at' => now(),
                        ]);

                    if ($claimed !== 1) {
                        continue;
                    }

                    $channels = $subscription->user
                        ? UserCommunication::sendBackInStock($subscription->user, $product)
                        : [];

                    if ($channels === []) {
                        BackInStockSubscription::query()
                            ->whereKey($subscription->id)
                            ->update([
                                'notified_at' => null,
                                'updated_at' => now(),
                            ]);

                        continue;
                    }

                    $sent++;
                }
            });

        return $sent;
    }
}
