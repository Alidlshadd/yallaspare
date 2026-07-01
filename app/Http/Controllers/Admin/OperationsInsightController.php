<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackInStockSubscription;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SearchAnalytic;
use App\Models\Setting;
use App\Support\AdminLogger;
use App\Support\SqlSafe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperationsInsightController extends Controller
{
    private const PAID_STATUSES = [Order::STATUS_DELIVERED, 'completed'];

    public function purchasePlanning(Request $request): View
    {
        $days = $this->allowedInt((int) $request->query('days', 30), [7, 30, 90], 30);
        $coverageDays = $this->allowedInt((int) $request->query('coverage_days', 30), [14, 30, 60, 90], 30);
        $status = $this->allowedString((string) $request->query('status', 'needs_reorder'), ['needs_reorder', 'all', 'out_of_stock', 'low_stock'], 'needs_reorder');
        $search = SqlSafe::searchTerm($request->query('search', ''));
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);
        $currency = $this->currencyMeta();

        $sales = $this->salesSubquery(now()->subDays($days));
        $waiting = $this->pendingBackInStockSubquery();

        $query = Product::query()
            ->leftJoinSub($sales, 'sales', 'sales.product_id', '=', 'products.id')
            ->leftJoinSub($waiting, 'waiting', 'waiting.product_id', '=', 'products.id')
            ->select('products.*')
            ->selectRaw('COALESCE(sales.sold_quantity, 0) as sold_quantity')
            ->selectRaw('COALESCE(waiting.waiting_count, 0) as waiting_count')
            ->selectRaw('sales.last_sold_at as last_sold_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                SqlSafe::whereLike($q, 'products.name_en', $search);
                SqlSafe::orWhereLike($q, 'products.sku', $search);
                SqlSafe::orWhereLike($q, 'products.brand', $search);
                SqlSafe::orWhereLike($q, 'products.part_number', $search);
                SqlSafe::orWhereLike($q, 'products.oem_number', $search);
            });
        }

        match ($status) {
            'out_of_stock' => $query->where('products.stock_quantity', '<=', 0),
            'low_stock' => $query->where('products.stock_quantity', '>', 0)->where('products.stock_quantity', '<=', $lowStockThreshold),
            'needs_reorder' => $query->where(function ($q) use ($lowStockThreshold, $days, $coverageDays): void {
                $q->where('products.stock_quantity', '<=', $lowStockThreshold)
                    ->orWhereRaw('(COALESCE(sales.sold_quantity, 0) > 0 AND products.stock_quantity <= ((COALESCE(sales.sold_quantity, 0) / ?) * ?))', [$days, $coverageDays])
                    ->orWhereRaw('COALESCE(waiting.waiting_count, 0) > 0');
            }),
            default => null,
        };

        $products = $query
            ->orderByRaw('CASE WHEN products.stock_quantity <= 0 THEN 0 ELSE 1 END')
            ->orderByDesc(DB::raw('COALESCE(waiting.waiting_count, 0)'))
            ->orderByDesc(DB::raw('COALESCE(sales.sold_quantity, 0)'))
            ->orderBy('products.stock_quantity')
            ->paginate(20)
            ->withQueryString();

        $products->getCollection()->transform(function (Product $product) use ($days, $coverageDays): Product {
            $sold = (int) $product->sold_quantity;
            $averageDailySales = $sold > 0 ? $sold / $days : 0.0;
            $recommendedQuantity = $averageDailySales > 0
                ? max(0, (int) ceil(($averageDailySales * $coverageDays) - (int) $product->stock_quantity))
                : ((int) $product->stock_quantity <= 0 || (int) $product->waiting_count > 0 ? max(1, (int) $product->waiting_count) : 0);
            $daysRemaining = $averageDailySales > 0 ? (int) floor(max(0, (int) $product->stock_quantity) / $averageDailySales) : null;

            $product->setAttribute('average_daily_sales', $averageDailySales);
            $product->setAttribute('recommended_quantity', $recommendedQuantity);
            $product->setAttribute('days_remaining', $daysRemaining);
            $product->setAttribute('estimated_purchase_cost', $recommendedQuantity * (float) ($product->dealer_price ?? $product->price));

            return $product;
        });

        $summary = [
            'out_of_stock' => Product::query()->where('stock_quantity', '<=', 0)->count(),
            'low_stock' => Product::query()->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', $lowStockThreshold)->count(),
            'waiting_customers' => Schema::hasTable('back_in_stock_subscriptions')
                ? BackInStockSubscription::query()->whereNull('notified_at')->count()
                : 0,
            'estimated_budget' => $products->getCollection()->sum('estimated_purchase_cost'),
        ];

        return view('admin.operations.purchase-planning', compact(
            'products',
            'days',
            'coverageDays',
            'status',
            'search',
            'lowStockThreshold',
            'summary',
            'currency'
        ));
    }

    public function stockRequests(Request $request): View
    {
        $status = $this->allowedString((string) $request->query('status', 'pending'), ['pending', 'notified', 'all'], 'pending');
        $search = SqlSafe::searchTerm($request->query('search', ''));
        $hasTable = Schema::hasTable('back_in_stock_subscriptions');

        $requests = collect();
        $products = collect();
        $summary = ['pending' => 0, 'notified' => 0, 'products' => 0];

        if ($hasTable) {
            $requestCounts = BackInStockSubscription::query()
                ->select('product_id')
                ->selectRaw('COUNT(id) as request_count')
                ->selectRaw('SUM(CASE WHEN notified_at IS NULL THEN 1 ELSE 0 END) as pending_count')
                ->selectRaw('MAX(created_at) as latest_requested_at')
                ->groupBy('product_id');

            if ($status === 'pending') {
                $requestCounts->whereNull('notified_at');
            } elseif ($status === 'notified') {
                $requestCounts->whereNotNull('notified_at');
            }

            $productQuery = Product::query()
                ->joinSub($requestCounts, 'requests', 'requests.product_id', '=', 'products.id')
                ->select('products.*')
                ->selectRaw('requests.request_count as request_count')
                ->selectRaw('requests.pending_count as pending_count')
                ->selectRaw('requests.latest_requested_at as latest_requested_at');

            if ($search !== '') {
                $productQuery->where(function ($q) use ($search): void {
                    SqlSafe::whereLike($q, 'products.name_en', $search);
                    SqlSafe::orWhereLike($q, 'products.sku', $search);
                    SqlSafe::orWhereLike($q, 'products.brand', $search);
                    SqlSafe::orWhereLike($q, 'products.part_number', $search);
                    SqlSafe::orWhereLike($q, 'products.oem_number', $search);
                });
            }

            $products = $productQuery
                ->orderByDesc('requests.pending_count')
                ->orderByDesc('latest_requested_at')
                ->paginate(15)
                ->withQueryString();

            $requests = BackInStockSubscription::query()
                ->with(['product:id,name_en,name_ar,name_ku,sku,brand,stock_quantity', 'user:id,name,email'])
                ->when($status === 'pending', fn ($q) => $q->whereNull('notified_at'))
                ->when($status === 'notified', fn ($q) => $q->whereNotNull('notified_at'))
                ->latest()
                ->limit(12)
                ->get();

            $summary = [
                'pending' => BackInStockSubscription::query()->whereNull('notified_at')->count(),
                'notified' => BackInStockSubscription::query()->whereNotNull('notified_at')->count(),
                'products' => BackInStockSubscription::query()->distinct('product_id')->count('product_id'),
            ];
        }

        return view('admin.operations.stock-requests', compact('hasTable', 'products', 'requests', 'summary', 'status', 'search'));
    }

    public function markStockRequestsNotified(Product $product): RedirectResponse
    {
        if (! Schema::hasTable('back_in_stock_subscriptions')) {
            return back()->with('error', __('Back-in-stock subscriptions are not available.'));
        }

        $count = BackInStockSubscription::query()
            ->where('product_id', $product->id)
            ->whereNull('notified_at')
            ->update(['notified_at' => now(), 'updated_at' => now()]);

        AdminLogger::log('stock_requests.marked_notified', $product, ['count' => $count]);

        return back()->with('success', __('Marked :count stock requests as notified.', ['count' => $count]));
    }

    public function searchInsights(Request $request): View
    {
        $search = SqlSafe::searchTerm($request->query('search', ''));
        $sort = $this->allowedString((string) $request->query('sort', 'search_count'), ['search_count', 'last_searched_at', 'keyword'], 'search_count');
        $direction = $this->allowedString((string) $request->query('dir', 'desc'), ['asc', 'desc'], 'desc');

        $query = SearchAnalytic::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                SqlSafe::whereLike($q, 'keyword', $search);
            });
        }

        $keywords = $query
            ->orderBy($sort, $direction)
            ->orderByDesc('search_count')
            ->paginate(25)
            ->withQueryString();

        $keywords->getCollection()->transform(function (SearchAnalytic $row): SearchAnalytic {
            $row->setAttribute('matching_products_count', $this->matchingProductsCount((string) $row->keyword));

            return $row;
        });

        $summary = [
            'keywords' => SearchAnalytic::query()->count(),
            'searches' => (int) SearchAnalytic::query()->sum('search_count'),
            'zero_result_on_page' => $keywords->getCollection()->where('matching_products_count', 0)->count(),
            'top_keyword' => optional(SearchAnalytic::query()->orderByDesc('search_count')->first())->keyword,
        ];

        return view('admin.operations.search-insights', compact('keywords', 'summary', 'search', 'sort', 'direction'));
    }

    public function deadStock(Request $request): View
    {
        $idleDays = $this->allowedInt((int) $request->query('idle_days', 90), [30, 60, 90, 180, 365], 90);
        $search = SqlSafe::searchTerm($request->query('search', ''));
        $currency = $this->currencyMeta();
        $cutoff = now()->subDays($idleDays);
        $lastSales = $this->salesSubquery();

        $query = Product::query()
            ->leftJoinSub($lastSales, 'sales', 'sales.product_id', '=', 'products.id')
            ->select('products.*')
            ->selectRaw('COALESCE(sales.sold_quantity, 0) as lifetime_sold_quantity')
            ->selectRaw('sales.last_sold_at as last_sold_at')
            ->where('products.stock_quantity', '>', 0)
            ->where(function ($q) use ($cutoff): void {
                $q->whereNull('sales.last_sold_at')
                    ->orWhere('sales.last_sold_at', '<=', $cutoff);
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                SqlSafe::whereLike($q, 'products.name_en', $search);
                SqlSafe::orWhereLike($q, 'products.sku', $search);
                SqlSafe::orWhereLike($q, 'products.brand', $search);
                SqlSafe::orWhereLike($q, 'products.part_number', $search);
                SqlSafe::orWhereLike($q, 'products.oem_number', $search);
            });
        }

        $products = $query
            ->orderByRaw('(products.stock_quantity * COALESCE(products.dealer_price, products.price)) DESC')
            ->paginate(20)
            ->withQueryString();

        $products->getCollection()->transform(function (Product $product): Product {
            $product->setAttribute('inventory_value', (int) $product->stock_quantity * (float) ($product->dealer_price ?? $product->price));

            return $product;
        });

        $summary = [
            'products' => $products->total(),
            'units' => $products->getCollection()->sum('stock_quantity'),
            'value_on_page' => $products->getCollection()->sum('inventory_value'),
            'never_sold_on_page' => $products->getCollection()->whereNull('last_sold_at')->count(),
        ];

        return view('admin.operations.dead-stock', compact('products', 'summary', 'idleDays', 'search', 'currency'));
    }

    public function deliveryZones(Request $request): View
    {
        $status = $this->allowedString((string) $request->query('status', 'all'), ['all', 'active', 'inactive'], 'all');
        $search = SqlSafe::searchTerm($request->query('search', ''));
        $currency = $this->currencyMeta();

        $query = DeliveryZone::query();

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                SqlSafe::whereLike($q, 'city', $search);
                SqlSafe::orWhereLike($q, 'district', $search);
                SqlSafe::orWhereLike($q, 'notes', $search);
            });
        }

        $zones = $query
            ->orderByDesc('is_active')
            ->orderBy('city')
            ->orderBy('district')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' => DeliveryZone::query()->count(),
            'active' => DeliveryZone::query()->where('is_active', true)->count(),
            'inactive' => DeliveryZone::query()->where('is_active', false)->count(),
            'cod' => DeliveryZone::query()->where('cash_on_delivery_enabled', true)->count(),
        ];

        return view('admin.operations.delivery-zones', compact('zones', 'summary', 'status', 'search', 'currency'));
    }

    public function storeDeliveryZone(Request $request): RedirectResponse
    {
        $data = $this->validateDeliveryZone($request);
        $zone = DeliveryZone::query()->create($data);

        AdminLogger::log('delivery_zone.created', $zone, ['city' => $zone->city, 'district' => $zone->district]);

        return back()->with('success', __('Delivery zone created.'));
    }

    public function updateDeliveryZone(Request $request, DeliveryZone $zone): RedirectResponse
    {
        $data = $this->validateDeliveryZone($request, $zone);
        $zone->update($data);

        AdminLogger::log('delivery_zone.updated', $zone, ['city' => $zone->city, 'district' => $zone->district]);

        return back()->with('success', __('Delivery zone updated.'));
    }

    public function destroyDeliveryZone(DeliveryZone $zone): RedirectResponse
    {
        AdminLogger::log('delivery_zone.deleted', $zone, ['city' => $zone->city, 'district' => $zone->district]);
        $zone->delete();

        return back()->with('success', __('Delivery zone deleted.'));
    }

    private function validateDeliveryZone(Request $request, ?DeliveryZone $zone = null): array
    {
        $data = $request->validate([
            'city' => ['required', 'string', 'max:120'],
            'district' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('delivery_zones', 'district')
                    ->where(fn ($query) => $query->where('city', trim((string) $request->input('city'))))
                    ->ignore($zone?->id),
            ],
            'shipping_fee' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'free_shipping_min' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'delivery_days_min' => ['required', 'integer', 'min:0', 'max:365'],
            'delivery_days_max' => ['required', 'integer', 'min:0', 'max:365', 'gte:delivery_days_min'],
            'cash_on_delivery_enabled' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['city'] = trim((string) $data['city']);
        $data['district'] = trim((string) ($data['district'] ?? '')) ?: null;
        $data['cash_on_delivery_enabled'] = $request->boolean('cash_on_delivery_enabled');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function salesSubquery(mixed $from = null)
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as sold_quantity')
            ->selectRaw('MAX(orders.created_at) as last_sold_at')
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->when($from, fn ($query) => $query->where('orders.created_at', '>=', $from))
            ->groupBy('order_items.product_id');
    }

    private function pendingBackInStockSubquery()
    {
        if (! Schema::hasTable('back_in_stock_subscriptions')) {
            return DB::query()->selectRaw('NULL as product_id, 0 as waiting_count')->whereRaw('1 = 0');
        }

        return BackInStockSubscription::query()
            ->select('product_id')
            ->selectRaw('COUNT(*) as waiting_count')
            ->whereNull('notified_at')
            ->groupBy('product_id');
    }

    private function matchingProductsCount(string $keyword): int
    {
        $keyword = SqlSafe::searchTerm($keyword, 80);
        if ($keyword === '') {
            return 0;
        }

        return Product::query()
            ->where(function ($q) use ($keyword): void {
                SqlSafe::whereLike($q, 'name_en', $keyword);
                SqlSafe::orWhereLike($q, 'name_ar', $keyword);
                SqlSafe::orWhereLike($q, 'name_ku', $keyword);
                SqlSafe::orWhereLike($q, 'sku', $keyword);
                SqlSafe::orWhereLike($q, 'brand', $keyword);
                SqlSafe::orWhereLike($q, 'part_number', $keyword);
                SqlSafe::orWhereLike($q, 'oem_number', $keyword);
            })
            ->count();
    }

    private function currencyMeta(): array
    {
        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');

        return [
            'symbol' => $currencySymbol,
            'label' => $currencyCode !== '' ? $currencyCode : $currencySymbol,
            'decimals' => strtoupper($currencyCode) === 'IQD' ? 0 : 2,
        ];
    }

    private function allowedInt(int $value, array $allowed, int $default): int
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function allowedString(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
