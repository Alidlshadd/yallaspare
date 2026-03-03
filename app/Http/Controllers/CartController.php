<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = Cart::query()
            ->where('user_id', auth()->id())
            ->with('items.product.category')
            ->first();

        $items = $cart?->items ?? collect();
        $subtotal = $items->sum(function (CartItem $item): float {
            $product = $item->product;
            if (!$product) {
                return 0;
            }

            return $product->priceFor(auth()->user()) * $item->quantity;
        });

        return view('shop.cart', [
            'cart' => $cart,
            'items' => $items,
            'subtotal' => round((float) $subtotal, 2),
            'currencySymbol' => (string) Setting::getValue('currency_symbol', 'IQD'),
            'cartCount' => (int) $items->sum('quantity'),
        ]);
    }

    public function add(Product $product): RedirectResponse
    {
        if (!$product->is_active) {
            return back()->with('error', 'This product is not available right now.');
        }

        $cart = Cart::query()->firstOrCreate(['user_id' => auth()->id()]);

        DB::transaction(function () use ($cart, $product) {
            $item = CartItem::query()->firstOrNew([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
            ]);

            $item->quantity = $item->exists ? $item->quantity + 1 : 1;
            $item->save();
        });

        return back()->with('success', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $cart = Cart::query()->where('user_id', auth()->id())->first();
        if (!$cart || $item->cart_id !== $cart->id) {
            abort(403);
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $item->update(['quantity' => $data['quantity']]);

        return back()->with('success', 'Cart item updated.');
    }

    public function remove(CartItem $item): RedirectResponse
    {
        $cart = Cart::query()->where('user_id', auth()->id())->first();
        if (!$cart || $item->cart_id !== $cart->id) {
            abort(403);
        }

        $item->delete();

        return back()->with('success', 'Item removed from cart.');
    }
}
