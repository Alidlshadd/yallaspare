<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\GoalProgressUpdate;
use App\Services\Goals\GoalMetricService;
use App\Services\Goals\PeriodRangeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GoalController extends Controller
{
    public function store(Request $request, PeriodRangeResolver $periods, GoalMetricService $metrics): RedirectResponse
    {
        $this->authorize('create', Goal::class);
        $data = $this->validated($request, $metrics);
        $period = $periods->resolve($data['period_type'], $data['period_anchor'] ?? null);
        $data = $this->normalize($data, $period, $metrics);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        Goal::create($data);

        return $this->backToPeriod($period)->with('status', __('Goal created successfully.'));
    }

    public function update(Request $request, Goal $goal, PeriodRangeResolver $periods, GoalMetricService $metrics): RedirectResponse
    {
        $this->authorize('update', $goal);
        $data = $this->validated($request, $metrics);
        $period = $periods->resolve($data['period_type'], $data['period_anchor'] ?? null);
        $data = $this->normalize($data, $period, $metrics);
        $data['updated_by'] = $request->user()->id;
        $goal->update($data);

        return $this->backToPeriod($period)->with('status', __('Goal updated successfully.'));
    }

    public function updateProgress(Request $request, Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);
        abort_unless($goal->tracking_mode === Goal::TRACKING_MANUAL, 422);
        $data = $request->validate(['value' => ['required', 'numeric', 'min:0'], 'note' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($goal, $data, $request): void {
            $goal->update(['manual_value' => $data['value'], 'updated_by' => $request->user()->id]);
            GoalProgressUpdate::create([
                'goal_id' => $goal->id, 'value' => $data['value'], 'note' => $data['note'] ?? null,
                'recorded_by' => $request->user()->id, 'recorded_at' => now(),
            ]);
        });

        return back()->with('status', __('Progress updated successfully.'));
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $this->authorize('delete', $goal);
        $goal->delete();

        return back()->with('status', __('Goal archived successfully.'));
    }

    private function validated(Request $request, GoalMetricService $metrics): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:2000'],
            'period_type' => ['required', Rule::in(Goal::periodTypes())], 'period_anchor' => ['nullable', 'date_format:Y-m-d'],
            'start_date' => ['required', 'date'], 'deadline' => ['required', 'date', 'after_or_equal:start_date'],
            'tracking_mode' => ['required', Rule::in([Goal::TRACKING_AUTOMATIC, Goal::TRACKING_MANUAL])],
            'metric_key' => ['nullable', Rule::requiredIf($request->input('tracking_mode') === Goal::TRACKING_AUTOMATIC), Rule::in($metrics->keys())],
            'target_value' => ['required', 'numeric', 'min:0'], 'unit' => ['required', Rule::in(Goal::UNITS)],
            'priority' => ['required', Rule::in(Goal::PRIORITIES)], 'reward_points' => ['required', 'integer', 'min:0', 'max:100000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'product_brand_id' => ['nullable', 'integer', 'exists:product_brands,id'],
            'vehicle_brand_id' => ['nullable', 'integer', 'exists:vehicle_brands,id'],
        ]);
    }

    private function normalize(array $data, array $period, GoalMetricService $metrics): array
    {
        $start = $period['start']->toDateString();
        $end = $period['display_end']->toDateString();
        abort_unless($data['start_date'] >= $start && $data['deadline'] <= $end, 422, __('Goal dates must be inside the selected period.'));
        $automatic = $data['tracking_mode'] === Goal::TRACKING_AUTOMATIC;
        $definition = $automatic ? $metrics->definitions()[$data['metric_key']] : null;
        $config = array_filter(Arr::only($data, ['category_id', 'product_brand_id', 'vehicle_brand_id']));

        return [
            'name' => $data['name'], 'description' => $data['description'] ?? null,
            'period_type' => $period['type'], 'period_start' => $period['anchor'], 'start_date' => $data['start_date'], 'deadline' => $data['deadline'],
            'tracking_mode' => $data['tracking_mode'], 'metric_key' => $automatic ? $data['metric_key'] : null,
            'metric_config' => $automatic && $config !== [] ? $config : null,
            'direction' => $automatic ? $definition['direction'] : Goal::DIRECTION_INCREASE,
            'baseline_value' => $automatic && $definition['direction'] === Goal::DIRECTION_DECREASE ? $metrics->valueFor($data['metric_key'], $config, $period) : 0,
            'target_value' => $data['target_value'], 'unit' => $automatic ? $definition['unit'] : $data['unit'],
            'priority' => $data['priority'], 'reward_points' => $data['reward_points'],
        ];
    }

    private function backToPeriod(array $period): RedirectResponse
    {
        return redirect()->route('admin.goals.index', ['period' => $period['type'], 'anchor' => $period['anchor']]);
    }
}
