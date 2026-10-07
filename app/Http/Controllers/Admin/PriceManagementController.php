<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Pricing\ProductPriceService;
use App\Support\AdminLogger;
use App\Support\Pricing\ExchangeRate;
use App\Support\SqlSafe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The price operations on the exchange rate page: converting dinar prices
 * to dollars, editing one price, changing many.
 *
 * Every operation that touches more than one product is applied through a
 * one-time token minted when its preview was shown. The token is taken out
 * of the session before anything is written, so a double click, a refresh
 * or the back button finds nothing to apply a second time.
 */
class PriceManagementController extends Controller
{
    public const SESSION_KEY = 'price_management.operations';

    public function __construct(private readonly ProductPriceService $prices) {}

    /**
     * Save one product's price from its row.
     */
    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'basis' => ['nullable', Rule::in(['usd', 'iqd'])],
            'usd_price' => ['nullable', 'string', 'max:20'],
            'target_iqd' => ['nullable', 'string', 'max:20'],
        ]);

        $usd = filled($data['usd_price'] ?? null) ? self::plainNumber((string) $data['usd_price']) : null;
        $target = filled($data['target_iqd'] ?? null) ? self::plainNumber((string) $data['target_iqd']) : null;

        // Both fields are on the row; the one the admin typed in last says
        // which of them is the instruction.
        if ($target !== null && ($data['basis'] ?? null) === 'usd' && $usd !== null) {
            $target = null;
        } elseif ($target !== null) {
            $usd = null;
        }

        $change = $this->prices->setPrice($product, $usd, $target, $request->user());

        if (! $change) {
            $message = __('The price of :product is already that. Nothing was changed.', ['product' => $product->localizedName()]);

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'changed' => false, 'product' => $this->priceResponse($product->fresh())])
                : back()->with('warning', $message);
        }

        AdminLogger::log('product.price_changed', $product, [
            'old_iqd' => $change->old_price_iqd,
            'new_iqd' => $change->new_price_iqd,
            'old_usd' => $change->old_price_usd,
            'new_usd' => $change->new_price_usd,
        ]);

        $message = __('Price saved for :product: :old → :new IQD.', [
            'product' => $product->localizedName(),
            'old' => number_format((float) $change->old_price_iqd),
            'new' => number_format((float) $change->new_price_iqd),
        ]);

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'changed' => true, 'product' => $this->priceResponse($product->fresh())])
            : back()->with('success', $message);
    }

    private function priceResponse(Product $product): array
    {
        return [
            'id' => $product->id,
            'usd' => $product->price_usd,
            'iqd' => $product->price,
            'rate' => ExchangeRate::perHundred(),
        ];
    }

    /**
     * Convert dinar-priced products to dollars: the ticked ones, or all.
     */
    public function convert(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'scope' => ['required', Rule::in(['selected', 'all'])],
            'ids' => ['nullable', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);

        $operation = $this->consume($request, (string) $data['token'], 'convert');

        if ($operation === null) {
            return $this->toPage()->with('warning', __('This conversion was already applied or has expired. Nothing was changed.'));
        }

        $ids = $data['scope'] === 'selected' ? array_map('intval', $data['ids'] ?? []) : null;

        if ($ids === []) {
            throw ValidationException::withMessages(['ids' => __('Tick at least one product, or convert all of them.')]);
        }

        $result = $this->prices->convertToUsd($ids, (string) $operation['conversion_rate'], $request->user());

        AdminLogger::log('product.prices_converted_to_usd', null, [
            'converted' => $result['converted'],
            'skipped' => $result['skipped'],
            'conversion_rate_per_100' => $operation['conversion_rate'],
            'batch' => $result['batch'],
        ]);

        return $this->toPage()->with($result['converted'] > 0 ? 'success' : 'warning', __(':count products were converted to USD at 1 USD = :rate IQD. :skipped were skipped because they were already in USD.', [
            'count' => number_format($result['converted']),
            'rate' => ExchangeRate::perDollar((string) $operation['conversion_rate']),
            'skipped' => number_format($result['skipped']),
        ]));
    }

    /**
     * Work out a bulk change and show it; nothing is saved here.
     */
    public function bulkPreview(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'bulk_mode' => ['required', Rule::in(ProductPriceService::MODES)],
            'bulk_value' => ['required', 'string', 'max:20'],
            'bulk_scope' => ['required', Rule::in(['selected', 'filtered'])],
            'ids' => ['nullable', 'array', 'max:1000'],
            'ids.*' => ['integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', Rule::in(['', ExchangeRate::USD, ExchangeRate::IQD])],
            'category_id' => ['nullable', 'integer'],
        ]);

        $value = $this->prices->bulkValue((string) $data['bulk_mode'], self::plainNumber((string) $data['bulk_value']));
        $ids = array_values(array_unique(array_map('intval', $data['ids'] ?? [])));

        if ($data['bulk_scope'] === 'selected' && $ids === []) {
            throw ValidationException::withMessages(['ids' => __('Tick at least one product, or apply the change to all filtered products.')]);
        }

        $filters = [
            'q' => trim((string) ($data['q'] ?? '')),
            'currency' => (string) ($data['currency'] ?? ''),
            'category_id' => (string) ($data['category_id'] ?? ''),
        ];

        $token = $this->remember($request, [
            'type' => 'bulk',
            'mode' => (string) $data['bulk_mode'],
            'value' => $value,
            'ids' => $data['bulk_scope'] === 'selected' ? $ids : null,
            'filters' => $filters,
        ]);

        $redirect = route('admin.exchange-rate.edit', array_filter($filters, fn ($value) => $value !== '') + ['bulk' => $token]);

        return $request->expectsJson() ? response()->json(['redirect' => $redirect]) : redirect($redirect);
    }

    public function bulkApply(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:64']]);

        $operation = $this->consume($request, (string) $data['token'], 'bulk');

        if ($operation === null) {
            $redirect = $this->toPage()->with('warning', __('This price change was already applied or has expired. Nothing was changed.'));

            return $request->expectsJson() ? response()->json(['redirect' => $redirect->getTargetUrl()]) : $redirect;
        }

        $result = $this->prices->applyBulk(
            self::scope($operation),
            (string) $operation['mode'],
            (string) $operation['value'],
            $request->user(),
        );

        AdminLogger::log('product.prices_changed_in_bulk', null, [
            'mode' => $operation['mode'],
            'value' => $operation['value'],
            'changed' => $result['changed'],
            'skipped' => $result['skipped'],
            'batch' => $result['batch'],
        ]);

        $redirect = redirect()
            ->route('admin.exchange-rate.edit', array_filter((array) $operation['filters'], fn ($value) => $value !== ''))
            ->with($result['changed'] > 0 ? 'success' : 'warning', __('The price of :count products was changed. :skipped were skipped.', [
                'count' => number_format($result['changed']),
                'skipped' => number_format($result['skipped']),
            ]));

        return $request->expectsJson() ? response()->json(['redirect' => $redirect->getTargetUrl()]) : $redirect;
    }

    /**
     * The products a remembered bulk operation covers: the ticked ones, or
     * everything its filters select.
     *
     * @param  array<string, mixed>  $operation
     * @return Builder<Product>
     */
    public static function scope(array $operation): Builder
    {
        if (is_array($operation['ids'] ?? null)) {
            return Product::query()->whereIn('id', $operation['ids']);
        }

        return self::filtered((array) ($operation['filters'] ?? []));
    }

    /**
     * The product table's query for a set of filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public static function filtered(array $filters): Builder
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $currency = (string) ($filters['currency'] ?? '');
        $categoryId = (int) ($filters['category_id'] ?? 0);

        return Product::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = SqlSafe::searchTerm($search);

                $query->where(function (Builder $nested) use ($term): void {
                    SqlSafe::whereLike($nested, 'name_en', $term);
                    SqlSafe::orWhereLike($nested, 'name_ar', $term);
                    SqlSafe::orWhereLike($nested, 'name_ku', $term);
                    SqlSafe::orWhereLike($nested, 'sku', $term);
                    SqlSafe::orWhereLike($nested, 'part_number', $term);
                    SqlSafe::orWhereLike($nested, 'oem_number', $term);
                });
            })
            ->when($currency === ExchangeRate::USD, fn (Builder $query) => $query->where('price_currency', ExchangeRate::USD))
            ->when($currency === ExchangeRate::IQD, fn (Builder $query) => $query->where(
                fn (Builder $nested) => $nested->where('price_currency', '!=', ExchangeRate::USD)->orWhereNull('price_currency')
            ))
            ->when($categoryId > 0, fn (Builder $query) => $query->where('category_id', $categoryId));
    }

    /**
     * Keep an operation's parameters for its preview, under a fresh token.
     * A handful at most: an abandoned preview is pushed out by newer ones.
     *
     * @param  array<string, mixed>  $operation
     */
    public static function remember(Request $request, array $operation): string
    {
        $token = Str::random(40);
        $operations = (array) $request->session()->get(self::SESSION_KEY, []);
        $operations[$token] = $operation;

        $request->session()->put(self::SESSION_KEY, array_slice($operations, -6, null, true));

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function peek(Request $request, string $token, string $type): ?array
    {
        $operation = ((array) $request->session()->get(self::SESSION_KEY, []))[$token] ?? null;

        return is_array($operation) && ($operation['type'] ?? null) === $type ? $operation : null;
    }

    /**
     * Take an operation out of the session. Whoever gets it is the only one
     * who ever will, which is what stops a second apply.
     *
     * @return array<string, mixed>|null
     */
    private function consume(Request $request, string $token, string $type): ?array
    {
        $operation = self::peek($request, $token, $type);

        if ($operation !== null) {
            $operations = (array) $request->session()->get(self::SESSION_KEY, []);
            unset($operations[$token]);
            $request->session()->put(self::SESSION_KEY, $operations);
            $request->session()->save();
        }

        return $operation;
    }

    private function toPage(): RedirectResponse
    {
        return redirect()->route('admin.exchange-rate.edit');
    }

    /**
     * "17,000", "17 000" and "١٧٠٠٠" as the number they plainly are.
     */
    public static function plainNumber(string $value): string
    {
        $value = strtr(trim($value), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.', '٬' => '',
        ]);

        return preg_match('/^[+-]?\d{1,3}([, ]\d{3})+(\.\d+)?$/', $value) === 1
            ? str_replace([',', ' '], '', $value)
            : $value;
    }
}
