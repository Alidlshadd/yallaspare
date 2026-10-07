<?php

namespace App\Services\Pricing;

use App\Models\Product;
use App\Models\ProductPriceChange;
use App\Models\Setting;
use App\Models\User;
use App\Support\Pricing\ExchangeRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Setting what a product sells for, as opposed to setting the exchange rate.
 *
 * Three operations live here: converting a dinar price to dollars once, at
 * the rate the old prices were set at; editing one product's price; and
 * changing many prices at once. Each works out the new figures without
 * saving them (the plan), so the admin can be shown exactly what will
 * happen, and applies the same plan when asked to.
 *
 * Every price is saved through the Product model, which derives the dinar
 * columns of a dollar product from its dollar amount on each save. Cost,
 * stock and purchase records are never written here.
 */
class ProductPriceService
{
    public const MODE_PERCENT = 'percent';

    public const MODE_USD = 'usd';

    public const MODE_IQD = 'iqd';

    public const MODES = [self::MODE_PERCENT, self::MODE_USD, self::MODE_IQD];

    /** Whole dinars a price column can hold on every install. */
    private const MAX_IQD = '99999999';

    // ── Converting dinar prices to dollars ─────────────────────────

    /**
     * Products a conversion may touch: priced in dinars, with a price.
     * A product already in dollars is never in here, which is what makes
     * running the conversion again harmless.
     *
     * @return Builder<Product>
     */
    public function convertible(): Builder
    {
        return Product::query()
            ->where(fn (Builder $query) => $query->where('price_currency', '!=', ExchangeRate::USD)->orWhereNull('price_currency'))
            ->where('price', '>', 0);
    }

    /**
     * What converting one product would do, without doing it.
     *
     * The old dinar price is divided by the conversion rate to get dollars;
     * what it will then sell for is those dollars at the selling rate. With
     * both rates equal the dinar price does not move at all.
     *
     * @return array{product: Product, old_iqd: float, usd: string, new_iqd: float, old_dealer_iqd: ?float, dealer_usd: ?string, new_dealer_iqd: ?float}
     */
    public function conversionPlan(Product $product, string $conversionRate): array
    {
        $usd = ExchangeRate::fromIqd($product->price, $conversionRate);
        $dealerUsd = $product->dealer_price !== null ? ExchangeRate::fromIqd($product->dealer_price, $conversionRate) : null;

        return [
            'product' => $product,
            'old_iqd' => (float) $product->price,
            'usd' => $usd,
            'new_iqd' => (float) ExchangeRate::toIqd($usd),
            'old_dealer_iqd' => $product->dealer_price !== null ? (float) $product->dealer_price : null,
            'dealer_usd' => $dealerUsd,
            'new_dealer_iqd' => $dealerUsd !== null ? (float) ExchangeRate::toIqd($dealerUsd) : null,
        ];
    }

    /**
     * Move dinar-priced products to dollars.
     *
     * Each product is locked and looked at again before it is changed: one
     * that has become a dollar product in the meantime — a second click, a
     * second tab, a second admin — is skipped, so no price is ever divided
     * twice. The purchase price is left exactly as it is.
     *
     * @param  array<int, int>|null  $productIds  null for every convertible product
     * @return array{converted: int, skipped: int, batch: string}
     */
    public function convertToUsd(?array $productIds, string $conversionRate, ?User $actor): array
    {
        $rate = ExchangeRate::normalizeRate($conversionRate);

        if ($rate === null) {
            throw ValidationException::withMessages(['conversion_rate' => __('Enter the conversion rate as a number greater than zero.')]);
        }

        $this->assertSellingRate();

        $batch = (string) Str::uuid();
        $converted = 0;
        $requested = $productIds !== null ? count(array_unique($productIds)) : null;

        $this->convertible()
            ->when($productIds !== null, fn (Builder $query) => $query->whereIn('id', $productIds))
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use ($rate, $actor, $batch, &$converted): void {
                foreach ($chunk as $row) {
                    $converted += DB::transaction(function () use ($row, $rate, $actor, $batch): int {
                        $product = Product::query()->whereKey($row->id)->lockForUpdate()->first();

                        if (! $product || $product->isUsdPriced() || (float) $product->price <= 0) {
                            return 0;
                        }

                        $plan = $this->conversionPlan($product, $rate);
                        $before = $this->snapshot($product);

                        $product->forceFill([
                            'price_currency' => ExchangeRate::USD,
                            'price_usd' => $plan['usd'],
                            'dealer_price_usd' => $plan['dealer_usd'],
                        ])->save();

                        $this->record($product, $before, ProductPriceChange::ACTION_CONVERSION, $batch, $actor, $rate);

                        return 1;
                    });
                }
            });

        if ($converted > 0) {
            Setting::setValue(ExchangeRate::SETTING_CONVERSION_RATE, $rate);
            $this->bumpPriceVersion();
        }

        return [
            'converted' => $converted,
            'skipped' => $requested !== null ? max($requested - $converted, 0) : 0,
            'batch' => $batch,
        ];
    }

    // ── One product ────────────────────────────────────────────────

    /**
     * What an edit would do, without doing it.
     *
     * A dollar product is edited either in dollars or by naming the dinar
     * price it should sell for, in which case the dollar price that sells
     * for exactly that at the current rate is worked out. A dinar product
     * has only its dinar price; it is not moved to dollars by an edit.
     *
     * @return array{usd: ?string, iqd: string, exact: bool}
     */
    public function editPlan(Product $product, ?string $usd, ?string $targetIqd): array
    {
        if ($product->isUsdPriced()) {
            $this->assertSellingRate();

            if ($targetIqd !== null) {
                $target = $this->dinars($targetIqd, 'target_iqd');
                $plan = ExchangeRate::usdForTargetIqd($target);

                return ['usd' => $plan['usd'], 'iqd' => $plan['iqd'], 'exact' => $plan['exact']];
            }

            // Typed, not computed: more decimals than a price is kept to is
            // a slip to point out, not something to round away quietly.
            $amount = preg_match('/^\d{1,7}(\.\d{1,4})?$/', trim((string) $usd)) === 1
                ? ExchangeRate::decimal(trim((string) $usd), ExchangeRate::USD_SCALE)
                : null;

            if ($amount === null || bccomp($amount, '0', ExchangeRate::USD_SCALE) <= 0) {
                throw ValidationException::withMessages(['usd_price' => __('Enter a USD price greater than zero, with at most four decimals.')]);
            }

            $iqd = ExchangeRate::toIqd($amount);
            $this->assertFits($iqd, 'usd_price');

            return ['usd' => $amount, 'iqd' => $iqd, 'exact' => true];
        }

        if ($targetIqd === null) {
            throw ValidationException::withMessages(['target_iqd' => __('This product is priced in IQD. Enter its IQD price, or convert it to USD first.')]);
        }

        return ['usd' => null, 'iqd' => $this->dinars($targetIqd, 'target_iqd'), 'exact' => true];
    }

    /**
     * Save an edit. Returns the history row, or null when the price asked
     * for is the price the product already has.
     */
    public function setPrice(Product $product, ?string $usd, ?string $targetIqd, ?User $actor): ?ProductPriceChange
    {
        $change = DB::transaction(function () use ($product, $usd, $targetIqd, $actor): ?ProductPriceChange {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $plan = $this->editPlan($locked, $usd, $targetIqd);

            return $this->applyPrice($locked, $plan['usd'], $plan['iqd'], null, false, ProductPriceChange::ACTION_EDIT, (string) Str::uuid(), $actor);
        });

        if ($change) {
            $this->bumpPriceVersion();
        }

        return $change;
    }

    // ── Many products ──────────────────────────────────────────────

    /**
     * What a bulk change would do to one product, without doing it.
     *
     * A percentage is applied to the price as it is stored — dollars for a
     * dollar product, dinars for a dinar one — and to the dealer price with
     * it, so the dealer's margin keeps its proportion. A fixed amount moves
     * the selling price only: in the product's own currency it is simply
     * added; across currencies it is added to the dinar selling price and,
     * for a dollar product, turned back into the dollars that sell for it.
     *
     * @return array{product: Product, old_usd: ?string, new_usd: ?string, old_iqd: float, new_iqd: float, old_dealer_iqd: ?float, new_dealer_iqd: ?float, new_dealer_usd: ?string, problem: ?string}
     */
    public function bulkPlan(Product $product, string $mode, string $value): array
    {
        $isUsd = $product->isUsdPriced();
        $oldIqd = (float) $product->price;
        $row = [
            'product' => $product,
            'old_usd' => $isUsd ? (string) $product->price_usd : null,
            'new_usd' => null,
            'old_iqd' => $oldIqd,
            'new_iqd' => $oldIqd,
            'old_dealer_iqd' => $product->dealer_price !== null ? (float) $product->dealer_price : null,
            'new_dealer_iqd' => $product->dealer_price !== null ? (float) $product->dealer_price : null,
            'new_dealer_usd' => $isUsd && $product->dealer_price_usd !== null ? (string) $product->dealer_price_usd : null,
            'problem' => null,
        ];

        if ($isUsd && $product->price_usd === null) {
            return ['problem' => __('This product has no USD price on file.')] + $row;
        }

        $scale = 8;
        $newUsd = null;
        $newIqd = null;
        $dealerUsd = $row['new_dealer_usd'];
        $dealerIqd = $product->dealer_price !== null ? (string) $product->dealer_price : null;

        if ($mode === self::MODE_PERCENT) {
            $factor = bcadd('1', bcdiv($value, '100', $scale), $scale);

            if ($isUsd) {
                $newUsd = $this->roundUsd(bcmul((string) $product->price_usd, $factor, $scale));
                $dealerUsd = $dealerUsd !== null ? $this->roundUsd(bcmul($dealerUsd, $factor, $scale)) : null;
            } else {
                $newIqd = $this->roundDinars(bcmul((string) $product->price, $factor, $scale));
                $dealerIqd = $dealerIqd !== null ? $this->roundDinars(bcmul($dealerIqd, $factor, $scale)) : null;
            }
        } elseif ($mode === self::MODE_USD) {
            if ($isUsd) {
                $newUsd = $this->roundUsd(bcadd((string) $product->price_usd, $value, $scale));
            } else {
                $sign = bccomp($value, '0', $scale) < 0 ? '-' : '';
                $step = ExchangeRate::toIqd(ltrim($value, '-'));
                $newIqd = $this->roundDinars(bcadd((string) $product->price, $sign.$step, $scale));
            }
        } else {
            $target = $this->roundDinars(bcadd((string) $product->price, $value, $scale));

            if ($isUsd) {
                if (bccomp($target, '0', 0) > 0) {
                    $newUsd = ExchangeRate::usdForTargetIqd($target)['usd'];
                } else {
                    $newUsd = '0';
                }
            } else {
                $newIqd = $target;
            }
        }

        if ($isUsd) {
            if (bccomp((string) $newUsd, '0', ExchangeRate::USD_SCALE) <= 0) {
                return ['problem' => __('The price would fall to zero or below.')] + $row;
            }

            $newIqd = ExchangeRate::toIqd($newUsd);
            $dealerIqd = $dealerUsd !== null ? ExchangeRate::toIqd($dealerUsd) : null;
        } elseif (bccomp((string) $newIqd, '0', 0) <= 0) {
            return ['problem' => __('The price would fall to zero or below.')] + $row;
        }

        if (bccomp((string) $newIqd, self::MAX_IQD, 0) > 0) {
            return ['problem' => __('The price would be too large to store.')] + $row;
        }

        return [
            'new_usd' => $isUsd ? $newUsd : null,
            'new_iqd' => (float) $newIqd,
            'new_dealer_iqd' => $dealerIqd !== null ? (float) $dealerIqd : null,
            'new_dealer_usd' => $isUsd ? $dealerUsd : null,
        ] + $row;
    }

    /**
     * Apply a bulk change to every product the query selects.
     *
     * Each product is planned again under a lock at the moment it is saved,
     * so the figures applied are computed from the price it has then, not
     * from the one on the preview. A product the change cannot be applied
     * to is skipped and counted.
     *
     * @param  Builder<Product>  $products
     * @return array{changed: int, skipped: int, batch: string}
     */
    public function applyBulk(Builder $products, string $mode, string $value, ?User $actor): array
    {
        $value = $this->bulkValue($mode, $value);
        $batch = (string) Str::uuid();
        $changed = 0;
        $skipped = 0;

        (clone $products)->select('products.id')->orderBy('products.id')->chunkById(200, function ($chunk) use ($mode, $value, $actor, $batch, &$changed, &$skipped): void {
            foreach ($chunk as $row) {
                $done = DB::transaction(function () use ($row, $mode, $value, $actor, $batch): bool {
                    $product = Product::query()->whereKey($row->id)->lockForUpdate()->first();

                    if (! $product) {
                        return false;
                    }

                    $plan = $this->bulkPlan($product, $mode, $value);

                    if ($plan['problem'] !== null) {
                        return false;
                    }

                    $dealer = $product->isUsdPriced() ? $plan['new_dealer_usd'] : ($plan['new_dealer_iqd'] !== null ? (string) $plan['new_dealer_iqd'] : null);

                    return $this->applyPrice($product, $plan['new_usd'], (string) $plan['new_iqd'], $dealer, $mode === self::MODE_PERCENT, ProductPriceChange::ACTION_BULK, $batch, $actor, $this->bulkNote($mode, $value)) !== null;
                });

                $done ? $changed++ : $skipped++;
            }
        }, 'products.id', 'id');

        if ($changed > 0) {
            $this->bumpPriceVersion();
        }

        return ['changed' => $changed, 'skipped' => $skipped, 'batch' => $batch];
    }

    /**
     * The amount of a bulk change as a signed decimal string, or a
     * validation error the form can show.
     */
    public function bulkValue(string $mode, mixed $value): string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        $negative = str_starts_with($text, '-');
        $amount = ExchangeRate::decimal(ltrim($text, '+-'), $mode === self::MODE_IQD ? 0 : ExchangeRate::USD_SCALE);

        if (! in_array($mode, self::MODES, true) || $amount === null || bccomp($amount, '0', ExchangeRate::USD_SCALE) === 0) {
            throw ValidationException::withMessages(['bulk_value' => __('Enter the change as a number other than zero. Use a minus sign for a decrease.')]);
        }

        if ($mode === self::MODE_PERCENT && ($negative ? bccomp($amount, '100', 4) >= 0 : bccomp($amount, '1000', 4) > 0)) {
            throw ValidationException::withMessages(['bulk_value' => __('A percentage must be between -99.99 and 1000.')]);
        }

        if ($mode !== self::MODE_PERCENT) {
            $this->assertSellingRate($mode === self::MODE_IQD);
        }

        return ($negative ? '-' : '').$amount;
    }

    // ── Shared ─────────────────────────────────────────────────────

    /**
     * Write a price to a locked product and record it. A dollar product is
     * given dollars and derives its dinars; a dinar product is given dinars.
     */
    private function applyPrice(Product $product, ?string $usd, string $iqd, ?string $dealer, bool $setDealer, string $action, string $batch, ?User $actor, ?string $note = null): ?ProductPriceChange
    {
        $before = $this->snapshot($product);

        if ($product->isUsdPriced()) {
            $product->price_usd = $usd;

            if ($setDealer) {
                $product->dealer_price_usd = $dealer;
            }
        } else {
            $product->price = $iqd;

            if ($setDealer) {
                $product->dealer_price = $dealer;
            }
        }

        if (! $product->isDirty(['price_usd', 'dealer_price_usd', 'price', 'dealer_price'])) {
            return null;
        }

        $product->save();

        return $this->record($product, $before, $action, $batch, $actor, null, $note);
    }

    /**
     * @return array{currency: string, iqd: mixed, usd: mixed, dealer_iqd: mixed, dealer_usd: mixed}
     */
    private function snapshot(Product $product): array
    {
        return [
            'currency' => $product->isUsdPriced() ? ExchangeRate::USD : ExchangeRate::IQD,
            'iqd' => $product->price,
            'usd' => $product->price_usd,
            'dealer_iqd' => $product->dealer_price,
            'dealer_usd' => $product->dealer_price_usd,
        ];
    }

    /**
     * @param  array{currency: string, iqd: mixed, usd: mixed, dealer_iqd: mixed, dealer_usd: mixed}  $before
     */
    private function record(Product $product, array $before, string $action, string $batch, ?User $actor, ?string $conversionRate = null, ?string $note = null): ProductPriceChange
    {
        return ProductPriceChange::query()->create([
            'product_id' => $product->id,
            'product_name' => $product->name_en,
            'product_sku' => $product->sku,
            'batch_id' => $batch,
            'action' => $action,
            'old_currency' => $before['currency'],
            'new_currency' => $product->isUsdPriced() ? ExchangeRate::USD : ExchangeRate::IQD,
            'old_price_iqd' => $before['iqd'],
            'new_price_iqd' => $product->price,
            'old_price_usd' => $before['usd'],
            'new_price_usd' => $product->price_usd,
            'old_dealer_price_iqd' => $before['dealer_iqd'],
            'new_dealer_price_iqd' => $product->dealer_price,
            'old_dealer_price_usd' => $before['dealer_usd'],
            'new_dealer_price_usd' => $product->dealer_price_usd,
            'conversion_rate_per_100' => $conversionRate,
            'selling_rate_per_100' => ExchangeRate::perHundred(),
            'note' => $note,
            'user_id' => $actor?->getKey(),
        ]);
    }

    private function bulkNote(string $mode, string $value): string
    {
        $sign = str_starts_with($value, '-') ? '' : '+';
        $plain = rtrim(rtrim($value, '0'), '.');

        return match ($mode) {
            self::MODE_PERCENT => $sign.$plain.'%',
            self::MODE_USD => $sign.$plain.' USD',
            default => $sign.$plain.' IQD',
        };
    }

    private function bumpPriceVersion(): void
    {
        Setting::setValue(ExchangeRate::SETTING_PRICE_VERSION, (string) now()->getTimestampMs());
    }

    private function assertSellingRate(bool $onlyIfUsdProductsExist = false): void
    {
        if (ExchangeRate::isConfigured()) {
            return;
        }

        if ($onlyIfUsdProductsExist && ! Product::query()->where('price_currency', ExchangeRate::USD)->exists()) {
            return;
        }

        throw ValidationException::withMessages(['usd_rate_per_100' => __('Set the exchange rate first.')]);
    }

    private function dinars(string $value, string $field): string
    {
        $amount = ExchangeRate::decimal(str_replace([',', ' '], '', trim($value)), 0);

        if ($amount === null || bccomp($amount, '0', 0) <= 0) {
            throw ValidationException::withMessages([$field => __('Enter an IQD price greater than zero.')]);
        }

        $this->assertFits($amount, $field);

        return $amount;
    }

    private function assertFits(string $iqd, string $field): void
    {
        if (bccomp($iqd, self::MAX_IQD, 0) > 0) {
            throw ValidationException::withMessages([$field => __('The price would be too large to store.')]);
        }
    }

    /** Half-up to four decimals; a negative result is left negative for the caller to refuse. */
    private function roundUsd(string $value): string
    {
        if (str_starts_with($value, '-')) {
            return '-'.(ExchangeRate::decimal(ltrim($value, '-'), ExchangeRate::USD_SCALE) ?? '0');
        }

        return ExchangeRate::decimal($value, ExchangeRate::USD_SCALE) ?? '0';
    }

    /** Half-up to a whole dinar; a negative result is left negative for the caller to refuse. */
    private function roundDinars(string $value): string
    {
        if (str_starts_with($value, '-')) {
            return '-'.(ExchangeRate::decimal(ltrim($value, '-'), 0) ?? '0');
        }

        return ExchangeRate::decimal($value, 0) ?? '0';
    }
}
