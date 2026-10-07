<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualInvoice;
use App\Models\ManualInvoicePayment;
use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevenueController extends Controller
{
    private const PAID_STATUSES = ['delivered', 'completed'];

    public function index(Request $request): View
    {
        [$start, $end, $days, $allowedDays, $customRange, $now] = $this->resolveRange($request);

        $paidStatuses = self::PAID_STATUSES;
        $pendingStatuses = ['pending', 'processing'];
        $cancelledStatuses = ['cancelled', 'canceled'];
        $refundedStatuses = ['refunded'];

        $rangeDays = max(1, (int) $start->diffInDays($end) + 1);
        $previousStart = $start->copy()->subDays($rangeDays);
        $previousEnd = $start->copy()->subSecond();

        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;
        $localizedProductColumn = match (true) {
            str_starts_with(app()->getLocale(), 'ar') => 'products.name_ar',
            str_starts_with(app()->getLocale(), 'ku') => 'products.name_ku',
            default => 'products.name_en',
        };
        $localizedCategoryColumn = match (true) {
            str_starts_with(app()->getLocale(), 'ar') => 'categories.name_ar',
            str_starts_with(app()->getLocale(), 'ku') => 'categories.name_ku',
            default => 'categories.name_en',
        };

        // Revenue is everything the shop sold: delivered site orders and
        // invoices written by hand, counted on the day they are invoiced.
        // The status cards and the customer and dealer rankings further down
        // stay site orders only: a manual invoice has no order status and
        // its customer is not a site account.
        $siteRevenue = (float) Order::query()
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        $totalRevenue = (float) Order::query()
            ->whereIn('status', $paidStatuses)
            ->sum('total_amount')
            + ManualInvoice::invoicedBetween();

        $periodRevenue = $siteRevenue + ManualInvoice::invoicedBetween($start, $end);

        $previousRevenue = (float) Order::query()
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total_amount')
            + ManualInvoice::invoicedBetween($previousStart, $previousEnd);

        $growthPercent = $this->percentageChange($periodRevenue, $previousRevenue);

        // Sales, like revenue, are both channels: delivered site orders and
        // finalized manual invoices. The average is therefore revenue over
        // sales, like for like.
        $periodPaidOrders = (int) Order::query()
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$start, $end])
            ->count()
            + ManualInvoice::countBetween($start, $end);

        $averageOrderValue = $periodPaidOrders > 0
            ? ($periodRevenue / $periodPaidOrders)
            : 0.0;

        $todayRevenue = (float) Order::query()
            ->whereIn('status', $paidStatuses)
            ->whereDate('created_at', $now->toDateString())
            ->sum('total_amount')
            + ManualInvoice::invoicedBetween($now, $now);

        $statusCards = [
            'paid' => [
                'label' => __('Paid'),
                'amount' => (float) Order::query()
                    ->whereIn('status', $paidStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total_amount'),
                'orders' => (int) Order::query()
                    ->whereIn('status', $paidStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
            'pending' => [
                'label' => __('Pending'),
                'amount' => (float) Order::query()
                    ->whereIn('status', $pendingStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total_amount'),
                'orders' => (int) Order::query()
                    ->whereIn('status', $pendingStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
            'cancelled' => [
                'label' => __('Cancelled'),
                'amount' => (float) Order::query()
                    ->whereIn('status', $cancelledStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total_amount'),
                'orders' => (int) Order::query()
                    ->whereIn('status', $cancelledStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
            'refunded' => [
                'label' => __('Refunded'),
                'amount' => (float) Order::query()
                    ->whereIn('status', $refundedStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total_amount'),
                'orders' => (int) Order::query()
                    ->whereIn('status', $refundedStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
        ];

        $dailyRows = Order::query()
            ->selectRaw('DATE(created_at) as order_day, COUNT(*) as orders_count, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('order_day')
            ->orderBy('order_day')
            ->get()
            ->keyBy('order_day');

        $manualByDay = ManualInvoice::invoicedByDay($start, $end);
        $manualCountByDay = ManualInvoice::countByDay($start, $end);

        $dailyRevenue = collect();
        for ($i = $rangeDays - 1; $i >= 0; $i--) {
            $day = $end->copy()->subDays($i);
            $key = $day->toDateString();
            $dailyRevenue->push([
                'label' => $day->format('M d'),
                'date' => $key,
                'amount' => (float) ($dailyRows[$key]->total_revenue ?? 0) + (float) ($manualByDay[$key] ?? 0),
                'orders' => (int) ($dailyRows[$key]->orders_count ?? 0) + (int) ($manualCountByDay[$key] ?? 0),
            ]);
        }

        $manualProductSales = ManualInvoice::productSalesBetween($start, $end);

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'products.id',
                DB::raw("COALESCE(NULLIF({$localizedProductColumn}, ''), products.name_en) as name"),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as units_sold'),
                DB::raw('COALESCE(SUM(order_items.subtotal), 0) as revenue_total')
            )
            ->groupBy('products.id', 'products.name_en', 'products.name_ar', 'products.name_ku')
            ->orderByDesc('revenue_total')
            ->get();

        $topProducts = $this->withManualProductSales($topProducts, $manualProductSales, $localizedProductColumn)
            ->sortByDesc('revenue_total')
            ->take(10)
            ->values();

        $topCategories = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                DB::raw('COALESCE(categories.id, 0) as category_id'),
                DB::raw("COALESCE(NULLIF({$localizedCategoryColumn}, ''), categories.name_en, 'Uncategorized') as category_name"),
                DB::raw('COALESCE(SUM(order_items.subtotal), 0) as revenue_total'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as units_sold')
            )
            ->groupBy('categories.id', 'categories.name_en', 'categories.name_ar', 'categories.name_ku')
            ->orderByDesc('revenue_total')
            ->get();

        $topCategories = $this->withManualCategorySales($topCategories, $manualProductSales, $localizedCategoryColumn)
            ->sortByDesc('revenue_total')
            ->take(8)
            ->values();

        $topCustomers = Order::query()
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'users.id',
                'users.name',
                'users.role',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('COALESCE(SUM(orders.total_amount), 0) as revenue_total')
            )
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('revenue_total')
            ->limit(10)
            ->get();

        $topDealers = Order::query()
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.status', $paidStatuses)
            ->where('users.role', 'dealer')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('COALESCE(SUM(orders.total_amount), 0) as revenue_total')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue_total')
            ->limit(10)
            ->get();

        $totalOrders = (int) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $cancelledOrders = (int) Order::query()
            ->whereIn('status', $cancelledStatuses)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $periodOrderValue = (float) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');
        $conversionSummary = [
            'total_orders' => $totalOrders,
            'paid_orders' => $periodPaidOrders,
            'avg_order_value' => $totalOrders > 0 ? ($periodOrderValue / $totalOrders) : 0.0,
            'cancel_rate' => $totalOrders > 0 ? (($cancelledOrders / $totalOrders) * 100) : 0.0,
            'paid_rate' => $totalOrders > 0 ? (($periodPaidOrders / $totalOrders) * 100) : 0.0,
        ];

        $recentPaidOrders = Order::query()
            ->with('user:id,name')
            ->whereIn('status', $paidStatuses)
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'status', 'total_amount', 'created_at']);

        return view('admin.revenue.index', [
            'days' => $days,
            'allowedDays' => $allowedDays,
            'start' => $start,
            'end' => $end,
            'now' => $now,
            'rangeDays' => $rangeDays,
            'customRange' => $customRange,
            'from' => $customRange ? $start->toDateString() : '',
            'to' => $customRange ? $end->toDateString() : '',
            'totalRevenue' => $totalRevenue,
            'periodRevenue' => $periodRevenue,
            'previousRevenue' => $previousRevenue,
            'growthPercent' => $growthPercent,
            'periodPaidOrders' => $periodPaidOrders,
            'averageOrderValue' => $averageOrderValue,
            'todayRevenue' => $todayRevenue,
            'statusCards' => $statusCards,
            'dailyRevenue' => $dailyRevenue,
            'topProducts' => $topProducts,
            'topCategories' => $topCategories,
            'topCustomers' => $topCustomers,
            'topDealers' => $topDealers,
            'conversionSummary' => $conversionSummary,
            'recentPaidOrders' => $recentPaidOrders,
            'manualSales' => $this->manualSales($start, $end),
            'profit' => $this->profit($start, $end),
            'currencyLabel' => $currencyLabel,
            'currencyDecimals' => $currencyDecimals,
        ]);
    }

    /**
     * Add what manual invoices sold to the site's per-product figures.
     *
     * A product sold only over the counter has no row from the site query at
     * all, so it is looked up and given one; the caller then ranks the lot.
     *
     * @param  Collection<int, \stdClass>  $rows
     * @param  array<int, array{units: float, revenue: float}>  $manual
     * @return Collection<int, \stdClass>
     */
    private function withManualProductSales(Collection $rows, array $manual, string $nameColumn): Collection
    {
        $rows = $rows->keyBy('id');

        $missing = array_diff(array_keys($manual), $rows->keys()->all());

        if ($missing !== []) {
            DB::table('products')
                ->whereIn('id', $missing)
                ->select('products.id', DB::raw("COALESCE(NULLIF({$nameColumn}, ''), products.name_en) as name"))
                ->get()
                ->each(function (\stdClass $product) use ($rows): void {
                    $product->units_sold = 0;
                    $product->revenue_total = 0;
                    $rows->put($product->id, $product);
                });
        }

        foreach ($manual as $productId => $sales) {
            if ($row = $rows->get($productId)) {
                $row->units_sold = (float) $row->units_sold + $sales['units'];
                $row->revenue_total = (float) $row->revenue_total + $sales['revenue'];
                // Kept beside the total, so the list can say how much of a
                // best seller went out over the counter.
                $row->manual_units = $sales['units'];
            }
        }

        return $rows->values();
    }

    /**
     * The same for categories: each manually sold product's figures go to
     * the category it is in today.
     *
     * @param  Collection<int, \stdClass>  $rows
     * @param  array<int, array{units: float, revenue: float}>  $manual
     * @return Collection<int, \stdClass>
     */
    private function withManualCategorySales(Collection $rows, array $manual, string $nameColumn): Collection
    {
        if ($manual === []) {
            return $rows;
        }

        $rows = $rows->keyBy(fn (\stdClass $row): int => (int) $row->category_id);

        DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('products.id', array_keys($manual))
            ->select(
                'products.id as product_id',
                DB::raw('COALESCE(categories.id, 0) as category_id'),
                DB::raw("COALESCE(NULLIF({$nameColumn}, ''), categories.name_en, 'Uncategorized') as category_name"),
            )
            ->get()
            ->each(function (\stdClass $product) use ($rows, $manual): void {
                $categoryId = (int) $product->category_id;

                if (! $rows->has($categoryId)) {
                    $rows->put($categoryId, (object) [
                        'category_id' => $categoryId,
                        'category_name' => $product->category_name,
                        'revenue_total' => 0,
                        'units_sold' => 0,
                    ]);
                }

                $row = $rows->get($categoryId);
                $row->units_sold = (float) $row->units_sold + $manual[(int) $product->product_id]['units'];
                $row->revenue_total = (float) $row->revenue_total + $manual[(int) $product->product_id]['revenue'];
            });

        return $rows->values();
    }

    /**
     * Net profit for the range, from the cost written on each line at the
     * moment it was sold.
     *
     * Only lines that carry a cost can say anything about profit, so only
     * they are counted — on both sides: their sales, minus their cost. The
     * number of lines left out is reported with it, because a profit figure
     * that silently treats a missing cost as zero would be flattering and
     * wrong. Discounts given on the orders come off; delivery is neither
     * income nor cost here.
     *
     * Site orders and manual invoices are worked out separately, like the
     * rest of this page, and shown side by side.
     *
     * @return array{orders: array{sales: float, cost: float, discount: float, profit: float, costed_lines: int, uncosted_lines: int}, manual: array{sales: float, cost: float, discount: float, profit: float, costed_lines: int, uncosted_lines: int}}
     */
    private function profit(Carbon $start, Carbon $end): array
    {
        $orderLines = fn () => DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->whereBetween('orders.created_at', [$start, $end]);

        $invoiceLines = fn () => DB::table('manual_invoice_items')
            ->join('manual_invoices', 'manual_invoices.id', '=', 'manual_invoice_items.manual_invoice_id')
            ->where('manual_invoices.status', ManualInvoice::STATUS_FINALIZED)
            ->whereDate('manual_invoices.invoice_date', '>=', $start->toDateString())
            ->whereDate('manual_invoices.invoice_date', '<=', $end->toDateString());

        $summarize = function (\Closure $lines, string $table, string $totalColumn, float $discount): array {
            $costed = $lines()->whereNotNull($table.'.unit_cost');
            $sales = (float) (clone $costed)->sum($table.'.'.$totalColumn);
            $cost = (float) (clone $costed)->sum(DB::raw($table.'.unit_cost * '.$table.'.quantity'));
            $costedLines = (int) (clone $costed)->count();

            return [
                'sales' => $sales,
                'cost' => $cost,
                // A discount belongs to the whole sale; with no costed line
                // there is no profit to take it from.
                'discount' => $costedLines > 0 ? $discount : 0.0,
                'profit' => $costedLines > 0 ? round($sales - $cost - $discount, 2) : 0.0,
                'costed_lines' => $costedLines,
                'uncosted_lines' => (int) $lines()->whereNull($table.'.unit_cost')->count(),
            ];
        };

        $orderDiscount = (float) Order::query()
            ->whereIn('status', self::PAID_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('order_items')
                ->whereColumn('order_items.order_id', 'orders.id')
                ->whereNotNull('order_items.unit_cost'))
            ->sum('discount_amount');

        $invoiceDiscount = (float) ManualInvoice::query()
            ->where('status', ManualInvoice::STATUS_FINALIZED)
            ->whereDate('invoice_date', '>=', $start->toDateString())
            ->whereDate('invoice_date', '<=', $end->toDateString())
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('manual_invoice_items')
                ->whereColumn('manual_invoice_items.manual_invoice_id', 'manual_invoices.id')
                ->whereNotNull('manual_invoice_items.unit_cost'))
            ->sum('discount_amount');

        return [
            'orders' => $summarize($orderLines, 'order_items', 'subtotal', $orderDiscount),
            'manual' => $summarize($invoiceLines, 'manual_invoice_items', 'line_total', $invoiceDiscount),
        ];
    }

    /**
     * Sales invoiced by hand, broken out. What was invoiced is part of the
     * revenue totals above; this block says how much of it that is, and adds
     * what only manual invoices have: money collected and money still owed.
     *
     * @return array{count: int, invoiced: float, collected: float, outstanding: float}
     */
    private function manualSales(Carbon $start, Carbon $end): array
    {
        $finalized = ManualInvoice::query()->where('status', ManualInvoice::STATUS_FINALIZED);
        // whereDate rather than whereBetween: a date column holds a bare date
        // on MySQL and a midnight timestamp on SQLite, and only a date
        // comparison treats the last day of the range the same on both.
        $inRange = (clone $finalized)
            ->whereDate('invoice_date', '>=', $start->toDateString())
            ->whereDate('invoice_date', '<=', $end->toDateString());

        return [
            'count' => (clone $inRange)->count(),
            'invoiced' => (float) (clone $inRange)->sum('total'),
            // Money that actually came in during the range, whichever
            // invoice it was for. Refunds are negative rows, so they net out.
            'collected' => (float) ManualInvoicePayment::query()
                ->whereDate('paid_on', '>=', $start->toDateString())
                ->whereDate('paid_on', '<=', $end->toDateString())
                ->sum('amount'),
            // Owed right now across every finalized invoice, not just these.
            'outstanding' => max((float) (clone $finalized)->sum('total') - (float) (clone $finalized)->sum('paid_amount'), 0),
        ];
    }

    public function export(Request $request): StreamedResponse
    {
        [$start, $end] = $this->resolveRange($request);
        $rangeDays = max(1, (int) $start->diffInDays($end) + 1);

        $dailyRows = Order::query()
            ->selectRaw('DATE(created_at) as order_day, COUNT(*) as orders_count, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->whereIn('status', self::PAID_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('order_day')
            ->orderBy('order_day')
            ->get()
            ->keyBy('order_day');

        // The sheet adds up to the revenue on the page, so it carries the
        // manual invoices too, in a column of their own.
        $manualByDay = ManualInvoice::invoicedByDay($start, $end);
        $manualCountByDay = ManualInvoice::countByDay($start, $end);

        $filename = 'revenue-'.$start->toDateString().'-to-'.$end->toDateString().'.csv';

        return response()->streamDownload(function () use ($dailyRows, $manualByDay, $manualCountByDay, $start, $end, $rangeDays) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Sales', 'Site Orders', 'Manual Invoices', 'Revenue']);

            $totalOrders = 0;
            $totalSite = 0.0;
            $totalManual = 0.0;
            for ($i = 0; $i < $rangeDays; $i++) {
                $key = $start->copy()->addDays($i)->toDateString();
                $orders = (int) ($dailyRows[$key]->orders_count ?? 0) + (int) ($manualCountByDay[$key] ?? 0);
                $site = (float) ($dailyRows[$key]->total_revenue ?? 0);
                $manual = (float) ($manualByDay[$key] ?? 0);
                $totalOrders += $orders;
                $totalSite += $site;
                $totalManual += $manual;
                fputcsv($out, [$key, $orders, $site, $manual, $site + $manual]);
            }

            fputcsv($out, ['Total ('.$start->toDateString().' to '.$end->toDateString().')', $totalOrders, $totalSite, $totalManual, $totalSite + $totalManual]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: int, 3: array<int>, 4: bool, 5: Carbon}
     */
    private function resolveRange(Request $request): array
    {
        $allowedDays = [7, 30, 90, 365];
        $days = (int) $request->query('days', 30);
        if (! in_array($days, $allowedDays, true)) {
            $days = 30;
        }

        $now = Carbon::now();
        $end = $now->copy()->endOfDay();
        $start = $now->copy()->subDays($days - 1)->startOfDay();
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $customRange = false;

        try {
            if ($from !== '' && $to !== '') {
                $customStart = Carbon::parse($from)->startOfDay();
                $customEnd = Carbon::parse($to)->endOfDay();
                if ($customStart->lessThanOrEqualTo($customEnd)) {
                    $start = $customStart;
                    $end = $customEnd;
                    $customRange = true;
                }
            }
        } catch (\Throwable $e) {
            // Keep default days filter if date parsing fails.
        }

        return [$start, $end, $days, $allowedDays, $customRange, $now];
    }

    private function percentageChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }
}
