<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Support\SqlSafe;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AdminActivityLogController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', Activity::class);

        $model = request()->query('model');
        $search = trim((string) request()->query('q', ''));

        $modelMap = [
            'Product' => Product::class,
            'Catalog' => Category::class,
            'Category' => Category::class,
            'Inventory' => InventoryMovement::class,
            'User' => User::class,
        ];

        $logs = Activity::query()
            ->select(['id', 'log_name', 'description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties', 'created_at'])
            ->with(['causer:id,name,email'])
            ->when($model && isset($modelMap[$model]), function ($query) use ($modelMap, $model) {
                $query->where('subject_type', $modelMap[$model]);
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
}
