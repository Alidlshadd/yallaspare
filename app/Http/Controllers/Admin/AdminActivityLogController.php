<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivityLogsExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Discount;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\SqlSafe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class AdminActivityLogController extends Controller
{
    private const MODEL_MAP = [
        'Product' => Product::class,
        'Catalog' => Category::class,
        'Category' => Category::class,
        'Inventory' => InventoryMovement::class,
        'User' => User::class,
        'Order' => Order::class,
        'Coupon' => Coupon::class,
        'Discount' => Discount::class,
        'Setting' => Setting::class,
    ];

    public function index(): View
    {
        $this->authorize('viewAny', Activity::class);

        $model = request()->query('model');
        $search = trim((string) request()->query('q', ''));

        $logs = Activity::query()
            ->select(['id', 'log_name', 'description', 'event', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties', 'batch_uuid', 'created_at'])
            ->with(['causer:id,name,email,role', 'subject'])
            ->when($model && isset(self::MODEL_MAP[$model]), function ($query) use ($model) {
                $query->where('subject_type', self::MODEL_MAP[$model]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    SqlSafe::whereLike($q, 'description', $search);
                    SqlSafe::orWhereLike($q, 'subject_type', $search);
                    SqlSafe::orWhereLike($q, 'subject_id', $search);
                    $q->orWhereHas('causer', function ($cq) use ($search) {
                        SqlSafe::whereLike($cq, 'name', $search);
                        SqlSafe::orWhereLike($cq, 'email', $search);
                    });
                    $q->orWhereHasMorph('subject', User::class, function ($sq) use ($search) {
                        SqlSafe::whereLike($sq, 'name', $search);
                        SqlSafe::orWhereLike($sq, 'email', $search);
                        SqlSafe::orWhereLike($sq, 'phone', $search);
                    });
                    $q->orWhereHasMorph('subject', Product::class, function ($sq) use ($search) {
                        SqlSafe::whereLike($sq, 'name_en', $search);
                        SqlSafe::orWhereLike($sq, 'sku', $search);
                        SqlSafe::orWhereLike($sq, 'brand', $search);
                    });
                    $q->orWhereHasMorph('subject', Category::class, function ($sq) use ($search) {
                        SqlSafe::whereLike($sq, 'name_en', $search);
                    });
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'model' => $model,
            'search' => $search,
            'totalCount' => Activity::count(),
            'modelCounts' => $this->modelFacetCounts(),
            'todayCount' => Activity::where('created_at', '>=', now(config('app.timezone'))->startOfDay()->utc())->count(),
            'topCauser' => $this->topCauser(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function modelFacetCounts(): array
    {
        $raw = Activity::query()
            ->select('subject_type')
            ->selectRaw('count(*) as aggregate')
            ->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->pluck('aggregate', 'subject_type');

        $counts = [];
        foreach (self::MODEL_MAP as $label => $class) {
            $counts[$label] = (int) ($raw[$class] ?? 0);
        }

        return $counts;
    }

    /**
     * @return array{name: string, count: int}|null
     */
    private function topCauser(): ?array
    {
        $row = Activity::query()
            ->select('causer_id')
            ->selectRaw('count(*) as aggregate')
            ->where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->groupBy('causer_id')
            ->orderByDesc('aggregate')
            ->first();

        if (! $row) {
            return null;
        }

        $causer = User::query()->select('id', 'name')->find($row->causer_id);

        if (! $causer) {
            return null;
        }

        return ['name' => $causer->name, 'count' => (int) $row->aggregate];
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('viewAny', Activity::class);

        $model = $request->query('model');
        $subjectType = $model && isset(self::MODEL_MAP[$model])
            ? self::MODEL_MAP[$model]
            : null;

        try {
            return Excel::download(
                new ActivityLogsExport([
                    'from' => $request->query('from'),
                    'to' => $request->query('to'),
                    'subject_type' => $subjectType,
                    'log_name' => $request->query('log_name'),
                ]),
                'activity-logs.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('Activity logs Excel export failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('Failed to export activity logs. Please try again.'));
        }
    }
}
