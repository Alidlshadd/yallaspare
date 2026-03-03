<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:50'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'delivery_city' => ['required', 'string', 'max:100'],
            'delivery_phone' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $cart = Cart::query()
            ->where('user_id', auth()->id())
            ->with('items.product')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return back()->with('error', 'Cart is empty.');
        }

        try {
            DB::transaction(function () use ($cart, $data): void {
                $productIds = $cart->items
                    ->pluck('product_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $products = Product::query()
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $totalAmount = 0;
                $lineItems = [];
                foreach ($cart->items as $item) {
                    $product = $products->get($item->product_id);
                    if (!$product) {
                        continue;
                    }

                    if ($product->stock_quantity < $item->quantity) {
                        throw new \RuntimeException("Insufficient stock for {$product->name_en}.");
                    }

                    $unitPrice = $product->priceFor(auth()->user());
                    $lineItems[] = [
                        'product' => $product,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => round($unitPrice * $item->quantity, 2),
                    ];
                    $totalAmount += $unitPrice * $item->quantity;
                }

                $order = Order::query()->create([
                    'user_id' => auth()->id(),
                    'order_number' => 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
                    'total_amount' => round($totalAmount, 2),
                    'status' => 'pending',
                    'payment_method' => $data['payment_method'] ?? 'cash_on_delivery',
                    'delivery_address' => $data['delivery_address'],
                    'delivery_city' => $data['delivery_city'],
                    'delivery_phone' => $data['delivery_phone'],
                    'notes' => $data['notes'] ?? null,
                ]);

                if (!$order->statusHistory()->exists()) {
                    $order->statusHistory()->create([
                        'from_status' => null,
                        'to_status' => $order->status,
                        'changed_by' => auth()->id(),
                        'note' => 'Order created',
                    ]);
                }

                foreach ($lineItems as $line) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'subtotal' => $line['subtotal'],
                    ]);

                    $line['product']->decrement('stock_quantity', $line['quantity']);
                }

                $cart->items()->delete();
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('home')->with('success', 'Order placed successfully.');
    }
}
