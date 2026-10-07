<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceChange;
use App\Models\Setting;
use App\Models\User;
use App\Services\Pricing\ExchangeRateService;
use App\Services\Pricing\ProductPriceService;
use App\Support\Pricing\ExchangeRate;
use Illuminate\Http\JsonResponse;
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
    /** Rows shown in a preview; the operation itself covers every product. */
    private const PREVIEW_ROWS = 100;

    public function edit(Request $request, ProductPriceService $prices): View
    {
        $updatedAt = trim((string) Setting::getValue(ExchangeRate::SETTING_UPDATED_AT, ''));
        $canManagePrices = (bool) $request->user()?->can(User::PERMISSION_PRODUCTS_MANAGE);
        $rateIsSet = ExchangeRate::isConfigured();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'currency' => in_array($request->query('currency'), [ExchangeRate::USD, ExchangeRate::IQD], true) ? (string) $request->query('currency') : '',
            'category_id' => (int) $request->query('category_id', 0) > 0 ? (string) (int) $request->query('category_id') : '',
        ];

        // ── Converting dinar prices to dollars ──
        $conversionRate = ExchangeRate::normalizeRate(PriceManagementController::plainNumber((string) $request->query('conversion_rate', '')))
            ?? ExchangeRate::conversionRate();
        $convertibleCount = $prices->convertible()->count();
        $conversion = null;

        if ($canManagePrices && $rateIsSet && $request->query('convert') === 'preview' && $convertibleCount > 0) {
            $conversion = [
                'rows' => $prices->convertible()->orderBy('name_en')->orderBy('id')->limit(self::PREVIEW_ROWS)->get()
                    ->map(fn (Product $product): array => $prices->conversionPlan($product, $conversionRate)),
                'token' => PriceManagementController::remember($request, ['type' => 'convert', 'conversion_rate' => $conversionRate]),
            ];
        }

        // ── A bulk change waiting to be confirmed ──
        $bulk = null;
        $bulkToken = (string) $request->query('bulk', '');
        $operation = $bulkToken !== '' ? PriceManagementController::peek($request, $bulkToken, 'bulk') : null;

        if ($canManagePrices && $operation !== null) {
            $scope = PriceManagementController::scope($operation);
            $rows = (clone $scope)->orderBy('name_en')->orderBy('id')->limit(self::PREVIEW_ROWS)->get()
                ->map(fn (Product $product): array => $prices->bulkPlan($product, (string) $operation['mode'], (string) $operation['value']));

            $bulk = [
                'token' => $bulkToken,
                'mode' => (string) $operation['mode'],
                'value' => (string) $operation['value'],
                'selected' => is_array($operation['ids'] ?? null),
                'ids' => $operation['ids'] ?? [],
                'count' => (clone $scope)->count(),
                'rows' => $rows,
                'problems' => $rows->whereNotNull('problem')->count(),
            ];
        }

        return view('admin.exchange-rate.edit', [
            'canManagePrices' => $canManagePrices,
            'filters' => $filters,
            'products' => PriceManagementController::filtered($filters)
                ->orderBy('name_en')
                ->orderBy('id')
                ->paginate(20, ['id', 'slug', 'name_en', 'name_ar', 'name_ku', 'sku', 'part_number', 'price', 'dealer_price', 'price_usd', 'price_currency', 'is_active'])
                ->withQueryString(),
            'categories' => Category::query()->orderBy('name_en')->get(['id', 'name_en', 'name_ar', 'name_ku']),
            'conversionRate' => $conversionRate,
            'convertibleCount' => $convertibleCount,
            'conversion' => $conversion,
            'previewRows' => self::PREVIEW_ROWS,
            'bulk' => $bulk,
            'history' => ProductPriceChange::query()->with('user:id,name')->latest('id')->limit(15)->get(),
            'ratePer100' => ExchangeRate::perHundred(),
            'ratePerDollar' => ExchangeRate::perDollar(),
            'defaultCurrency' => ExchangeRate::defaultCurrency(),
            'updatedAt' => $updatedAt !== '' ? rescue(fn () => Carbon::parse($updatedAt), null, false) : null,
            'updatedBy' => trim((string) Setting::getValue(ExchangeRate::SETTING_UPDATED_BY_NAME, '')),
            'usdProductCount' => Product::query()->where('price_currency', ExchangeRate::USD)->count(),
            'iqdProductCount' => PriceManagementController::filtered(['currency' => ExchangeRate::IQD])->count(),
        ]);
    }

    public function update(Request $request, ExchangeRateService $exchangeRates): RedirectResponse|JsonResponse
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'rate' => ExchangeRate::perHundred(),
                'default_currency' => ExchangeRate::defaultCurrency(),
                'updated_at' => Setting::getValue(ExchangeRate::SETTING_UPDATED_AT, ''),
                'updated_by' => Setting::getValue(ExchangeRate::SETTING_UPDATED_BY_NAME, ''),
            ]);
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
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.', '٬' => '',
        ]);

        return preg_match('/^\d{1,3}([, ]\d{3})+(\.\d+)?$/', $value) === 1
            ? str_replace([',', ' '], '', $value)
            : $value;
    }
}
