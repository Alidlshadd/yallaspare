<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivityLogsExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
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
    ];

    public function index(): View
    {
        $this->authorize('viewAny', Activity::class);

        $model = request()->query('model');
        $search = trim((string) request()->query('q', ''));

        $logs = Activity::query()
            ->select(['id', 'log_name', 'description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties', 'created_at'])
            ->with(['causer:id,name,email'])
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
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'model' => $model,
            'search' => $search,
        ]);
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
