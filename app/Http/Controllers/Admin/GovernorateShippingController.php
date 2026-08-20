<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGovernorateShippingRequest;
use App\Models\Governorate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
}
