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

    /**
     * The rate the old dinar prices were set at. Used once, to turn those
     * prices into dollars; it is not the selling rate and does not move
     * with it.
     */
    public const SETTING_CONVERSION_RATE = 'usd_conversion_rate_per_100';

    public const DEFAULT_CONVERSION_RATE = '150000.00';

    /**
     * Bumped on every change to a selling price, so anything cached per
     * price (the header's cart total) is dropped with it.
     */
    public const SETTING_PRICE_VERSION = 'price_version';

    /**
     * Decimals a dollar price is kept to. Cents are not enough: a price
     * chosen in dinars ("17,000") has to come back as exactly that many
     * dinars after being stored in dollars, and at four decimals the error
     * is under a tenth of a dinar at any realistic rate.
     */
    public const USD_SCALE = 4;

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

        $amount = self::decimal($usd, self::USD_SCALE);

        if ($amount === null || bccomp($amount, '0', self::USD_SCALE) < 0) {
            throw new \DomainException('Not a valid USD amount.');
        }

        $exact = bcdiv(bcmul($amount, $rate, self::SCALE), '100', self::SCALE);

        // Half-up to the dinar: add a half, then drop the fraction.
        return bcadd($exact, '0.5', 0);
    }

    /**
     * A dinar amount in dollars at the given rate, to four decimals.
     *
     * Plain division, rounded half-up. This is what the one-off conversion
     * of old dinar prices uses: 15,000 at 1,500 is 10.
     *
     * @throws \DomainException when the rate or the amount is unusable
     */
    public static function fromIqd(mixed $iqd, string $perHundred): string
    {
        $rate = self::normalizeRate($perHundred);
        $amount = self::decimal($iqd, 2);

        if ($rate === null || $amount === null) {
            throw new \DomainException('Not a valid IQD amount or rate.');
        }

        $exact = bcdiv(bcmul($amount, '100', self::SCALE), $rate, self::SCALE);

        return self::decimal($exact, self::USD_SCALE) ?? '0.0000';
    }

    /**
     * The dollar price that sells for exactly this many dinars at the given
     * rate, or at the current one.
     *
     * Division alone can land a hair to one side, so the result is checked
     * by converting it back and, if it misses, the nearest neighbours are
     * tried. `exact` says whether a dollar price was found that comes back
     * to the dinar figure asked for.
     *
     * @return array{usd: string, iqd: string, exact: bool}
     */
    public static function usdForTargetIqd(mixed $targetIqd, ?string $perHundred = null): array
    {
        $rate = $perHundred !== null ? self::normalizeRate($perHundred) : self::perHundred();
        $target = self::decimal($targetIqd, 0);

        if ($rate === null || $target === null) {
            throw new \DomainException('Not a valid IQD target or rate.');
        }

        $usd = self::fromIqd($target, $rate);
        $step = '0.'.str_repeat('0', self::USD_SCALE - 1).'1';

        foreach ([$usd, bcadd($usd, $step, self::USD_SCALE), bcsub($usd, $step, self::USD_SCALE)] as $candidate) {
            if (bccomp($candidate, '0', self::USD_SCALE) >= 0 && self::toIqd($candidate, $rate) === $target) {
                return ['usd' => $candidate, 'iqd' => $target, 'exact' => true];
            }
        }

        return ['usd' => $usd, 'iqd' => self::toIqd($usd, $rate), 'exact' => false];
    }

    /**
     * A dollar amount for people: two decimals when that is all there is
     * ("10.00"), more only when the price really has them ("9.4118").
     */
    public static function formatUsd(mixed $usd): string
    {
        $amount = self::decimal($usd, self::USD_SCALE);

        if ($amount === null) {
            return '';
        }

        [$whole, $fraction] = explode('.', $amount);
        $fraction = str_pad(rtrim($fraction, '0'), 2, '0');

        return number_format((float) $whole).'.'.$fraction;
    }

    /**
     * The rate old dinar prices are converted at. A setting of its own, so
     * moving the selling rate never changes what a conversion would do.
     */
    public static function conversionRate(): string
    {
        return self::normalizeRate(Setting::getValue(self::SETTING_CONVERSION_RATE)) ?? self::DEFAULT_CONVERSION_RATE;
    }

    public static function priceVersion(): string
    {
        return (string) Setting::getValue(self::SETTING_PRICE_VERSION, '0');
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
