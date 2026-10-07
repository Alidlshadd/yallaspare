<?php

namespace App\Services\Pricing;

use App\Models\Setting;
use App\Models\User;
use App\Support\AdminLogger;
use App\Support\Pricing\ExchangeRate;
use Illuminate\Support\Facades\DB;

/**
 * Changing the dollar rate, and everything that has to follow from it.
 */
class ExchangeRateService
{
    /**
     * Set the rate and bring every dollar-priced product's dinar price in
     * line with it, in one transaction: the shop never shows a new rate with
     * old prices or the other way round.
     *
     * Saving the rate it already has does nothing — the "last updated" stamp
     * records when the rate changed, not when the form was submitted.
     *
     * @return array{changed: bool, repriced: int}
     */
    public function setRate(mixed $perHundred, ?User $actor): array
    {
        $rate = ExchangeRate::normalizeRate($perHundred);

        if ($rate === null) {
            throw new \InvalidArgumentException('Not a valid exchange rate.');
        }

        $previous = ExchangeRate::perHundred();

        if ($previous !== null && ExchangeRate::sameRate($previous, $rate)) {
            return ['changed' => false, 'repriced' => 0];
        }

        $repriced = DB::transaction(function () use ($rate, $actor): int {
            Setting::setMany([
                ExchangeRate::SETTING_RATE => $rate,
                ExchangeRate::SETTING_UPDATED_AT => now()->toIso8601String(),
                ExchangeRate::SETTING_UPDATED_BY => (string) ($actor?->getKey() ?? ''),
                ExchangeRate::SETTING_UPDATED_BY_NAME => (string) ($actor?->name ?? ''),
            ]);

            return $this->repriceUsdProducts($rate);
        });

        // Again, now that the change is committed: a request that read the
        // settings while the transaction was open may have cached the old rate.
        Setting::forgetCache();

        AdminLogger::log('exchange_rate.updated', null, [
            'previous_per_100_usd' => $previous,
            'per_100_usd' => $rate,
            'repriced_products' => $repriced,
        ]);

        return ['changed' => true, 'repriced' => $repriced];
    }

    /**
     * Recompute the dinar columns of every dollar-priced product.
     *
     * Each figure comes from the stored dollar amount and the rate — never
     * from the dinar price that was there before — and through the same
     * rounding as a single product save. Products sharing a dollar price are
     * updated together, so this is a handful of statements however large the
     * catalogue is. Dinar-priced products are not touched.
     *
     * The header's cart summary needs no flush here: its cache key carries
     * the rate, so the old entries are simply never read again.
     *
     * @return int how many products are priced in dollars
     */
    public function repriceUsdProducts(?string $perHundred = null): int
    {
        $rate = $perHundred !== null ? ExchangeRate::normalizeRate($perHundred) : ExchangeRate::perHundred();

        if ($rate === null) {
            return 0;
        }

        $usdProducts = fn () => DB::table('products')->where('price_currency', ExchangeRate::USD);

        foreach (['price_usd' => 'price', 'dealer_price_usd' => 'dealer_price', 'cost_price_usd' => 'cost_price'] as $usdColumn => $dinarColumn) {
            $amounts = $usdProducts()->whereNotNull($usdColumn)->distinct()->pluck($usdColumn);

            foreach ($amounts as $usd) {
                $usdProducts()->where($usdColumn, $usd)->update([
                    $dinarColumn => ExchangeRate::toIqd($usd, $rate),
                    'updated_at' => now(),
                ]);
            }
        }

        return $usdProducts()->count();
    }
}
