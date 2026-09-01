<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Goal;
use App\Models\ProductBrand;
use App\Models\VehicleBrand;
use App\Services\Goals\GoalAnalyticsService;
use App\Services\Goals\GoalMetricService;
use App\Services\Goals\GoalMotivationService;
use App\Services\Goals\GoalProgressService;
use App\Services\Goals\PeriodRangeResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ProgressCenterController extends Controller
{
    public function index(Request $request, PeriodRangeResolver $periods, GoalMetricService $metrics, GoalProgressService $progress, GoalAnalyticsService $analytics, GoalMotivationService $motivation): View
    {
        try {
            $period = $periods->resolve($request->query('period'), $request->query('anchor'));
        } catch (Throwable) {
            $period = $periods->resolve();
        }

        $goals = Goal::query()
            ->where('period_type', $period['type'])
            ->whereDate('period_start', $period['anchor'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('deadline')
            ->get()
            ->map(function (Goal $goal) use ($progress, $period): Goal {
                $evaluation = $progress->evaluate($goal, $period);
                $evaluation['status_label'] = match ($evaluation['status']) {
                    'completed' => __('Completed'),
                    'at_risk' => __('At Risk'),
                    'failed' => __('Failed'),
                    'in_progress' => __('In Progress'),
                    default => __('Not Started'),
                };

                return $goal->withEvaluation($evaluation);
            });

        $completed = $goals->filter(fn (Goal $goal) => $goal->evaluation['status'] === 'completed')->count();
        $overall = $goals->isEmpty() ? 0 : round((float) $goals->avg(fn (Goal $goal) => $goal->evaluation['progress']));
        $needsAttention = $goals->filter(fn (Goal $goal) => in_array($goal->evaluation['status'], ['at_risk', 'failed'], true))->count();
        $analyticsData = $analytics->dashboard($period, $goals);
        $motivationData = $motivation->overview();
        $analyticsData['cards']['current_streak'] = [
            'label' => __('Current Streak'), 'icon' => 'fa-fire', 'value' => $motivationData['streak'],
            'previous' => null, 'change' => null, 'unit' => 'days',
        ];

        return view('admin.goals.index', [
            'period' => $period,
            'goals' => $goals,
            'completed' => $completed,
            'overall' => $overall,
            'needsAttention' => $needsAttention,
            'metricDefinitions' => $metrics->definitions(),
            'categories' => Category::query()->orderBy('name_en')->get(['id', 'name_en', 'name_ar', 'name_ku']),
            'productBrands' => ProductBrand::query()->orderBy('name')->get(['id', 'name']),
            'vehicleBrands' => VehicleBrand::query()->orderBy('name')->get(['id', 'name']),
            'kpis' => $analyticsData['cards'],
            'trend' => $analyticsData['trend'],
            'insights' => $analyticsData['insights'],
            'motivation' => $motivationData,
            'timeline' => $goals->sortBy('deadline')->values(),
        ]);
    }
}
