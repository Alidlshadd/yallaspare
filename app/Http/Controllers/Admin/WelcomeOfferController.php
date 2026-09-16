<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WelcomeOfferController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'welcome_offer_enabled' => ['required', 'boolean'],
            'welcome_offer_type' => ['required', Rule::in(['percent', 'fixed', 'free_shipping'])],
            'welcome_offer_value' => ['required', 'numeric', 'min:0', $request->input('welcome_offer_type') === 'percent' ? 'max:100' : 'max:999999999'],
            'welcome_offer_minimum_subtotal' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'welcome_offer_maximum_discount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'welcome_offer_valid_days' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        if ($data['welcome_offer_type'] !== 'free_shipping' && (float) $data['welcome_offer_value'] <= 0) {
            return back()->withInput()->withErrors(['welcome_offer_value' => __('welcome.positive_value')]);
        }

        DB::transaction(fn () => Setting::setMany($data));

        return redirect()->to(route('admin.discounts.edit').'#welcome-offer')->with('success', __('welcome.saved'));
    }
}
