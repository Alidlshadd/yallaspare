<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\View\Composers\HeaderComposer;
use App\Models\Product;
use App\Models\Wishlist;
use App\Services\Analytics\WishlistClickTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $items = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->with(['product.category'])
            ->latest('id')
            ->paginate(12);

        return view('user.wishlist', [
            'items' => $items,
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $entry = Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        $this->syncWishlistCount($request);

        if ($entry->wasRecentlyCreated) {
            app(WishlistClickTracker::class)->record($request, $product);
        }

        return back()->with('success', __('Added to wishlist.'));
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        $this->syncWishlistCount($request);

        return back()->with('success', __('Removed from wishlist.'));
    }

    private function syncWishlistCount(Request $request): void
    {
        $count = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->count();

        $request->session()->put('wishlist_count', $count);
        HeaderComposer::forgetWishlistCacheForUser((int) $request->user()->id);
    }
}
