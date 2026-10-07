<?php

namespace App\Support\Pricing;

use App\Models\Setting;

/**
 * The dollar rate the shop owner sets by hand, and the one conversion built
 * on it.
 *
 * The rate is kept the way it is quoted in the market: how many dinars one
 * hundred dollars buy. Nothing here fetches a rate from outside.
 *
 * Money goes through bcmath as decimal strings, never floats, and every
 * conversion ends the same way: rounded half-up to a whole dinar. A dollar
 * price is always converted from the stored dollar amount, so changing the
 * rate ten times leaves no rounding residue behind.
 */
final class ExchangeRate
{
    public const IQD = 'IQD';

    public const USD = 'USD';

    public const CURRENCIES = [self::IQD, self::USD];

    public const SETTING_RATE = 'usd_rate_per_100';

    public const SETTING_DEFAULT_CURRENCY = 'default_price_currency';

    public const SETTING_UPDATED_AT = 'usd_rate_updated_at';

    public const SETTING_UPDATED_BY = 'usd_rate_updated_by';

    public const SETTING_UPDATED_BY_NAME = 'usd_rate_updated_by_name';

    /** Far above any real rate; only there to keep a typo out of the columns. */
    public const MAX_PER_HUNDRED = '100000000';

    private const SCALE = 8;

    /**
     * Dinars per one hundred dollars, or null while no rate has been set.
     */
    public static function perHundred(): ?string
    {
        return self::normalizeRate(Setting::getValue(self::SETTING_RATE));
    }

    public static function isConfigured(): bool
    {
        return self::perHundred() !== null;
    }

    /**
     * A rate as a two-decimal string, or null when it is not a usable rate:
     * empty, not a plain number, zero, negative or absurdly large.
     */
    public static function normalizeRate(mixed $value): ?string
    {
        $decimal = self::decimal($value, 2);

        if ($decimal === null || bccomp($decimal, '0', 2) <= 0 || bccomp($decimal, self::MAX_PER_HUNDRED, 2) > 0) {
            return null;
        }

        return $decimal;
    }

    /**
     * Dinars per single dollar, for display: "1500" or "1475.5".
     */
    public static function perDollar(?string $perHundred = null): ?string
    {
        $rate = $perHundred !== null ? self::normalizeRate($perHundred) : self::perHundred();

        if ($rate === null) {
            return null;
        }

        $perDollar = bcdiv($rate, '100', 4);

        return str_contains($perDollar, '.') ? rtrim(rtrim($perDollar, '0'), '.') : $perDollar;
    }

    /**
     * A dollar amount in whole dinars at the given rate, or at the current one.
     *
     * @throws \DomainException when there is no rate to convert with
     */
    public static function toIqd(mixed $usd, ?string $perHundred = null): string
    {
        $rate = $perHundred !== null ? self::normalizeRate($perHundred) : self::perHundred();

        if ($rate === null) {
            throw new \DomainException('No USD exchange rate is set.');
        }

        $amount = self::decimal($usd, 2);

        if ($amount === null || bccomp($amount, '0', 2) < 0) {
            throw new \DomainException('Not a valid USD amount.');
        }

        $exact = bcdiv(bcmul($amount, $rate, self::SCALE), '100', self::SCALE);

        // Half-up to the dinar: add a half, then drop the fraction.
        return bcadd($exact, '0.5', 0);
    }

    public static function sameRate(mixed $first, mixed $second): bool
    {
        $first = self::normalizeRate($first);
        $second = self::normalizeRate($second);

        return $first !== null && $second !== null && bccomp($first, $second, 2) === 0;
    }

    public static function normalizeCurrency(mixed $value): string
    {
        return strtoupper(trim((string) $value)) === self::USD ? self::USD : self::IQD;
    }

    /**
     * The currency the price field starts on for a new product. Existing
     * products carry their own and never read this.
     */
    public static function defaultCurrency(): string
    {
        return self::normalizeCurrency(Setting::getValue(self::SETTING_DEFAULT_CURRENCY, self::IQD));
    }

    /**
     * A plain non-negative decimal as a fixed-scale string, or null. Floats
     * are accepted because that is what casts and old call sites hand over,
     * but they are pinned to the scale before any arithmetic happens.
     */
    public static function decimal(mixed $value, int $scale): ?string
    {
        if (is_int($value) || is_float($value)) {
            if (! is_finite((float) $value)) {
                return null;
            }

            $value = number_format((float) $value, $scale, '.', '');
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{1,12}(\.\d{1,8})?$/', $value) !== 1) {
            return null;
        }

        // Rounded, not cut, should more decimals arrive than the scale holds.
        return bcadd(bcadd($value, '0.'.str_repeat('0', $scale).'5', $scale + 1), '0', $scale);
    }
}
