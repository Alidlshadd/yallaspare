<?php

namespace App\Observers;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Cart;
use App\Models\CartItem;

class CartItemCacheObserver
{
    public function saved(CartItem $cartItem): void
    {
        $this->forgetCartCache($cartItem);
    }

    public function deleted(CartItem $cartItem): void
    {
        $this->forgetCartCache($cartItem);
    }

    private function forgetCartCache(CartItem $cartItem): void
    {
        $userId = Cart::query()
            ->whereKey($cartItem->cart_id)
            ->value('user_id');

        HeaderComposer::forgetCartCacheForUser($userId !== null ? (int) $userId : null);
    }
}
