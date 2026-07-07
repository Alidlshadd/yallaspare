<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Support\AdminLogger;
use App\Support\SqlSafe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    /**
     * Apply request filters shared by index and export.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Builder, 1: string, 2: bool, 3: bool}
     */
    private function buildFilteredQuery(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $productId = (int) $request->query('product_id', 0);
        $warehouseId = (int) $request->query('warehouse_id', 0);
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $hasWarehouseSupport = Schema::hasTable('warehouses') && Schema::hasColumn('inventory_movements', 'warehouse_id');
        $hasPerformedAt = Schema::hasColumn('inventory_movements', 'performed_at');
        $dateExpression = $hasPerformedAt ? 'COALESCE(performed_at, created_at)' : 'created_at';

        $query = InventoryMovement::query()
            ->with($hasWarehouseSupport ? ['product', 'user', 'warehouse'] : ['product', 'user']);

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

        if ($hasWarehouseSupport && $warehouseId > 0) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($from !== '') {
            $query->whereRaw("DATE({$dateExpression}) >= ?", [$from]);
        }

        if ($to !== '') {
            $query->whereRaw("DATE({$dateExpression}) <= ?", [$to]);
        }

        return [$query, $dateExpression, $hasWarehouseSupport, $hasPerformedAt];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $productId = (int) $request->query('product_id', 0);
        $warehouseId = (int) $request->query('warehouse_id', 0);
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        [$query, $dateExpression, $hasWarehouseSupport, $hasPerformedAt] = $this->buildFilteredQuery($request);
        $hasWarehouseStockSupport = $hasWarehouseSupport && Schema::hasTable('product_warehouse_stocks');

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

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        [$query, $dateExpression, $hasWarehouseSupport] = $this->buildFilteredQuery($request);

        $filename = 'inventory-movements-' . now()->format('Y-m-d-Hi') . '.csv';

        return response()->streamDownload(function () use ($query, $dateExpression, $hasWarehouseSupport) {
            $out = fopen('php://output', 'w');
            $headers = ['Date', 'Product', 'SKU'];
            if ($hasWarehouseSupport) {
                $headers[] = 'Warehouse';
            }
            array_push($headers, 'Type', 'Quantity', 'Stock Before', 'Stock After', 'User', 'Reference', 'Note');
            fputcsv($out, $headers);

            $query
                ->orderByRaw("{$dateExpression} DESC")
                ->latest('id')
                ->chunk(500, function ($movements) use ($out, $hasWarehouseSupport): void {
                    foreach ($movements as $movement) {
                        $movementDate = $movement->performed_at ?? $movement->created_at;
                        $row = [
                            $movementDate?->format('Y-m-d H:i'),
                            $movement->product->name ?? 'Deleted Product',
                            $movement->product->sku ?? '',
                        ];
                        if ($hasWarehouseSupport) {
                            $row[] = $movement->warehouse->name ?? '';
                        }
                        array_push(
                            $row,
                            $movement->type,
                            ($movement->type === InventoryMovement::TYPE_IN ? '+' : '-') . $movement->quantity,
                            $movement->stock_before,
                            $movement->stock_after,
                            $movement->user->name ?? '',
                            $movement->reference ?? '',
                            $movement->note ?? ''
                        );
                        fputcsv($out, $row);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
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

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
        ]);

        $hasWarehouseSupport = Schema::hasTable('warehouses') && Schema::hasColumn('inventory_movements', 'warehouse_id');
        $hasPerformedAt = Schema::hasColumn('inventory_movements', 'performed_at');

        $path = $request->file('import_file')->getRealPath();
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return back()->with('error', __('Could not open uploaded CSV file.'));
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers) || $headers === []) {
            fclose($handle);
            return back()->with('error', __('Uploaded CSV does not contain a header row.'));
        }

        $headers = array_map(
            fn ($h) => strtolower(trim((string) $h)),
            $headers
        );

        $required = ['product_sku', 'type', 'quantity'];
        $missing = array_diff($required, $headers);
        if ($missing !== []) {
            fclose($handle);
            return back()->with('error', __('CSV is missing required columns: :missing', [
                'missing' => implode(', ', $missing),
            ]));
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if ($row === [null] || $row === []) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $key) {
                $assoc[$key] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            try {
                DB::transaction(function () use ($assoc, $request, $hasWarehouseSupport, $hasPerformedAt) {
                    $sku = $assoc['product_sku'] ?? '';
                    $type = strtolower($assoc['type'] ?? '');
                    $quantity = (int) ($assoc['quantity'] ?? 0);

                    if ($sku === '') {
                        throw new \RuntimeException('product_sku is required');
                    }
                    if (! in_array($type, [InventoryMovement::TYPE_IN, InventoryMovement::TYPE_OUT], true)) {
                        throw new \RuntimeException("type must be 'in' or 'out', got '{$type}'");
                    }
                    if ($quantity <= 0) {
                        throw new \RuntimeException('quantity must be > 0');
                    }

                    $product = Product::query()
                        ->where(function ($q) use ($sku) {
                            $q->where('sku', $sku)
                                ->orWhere('part_number', $sku)
                                ->orWhere('oem_number', $sku);
                        })
                        ->lockForUpdate()
                        ->first();

                    if (! $product) {
                        throw new \RuntimeException(__('errors.inventory_product_not_found', ['sku' => $sku]));
                    }

                    $stockBefore = (int) $product->stock_quantity;
                    $stockAfter = $type === InventoryMovement::TYPE_IN
                        ? $stockBefore + $quantity
                        : $stockBefore - $quantity;

                    if ($stockAfter < 0) {
                        throw new \RuntimeException(__('errors.inventory_stock_out_exceeds', ['available' => $stockBefore]));
                    }

                    $warehouseCode = $assoc['warehouse_code'] ?? '';
                    $reference = $assoc['reference'] ?? '';
                    $note = $assoc['note'] ?? '';
                    $performedAt = $assoc['performed_at'] ?? '';

                    $warehouseId = null;
                    if ($hasWarehouseSupport && $warehouseCode !== '') {
                        $warehouse = Warehouse::query()->where('code', $warehouseCode)->first();
                        if (! $warehouse) {
                            throw new \RuntimeException(__('errors.inventory_warehouse_not_found', ['code' => $warehouseCode]));
                        }
                        $warehouseId = $warehouse->id;
                    }

                    $product->update(['stock_quantity' => $stockAfter]);

                    $payload = [
                        'product_id' => $product->id,
                        'user_id' => $request->user()->id,
                        'type' => $type,
                        'quantity' => $quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference' => $reference !== '' ? $reference : null,
                        'note' => $note !== '' ? $note : null,
                    ];

                    if ($hasWarehouseSupport) {
                        $payload['warehouse_id'] = $warehouseId;
                    }

                    if ($hasPerformedAt) {
                        $payload['performed_at'] = $performedAt !== ''
                            ? $performedAt
                            : now();
                    }

                    InventoryMovement::create($payload);

                    AdminLogger::log('inventory.bulk_adjusted', $product, [
                        'type' => $type,
                        'quantity' => $quantity,
                        'warehouse_id' => $warehouseId,
                        'source' => 'csv_import',
                    ]);
                });

                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                if (count($errors) < 20) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }
        }

        fclose($handle);

        $summary = __(':imported imported, :skipped skipped', [
            'imported' => $imported,
            'skipped' => $skipped,
        ]);

        if ($skipped > 0) {
            return back()
                ->with('error', $summary . ' — ' . implode(' | ', $errors));
        }

        return back()->with('success', __('Bulk inventory import: :summary', ['summary' => $summary]));
    }
}
