<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\BackInStockNotificationService;

class ProductStockObserver
{
    public function updated(Product $product): void
    {
        if (! $product->wasChanged('stock_quantity')) {
            return;
        }

        $previousStock = (int) $product->getOriginal('stock_quantity');
        $currentStock = (int) $product->stock_quantity;

        if ($previousStock <= 0 && $currentStock > 0) {
            app(BackInStockNotificationService::class)->notify($product);
        }
    }
}
