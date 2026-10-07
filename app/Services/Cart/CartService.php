<?php

namespace App\Services\Cart;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Pricing\ExchangeRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The one place that decides whose cart this is.
 *
 * A visitor gets a cart before they get an account: the cart belongs to the
 * signed-in user when there is one, and otherwise to a token held in their
 * session. Exactly one of the two is ever set on a row, which the schema
 * cannot express — so every cart in the application is created here.
 *
 * When a visitor signs in, whatever they collected as a guest is merged into
 * the account's cart rather than thrown away.
 */
class CartService
{
    public const SESSION_KEY = 'guest_cart_token';

    /**
     * The current visitor's cart, or null when they have not started one.
     */
    public function current(): ?Cart
    {
        $user = auth()->user();

        if ($user) {
            return Cart::query()->where('user_id', $user->getAuthIdentifier())->first();
        }

        $token = $this->guestToken();

        return $token === null
            ? null
            : Cart::query()->where('session_token', $token)->first();
    }

    /**
     * The current visitor's cart, started now if they have none. Calling this
     * for a guest issues them a cart token, so only call it when the visitor
     * is actually putting something in a cart.
     */
    public function currentOrCreate(): Cart
    {
        $user = auth()->user();

        if ($user) {
            return Cart::query()->firstOrCreate(['user_id' => $user->getAuthIdentifier()]);
        }

        return Cart::query()->firstOrCreate(['session_token' => $this->guestToken(create: true)]);
    }

    /**
     * Add a product, or raise the quantity of one already in the cart, never
     * past what is actually on the shelf.
     *
     * @return array{0: bool, 1: int, 2: int} whether the request was trimmed,
     *                                        the resulting quantity, and how
     *                                        many were actually added
     */
    public function addProduct(Cart $cart, Product $product, int $quantity): array
    {
        $result = DB::transaction(function () use ($cart, $product, $quantity): array {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $maxQuantity = $this->maxPurchasableQuantity($lockedProduct);

            if (! $lockedProduct->is_active || $maxQuantity < 1) {
                throw new \RuntimeException(__('This product is not available right now.'));
            }

            $item = CartItem::query()->firstOrNew([
                'cart_id' => $cart->id,
                'product_id' => $lockedProduct->id,
            ]);

            $currentQuantity = $item->exists ? (int) $item->quantity : 0;
            $requestedTotal = $currentQuantity + $quantity;
            $item->quantity = min($maxQuantity, $requestedTotal);

            // The price on the page they just added from is the one they
            // have seen, whatever this line remembered before.
            $owner = $cart->user_id ? User::query()->find($cart->user_id) : null;
            $item->seen_unit_price = round($lockedProduct->priceFor($owner), 2);
            $item->seen_usd_rate = $lockedProduct->isUsdPriced() ? ExchangeRate::perHundred() : null;
            $item->save();

            return [
                $item->quantity < $requestedTotal,
                (int) $item->quantity,
                (int) $item->quantity - $currentQuantity,
            ];
        });

        $this->forgetSummaryCache($cart);

        return $result;
    }

    public function maxPurchasableQuantity(Product $product): int
    {
        return min(99, max(0, (int) $product->stock_quantity));
    }

    /**
     * Bring a cart back in line with the shelf. Stock moves while a cart sits
     * there, so what it holds is a hope until this says otherwise.
     */
    public function syncToStock(Cart $cart): bool
    {
        $changed = false;

        $cart->loadMissing('items.product');

        foreach ($cart->items as $item) {
            $product = $item->product;
            $maxQuantity = $product ? $this->maxPurchasableQuantity($product) : 0;

            if ($maxQuantity < 1) {
                $item->delete();
                $changed = true;

                continue;
            }

            if ((int) $item->quantity > $maxQuantity) {
                $item->update(['quantity' => $maxQuantity]);
                $changed = true;
            }
        }

        if ($changed) {
            $cart->unsetRelation('items');
            $this->forgetSummaryCache($cart);
        }

        return $changed;
    }

    /**
     * Compare what each line costs now with what the customer was last shown,
     * report the lines the exchange rate has moved, and remember the new
     * figures as seen.
     *
     * Only a dollar-priced product whose rate differs from the one on the
     * line counts. A price that moved for another reason — a discount ending,
     * a dealer signing in — is not the rate's doing and is not reported here.
     *
     * Reporting is once per change: the caller is expected to tell the
     * customer, since the next call will find nothing to say. The line's own
     * timestamp is left alone, because the abandoned-cart reminders read it
     * as the customer's last activity and looking at a price is not that.
     *
     * @return array<int, array{old: float, new: float}> keyed by cart item id
     */
    public function reviewPrices(Cart $cart, ?User $user): array
    {
        $cart->loadMissing('items.product');

        $rate = ExchangeRate::perHundred();
        $changes = [];

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            $currentPrice = round((float) $product->priceFor($user), 2);
            $currentRate = $product->isUsdPriced() ? $rate : null;
            $seenPrice = $item->seen_unit_price !== null ? round((float) $item->seen_unit_price, 2) : null;
            $seenRate = $item->seen_usd_rate !== null ? (string) $item->seen_usd_rate : null;

            $rateMoved = $currentRate !== null && $seenRate !== null && ! ExchangeRate::sameRate($seenRate, $currentRate);
            $priceMoved = $seenPrice !== null && abs($seenPrice - $currentPrice) >= 0.005;

            if ($rateMoved && $priceMoved) {
                $changes[(int) $item->id] = ['old' => $seenPrice, 'new' => $currentPrice];
            }

            $rateRecorded = $currentRate === null ? $seenRate === null : ExchangeRate::sameRate($seenRate, $currentRate);

            if ($seenPrice === null || $priceMoved || ! $rateRecorded) {
                DB::table('cart_items')->where('id', $item->id)->update([
                    'seen_unit_price' => $currentPrice,
                    'seen_usd_rate' => $currentRate,
                ]);

                $item->seen_unit_price = $currentPrice;
                $item->seen_usd_rate = $currentRate;
                $item->syncOriginal();
            }
        }

        return $changes;
    }

    /**
     * Move whatever the visitor collected before signing in into their
     * account's cart, then retire the guest cart.
     *
     * Quantities add up rather than replace: someone who put two of a part in
     * before signing in and one after wanted three, and clamping to the shelf
     * is addProduct's job either way.
     *
     * @return int how many product lines came across
     */
    public function mergeGuestCartInto(User $user): int
    {
        $token = $this->guestToken();

        if ($token === null) {
            return 0;
        }

        $guestCart = Cart::query()->where('session_token', $token)->with('items')->first();
        $this->releaseGuestToken();

        if (! $guestCart) {
            return 0;
        }

        $userCart = Cart::query()->firstOrCreate(['user_id' => $user->getKey()]);
        $merged = 0;

        foreach ($guestCart->items as $item) {
            $product = Product::query()->find($item->product_id);

            if (! $product) {
                continue;
            }

            try {
                $this->addProduct($userCart, $product, (int) $item->quantity);
                $merged++;
            } catch (\RuntimeException) {
                // The product went out of stock or off sale while the cart
                // waited. Sign-in is the wrong moment to argue about it; the
                // cart screen reports what is available.
                continue;
            }
        }

        $guestCart->items()->delete();
        $guestCart->delete();

        $this->forgetSummaryCache($userCart);

        return $merged;
    }

    /**
     * The token identifying this browser's cart. Only issued when asked to
     * create one, so a visitor who never adds anything is never tagged.
     */
    public function guestToken(bool $create = false): ?string
    {
        $token = session()->get(self::SESSION_KEY);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        if (! $create) {
            return null;
        }

        $token = Str::random(48);
        session()->put(self::SESSION_KEY, $token);

        return $token;
    }

    public function releaseGuestToken(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private function forgetSummaryCache(Cart $cart): void
    {
        HeaderComposer::forgetCartCacheFor($cart->user_id ? (int) $cart->user_id : null, $cart->session_token);
    }
}
