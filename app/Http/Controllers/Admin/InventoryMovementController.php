<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Support\AdminLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $productId = (int) $request->query('product_id', 0);

        $query = InventoryMovement::query()->with(['product', 'user']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name_en', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (in_array($type, [InventoryMovement::TYPE_IN, InventoryMovement::TYPE_OUT], true)) {
            $query->where('type', $type);
        }

        if ($productId > 0) {
            $query->where('product_id', $productId);
        }

        $movements = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $products = Product::orderBy('name_en')->select('id', 'name_en', 'sku', 'stock_quantity')->get();

        $totalMovements = InventoryMovement::count();
        $totalStockIn = (int) InventoryMovement::where('type', InventoryMovement::TYPE_IN)->sum('quantity');
        $totalStockOut = (int) InventoryMovement::where('type', InventoryMovement::TYPE_OUT)->sum('quantity');
        $todayMovements = InventoryMovement::whereDate('created_at', now()->toDateString())->count();

        return view('admin.inventory.index', compact(
            'movements',
            'products',
            'search',
            'type',
            'productId',
            'totalMovements',
            'totalStockIn',
            'totalStockOut',
            'todayMovements'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $product = Product::query()->whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            $quantity = (int) $data['quantity'];
            $stockBefore = (int) $product->stock_quantity;

            $stockAfter = $data['type'] === InventoryMovement::TYPE_IN
                ? $stockBefore + $quantity
                : $stockBefore - $quantity;

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock out movement exceeds available stock.',
                ]);
            }

            $product->update([
                'stock_quantity' => $stockAfter,
            ]);

            InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => $data['type'],
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            AdminLogger::log('inventory.adjusted', $product, [
                'type' => $data['type'],
                'quantity' => $quantity,
            ]);
        });

        return back()->with('success', 'Inventory movement recorded successfully.');
    }
}
