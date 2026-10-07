<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Pricing\ExchangeRateService;
use App\Support\Pricing\ExchangeRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The dollar rate on a page of its own.
 *
 * It used to be one tab of the system settings form, which meant changing
 * the rate went through every other setting's validation and a password
 * prompt. The rate is the one figure here that changes week to week, so it
 * gets a form that asks for nothing else.
 */
class ExchangeRateController extends Controller
{
    public function edit(): View
    {
        $updatedAt = trim((string) Setting::getValue(ExchangeRate::SETTING_UPDATED_AT, ''));

        return view('admin.exchange-rate.edit', [
            'ratePer100' => ExchangeRate::perHundred(),
            'ratePerDollar' => ExchangeRate::perDollar(),
            'defaultCurrency' => ExchangeRate::defaultCurrency(),
            'updatedAt' => $updatedAt !== '' ? rescue(fn () => Carbon::parse($updatedAt), null, false) : null,
            'updatedBy' => trim((string) Setting::getValue(ExchangeRate::SETTING_UPDATED_BY_NAME, '')),
            'usdProductCount' => Product::query()->where('price_currency', ExchangeRate::USD)->count(),
            'iqdProductCount' => Product::query()->where('price_currency', '!=', ExchangeRate::USD)->count(),
            'examples' => Product::query()
                ->where('price_currency', ExchangeRate::USD)
                ->whereNotNull('price_usd')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(['id', 'slug', 'name_en', 'name_ar', 'name_ku', 'sku', 'price', 'price_usd', 'price_currency']),
        ]);
    }

    public function update(Request $request, ExchangeRateService $exchangeRates): RedirectResponse
    {
        $typed = $request->input('usd_rate_per_100');

        if (is_string($typed)) {
            $request->merge(['usd_rate_per_100' => self::plainNumber($typed)]);
        }

        $data = $request->validate([
            'default_price_currency' => ['required', Rule::in(ExchangeRate::CURRENCIES)],
            // Left empty, the rate already on file stays: it can be changed
            // but never cleared, because dollar-priced products depend on it.
            'usd_rate_per_100' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (ExchangeRate::normalizeRate(is_scalar($value) ? (string) $value : null) === null) {
                        $fail(__('Enter how many IQD 100 USD is worth, as a number greater than zero (for example 150000).'));
                    }
                },
            ],
        ]);

        $rateGiven = filled($data['usd_rate_per_100'] ?? null);

        if ($data['default_price_currency'] === ExchangeRate::USD && ! $rateGiven && ! ExchangeRate::isConfigured()) {
            return back()->withInput()->withErrors([
                'usd_rate_per_100' => __('Set the exchange rate before making USD the default price currency.'),
            ]);
        }

        // Only the starting choice on the new-product form. Products already
        // saved keep the currency they were priced in.
        Setting::setValue(ExchangeRate::SETTING_DEFAULT_CURRENCY, $data['default_price_currency']);

        $message = __('Exchange rate settings saved.');

        if ($rateGiven) {
            $result = $exchangeRates->setRate((string) $data['usd_rate_per_100'], $request->user());

            if ($result['changed']) {
                $message = __('Exchange rate updated: 1 USD = :rate IQD. :count USD-priced products were repriced.', [
                    'rate' => ExchangeRate::perDollar(),
                    'count' => $result['repriced'],
                ]);
            }
        }

        return redirect()->route('admin.exchange-rate.edit')->with('success', $message);
    }

    /**
     * "150,000", "150 000" and "١٥٠٠٠٠" as the number they plainly are.
     * Anything else is left alone for validation to refuse.
     */
    private static function plainNumber(string $value): string
    {
        $value = strtr(trim($value), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٫' => '.', '٬' => '',
        ]);

        return preg_match('/^\d{1,3}([, ]\d{3})+(\.\d+)?$/', $value) === 1
            ? str_replace([',', ' '], '', $value)
            : $value;
    }
}
