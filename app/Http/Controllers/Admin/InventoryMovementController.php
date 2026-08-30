<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Support\AdminLogger;
use App\Support\SqlSafe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryMovementController extends Controller
{
    /**
     * Apply request filters shared by index and export.
     *
     * @return array{0: Builder<InventoryMovement>, 1: string, 2: bool}
     */
    private function buildFilteredQuery(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $productId = (int) $request->query('product_id', 0);
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $hasPerformedAt = Schema::hasColumn('inventory_movements', 'performed_at');
        $dateExpression = $hasPerformedAt ? 'COALESCE(performed_at, created_at)' : 'created_at';

        $query = InventoryMovement::query()->with(['product', 'user']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                SqlSafe::whereLike($q, 'reference', $search);
                SqlSafe::orWhereLike($q, 'note', $search);
                $q->orWhereHas('product', function ($productQuery) use ($search) {
                    SqlSafe::whereLike($productQuery, 'name_en', $search);
                    SqlSafe::orWhereLike($productQuery, 'name_ar', $search);
                    SqlSafe::orWhereLike($productQuery, 'name_ku', $search);
                    SqlSafe::orWhereLike($productQuery, 'sku', $search);
                    SqlSafe::orWhereLike($productQuery, 'part_number', $search);
                    SqlSafe::orWhereLike($productQuery, 'oem_number', $search);
                    SqlSafe::orWhereLike($productQuery, 'brand', $search);
                });
                $q->orWhereHas('user', function ($userQuery) use ($search) {
                    SqlSafe::whereLike($userQuery, 'name', $search);
                    SqlSafe::orWhereLike($userQuery, 'email', $search);
                });
            });
        }

        if (in_array($type, [InventoryMovement::TYPE_IN, InventoryMovement::TYPE_OUT], true)) {
            $query->where('type', $type);
        }

        if ($productId > 0) {
            $query->where('product_id', $productId);
        }

        if ($from !== '') {
            $query->whereRaw("DATE({$dateExpression}) >= ?", [$from]);
        }

        if ($to !== '') {
            $query->whereRaw("DATE({$dateExpression}) <= ?", [$to]);
        }

        return [$query, $dateExpression, $hasPerformedAt];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $productId = (int) $request->query('product_id', 0);
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        [$query, $dateExpression, $hasPerformedAt] = $this->buildFilteredQuery($request);

        $statsQuery = clone $query;

        $movements = $query
            ->orderByRaw("{$dateExpression} DESC")
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $products = Product::orderBy('name_en')
            ->select('id', 'name_en', 'name_ar', 'name_ku', 'sku', 'part_number', 'oem_number', 'brand', 'stock_quantity')
            ->get();
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
            'search',
            'type',
            'productId',
            'from',
            'to',
            'hasPerformedAt',
            'totalMovements',
            'totalStockIn',
            'totalStockOut',
            'todayMovements',
            'netMovement'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        [$query, $dateExpression] = $this->buildFilteredQuery($request);

        $filename = 'inventory-movements-'.now()->format('Y-m-d-Hi').'.csv';

        // Guard user-controlled cells against CSV formula injection: a value
        // starting with =, +, -, @, tab, or CR would execute in spreadsheets.
        $safeCell = fn ($value) => (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value))
            ? "'".$value
            : $value;

        return response()->streamDownload(function () use ($query, $dateExpression, $safeCell) {
            $out = fopen('php://output', 'w');
            $headers = ['Date', 'Product', 'SKU', 'Type', 'Quantity', 'Stock Before', 'Stock After', 'User', 'Reference', 'Note'];
            fputcsv($out, $headers);

            $query
                ->orderByRaw("{$dateExpression} DESC")
                ->latest('id')
                ->chunk(500, function ($movements) use ($out, $safeCell): void {
                    foreach ($movements as $movement) {
                        $movementDate = $movement->performed_at ?? $movement->created_at;
                        $row = [
                            $movementDate?->format('Y-m-d H:i'),
                            $safeCell($movement->product->name ?? 'Deleted Product'),
                            $safeCell($movement->product->sku ?? ''),
                        ];
                        array_push(
                            $row,
                            $movement->type,
                            ($movement->type === InventoryMovement::TYPE_IN ? 'in +' : 'out -').$movement->quantity,
                            $movement->stock_before,
                            $movement->stock_after,
                            $safeCell($movement->user->name ?? ''),
                            $safeCell($movement->reference ?? ''),
                            $safeCell($movement->note ?? '')
                        );
                        fputcsv($out, $row);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request): RedirectResponse
    {
        $hasPerformedAt = Schema::hasColumn('inventory_movements', 'performed_at');

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'performed_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $data, $hasPerformedAt) {
            $product = Product::query()->whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            $quantity = (int) $data['quantity'];
            $stockBefore = (int) $product->stock_quantity;

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

            if ($hasPerformedAt) {
                $movementPayload['performed_at'] = $data['performed_at'] ?? now();
            }

            InventoryMovement::create($movementPayload);

            AdminLogger::log('inventory.adjusted', $product, [
                'type' => $data['type'],
                'quantity' => $quantity,
            ]);
        });

        return back()->with('success', __('Inventory movement recorded successfully.'));
    }
}
