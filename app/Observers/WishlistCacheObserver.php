<?php

namespace App\Observers;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Wishlist;

class WishlistCacheObserver
{
    public function saved(Wishlist $wishlist): void
    {
        HeaderComposer::forgetWishlistCacheForUser((int) $wishlist->user_id);
    }

    public function deleted(Wishlist $wishlist): void
    {
        HeaderComposer::forgetWishlistCacheForUser((int) $wishlist->user_id);
    }
}
