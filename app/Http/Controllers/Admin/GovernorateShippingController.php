<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGovernorateRequest;
use App\Http\Requests\Admin\UpdateGovernorateShippingRequest;
use App\Models\Governorate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GovernorateShippingController extends Controller
{
    public function index(): View
    {
        return view('admin.shipping.governorates', [
            'governorates' => Governorate::query()->ordered()->get(),
        ]);
    }

    public function update(UpdateGovernorateShippingRequest $request): RedirectResponse
    {
        $rows = collect($request->validated('rows'))->keyBy('id');

        DB::transaction(function () use ($rows): void {
            $governorates = Governorate::query()
                ->whereIn('id', $rows->keys())
                ->lockForUpdate()
                ->get();

            foreach ($governorates as $governorate) {
                $row = $rows[$governorate->id];

                $governorate->fill([
                    'delivery_days' => (int) $row['delivery_days'],
                    'shipping_fee' => (int) $row['shipping_fee'],
                ]);

                // A save on every row would touch nineteen updated_at columns
                // to record that nothing happened.
                if ($governorate->isDirty()) {
                    $governorate->save();
                }
            }
        });

        return back()->with('success', __('Shipping settings saved.'));
    }

    public function store(StoreGovernorateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Governorate::query()->create([
            // The operator never sees or types this. It is derived once, on
            // creation, and then stays put even if the name is edited later.
            'code' => $this->uniqueCode($data['name_en']),
            'name_en' => $data['name_en'],
            'name_ar' => $data['name_ar'],
            'name_ku' => $data['name_ku'],
            'delivery_days' => (int) $data['delivery_days'],
            'shipping_fee' => (int) $data['shipping_fee'],
            // New places go to the end of the list rather than into the middle
            // of an order the standard governorates already have.
            'sort_order' => min(255, (int) Governorate::query()->max('sort_order') + 1),
        ]);

        return back()->with('success', __('Governorate added.'));
    }

    public function destroy(Governorate $governorate): RedirectResponse
    {
        // A standard governorate would come back with the next deploy, so
        // removing one would look like the panel had ignored the request.
        abort_if($governorate->isStandard(), 403);

        $governorate->delete();

        return back()->with('success', __('Governorate removed.'));
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name) ?: 'governorate';
        $code = Str::limit($base, 32, '');
        $suffix = 2;

        while (Governorate::query()->where('code', $code)->exists()) {
            $code = Str::limit($base, 30, '').'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}
