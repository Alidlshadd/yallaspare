<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * What the shop knows about the companies it hands parcels to.
 *
 * The carrier on an order is free text, because the operator's real courier is
 * often a local company nobody configured. Anything that matches a configured
 * carrier gains a tracking link; anything else still shows its number, which is
 * what support asks the customer for anyway.
 */
final class Carriers
{
    /**
     * Names to suggest in the carrier field, keyed by the slug they resolve to.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::all() as $slug => $carrier) {
            $options[$slug] = (string) ($carrier['name'] ?? $slug);
        }

        return $options;
    }

    /**
     * The carrier's configured name, or the operator's own words when the
     * carrier is not one this application knows.
     */
    public static function displayName(?string $carrier): ?string
    {
        $carrier = trim((string) $carrier);

        if ($carrier === '') {
            return null;
        }

        return self::find($carrier)['name'] ?? $carrier;
    }

    /**
     * A link the customer can follow, or null when the carrier publishes no
     * tracking page — which is the normal case for an own driver.
     */
    public static function trackingUrl(?string $carrier, ?string $trackingNumber): ?string
    {
        $trackingNumber = trim((string) $trackingNumber);

        if ($trackingNumber === '') {
            return null;
        }

        $template = self::find(trim((string) $carrier))['tracking_url'] ?? null;

        if (! is_string($template) || $template === '') {
            return null;
        }

        // The template is ours, the number is not: encode it so a tracking
        // number can never add a parameter of its own to the URL.
        return str_replace(':number', rawurlencode($trackingNumber), $template);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function find(string $carrier): ?array
    {
        if ($carrier === '') {
            return null;
        }

        $carriers = self::all();
        $slug = Str::slug($carrier);

        if (isset($carriers[$slug]) && is_array($carriers[$slug])) {
            return $carriers[$slug];
        }

        // An operator who typed the display name rather than the slug still
        // gets their link.
        foreach ($carriers as $candidate) {
            if (is_array($candidate) && Str::slug((string) ($candidate['name'] ?? '')) === $slug) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function all(): array
    {
        return (array) config('shipping.carriers', []);
    }
}
