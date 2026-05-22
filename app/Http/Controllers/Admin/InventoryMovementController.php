<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Support\AdminLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $productId = (int) $request->query('product_id', 0);
        $warehouseId = (int) $request->query('warehouse_id', 0);
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $hasWarehouseSupport = Schema::hasTable('warehouses') && Schema::hasColumn('inventory_movements', 'warehouse_id');
        $hasWarehouseStockSupport = $hasWarehouseSupport && Schema::hasTable('product_warehouse_stocks');
        $hasPerformedAt = Schema::hasColumn('inventory_movements', 'performed_at');
        $dateExpression = $hasPerformedAt ? 'COALESCE(performed_at, created_at)' : 'created_at';

        $query = InventoryMovement::query()
            ->with($hasWarehouseSupport ? ['product', 'user', 'warehouse'] : ['product', 'user']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name_en', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_ku', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('part_number', 'like', "%{$search}%")
                            ->orWhere('oem_number', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%");
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

        if ($hasWarehouseSupport && $warehouseId > 0) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($from !== '') {
            $query->whereRaw("DATE({$dateExpression}) >= ?", [$from]);
        }

        if ($to !== '') {
            $query->whereRaw("DATE({$dateExpression}) <= ?", [$to]);
        }

        $statsQuery = clone $query;

        $movements = $query
            ->orderByRaw("{$dateExpression} DESC")
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $products = Product::orderBy('name_en')
            ->select('id', 'name_en', 'name_ar', 'name_ku', 'sku', 'part_number', 'oem_number', 'brand', 'stock_quantity')
            ->get();
        $warehouses = $hasWarehouseSupport
            ? Warehouse::query()
                ->select(['id', 'code', 'name', 'city', 'is_active'])
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
            : collect();

        $totalMovements = (clone $statsQuery)->count();
        $totalStockIn = (int) (clone $statsQuery)->where('type', InventoryMovement::TYPE_IN)->sum('quantity');
        $totalStockOut = (int) (clone $statsQuery)->where('type', InventoryMovement::TYPE_OUT)->sum('quantity');
        $todayMovements = (clone $statsQuery)
            ->whereRaw("DATE({$dateExpression}) = ?", [now()->toDateString()])
            ->count();
        $netMovement = $totalStockIn - $totalStockOut;

        return view('admin.inventory.index', compact(
            'movements',
            'products',
            'warehouses',
            'search',
            'type',
            'productId',
            'warehouseId',
            'from',
            'to',
            'hasWarehouseSupport',
            'hasWarehouseStockSupport',
            'hasPerformedAt',
            'totalMovements',
            'totalStockIn',
            'totalStockOut',
            'todayMovements',
            'netMovement'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $hasWarehouseSupport = Schema::hasTable('warehouses') && Schema::hasColumn('inventory_movements', 'warehouse_id');
        $hasPerformedAt = Schema::hasColumn('inventory_movements', 'performed_at');

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => $hasWarehouseSupport
                ? ['nullable', 'integer', Rule::exists('warehouses', 'id')]
                : ['nullable'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'performed_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $data, $hasWarehouseSupport, $hasPerformedAt) {
            $product = Product::query()->whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            $quantity = (int) $data['quantity'];
            $stockBefore = (int) $product->stock_quantity;
            $warehouseId = $hasWarehouseSupport ? (int) ($data['warehouse_id'] ?? 0) : 0;

            $stockAfter = $data['type'] === InventoryMovement::TYPE_IN
                ? $stockBefore + $quantity
                : $stockBefore - $quantity;

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => __('Stock out movement exceeds available stock.'),
                ]);
            }

            $product->update([
                'stock_quantity' => $stockAfter,
            ]);

            if ($warehouseId > 0 && Schema::hasTable('product_warehouse_stocks')) {
                $warehouseStock = DB::table('product_warehouse_stocks')
                    ->where('product_id', $product->id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                $warehouseBefore = (int) ($warehouseStock->available_quantity ?? 0);
                $warehouseAfter = $data['type'] === InventoryMovement::TYPE_IN
                    ? $warehouseBefore + $quantity
                    : $warehouseBefore - $quantity;

                if ($warehouseAfter < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => __('Warehouse stock out movement exceeds available warehouse stock.'),
                    ]);
                }

                if ($warehouseStock) {
                    DB::table('product_warehouse_stocks')
                        ->where('id', $warehouseStock->id)
                        ->update([
                            'available_quantity' => $warehouseAfter,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('product_warehouse_stocks')->insert([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouseId,
                        'available_quantity' => $warehouseAfter,
                        'reserved_quantity' => 0,
                        'reorder_level' => 0,
                        'reorder_quantity' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $movementPayload = [
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => $data['type'],
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
            ];

            if ($hasWarehouseSupport) {
                $movementPayload['warehouse_id'] = $warehouseId > 0 ? $warehouseId : null;
            }

            if ($hasPerformedAt) {
                $movementPayload['performed_at'] = $data['performed_at'] ?? now();
            }

            InventoryMovement::create($movementPayload);

            AdminLogger::log('inventory.adjusted', $product, [
                'type' => $data['type'],
                'quantity' => $quantity,
                'warehouse_id' => $warehouseId > 0 ? $warehouseId : null,
            ]);
        });

        return back()->with('success', __('Inventory movement recorded successfully.'));
    }
}
