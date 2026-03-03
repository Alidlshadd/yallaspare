<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = Setting::allWithDefaults();

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'currency_code' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'site_logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $currentLogo = (string) Setting::getValue('site_logo', '');
        $newLogo = $currentLogo;

        if ($request->boolean('remove_logo')) {
            if ($currentLogo !== '') {
                Storage::disk('public')->delete($currentLogo);
            }
            $newLogo = '';
        }

        if ($request->hasFile('site_logo')) {
            if ($currentLogo !== '') {
                Storage::disk('public')->delete($currentLogo);
            }
            $newLogo = $request->file('site_logo')->store('settings', 'public');
        }

        Setting::setMany([
            'site_name' => $data['site_name'],
            'currency_code' => strtoupper($data['currency_code']),
            'currency_symbol' => $data['currency_symbol'],
            'low_stock_threshold' => (string) $data['low_stock_threshold'],
            'site_logo' => $newLogo,
        ]);

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'System settings updated successfully.');
    }
}
