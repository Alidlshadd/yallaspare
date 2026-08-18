<?php

namespace App\Http\Controllers;

use App\Support\Garage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GarageController extends Controller
{
    /**
     * Save the vehicle the customer picked on a product page.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_model_id' => ['required', 'integer', 'exists:vehicle_models,id'],
            // Bounded rather than free: the field is a picker, and a stray value
            // would only ever come from a tampered form.
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 2)],
        ]);

        Garage::put((int) $validated['vehicle_model_id'], $validated['year'] ?? null);

        return back()->with('success', __('Vehicle saved to your garage.'));
    }

    public function destroy(): RedirectResponse
    {
        Garage::forget();

        return back();
    }
}
