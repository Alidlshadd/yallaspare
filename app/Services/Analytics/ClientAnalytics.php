<?php

namespace App\Services\Analytics;

use App\Support\BotDetector;

/**
 * What the browser reports to GA4 and the Meta pixel.
 *
 * AnalyticsRecorder writes the shop's own history to our tables; this is the
 * other half — the events an ad platform has to see in the visitor's browser
 * before a campaign can be measured or an audience rebuilt. The two are
 * deliberately independent: the first-party trackers dedupe and rate-limit to
 * keep the tables honest, while an ad platform expects every occurrence.
 *
 * Events are collected during a request and rendered once, by the storefront
 * layout, at the end of the response. They go through the session so that an
 * action which ends in a redirect — adding to the cart does — still reports
 * on the page the visitor lands on.
 */
class ClientAnalytics
{
    public const SESSION_KEY = 'analytics.pending_events';

    /**
     * Purchases already reported in this session, so a refreshed confirmation
     * page cannot count the same order twice.
     */
    public const REPORTED_PURCHASES_KEY = 'analytics.reported_purchases';

    /**
     * Nothing is unbounded that a visitor can drive: a session that somehow
     * collects events without ever rendering them stops growing here.
     */
    private const MAX_PENDING = 20;

    /**
     * Canonical event name => [GA4 name, Meta pixel name].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const EVENT_NAMES = [
        'view_item' => ['view_item', 'ViewContent'],
        'add_to_cart' => ['add_to_cart', 'AddToCart'],
        'begin_checkout' => ['begin_checkout', 'InitiateCheckout'],
        'search' => ['search', 'Search'],
        'purchase' => ['purchase', 'Purchase'],
    ];

    public function ga4MeasurementId(): string
    {
        return trim((string) config('analytics.ga4.measurement_id', ''));
    }

    public function metaPixelId(): string
    {
        return trim((string) config('analytics.meta.pixel_id', ''));
    }

    public function ga4Enabled(): bool
    {
        return $this->ga4MeasurementId() !== '';
    }

    public function metaEnabled(): bool
    {
        return $this->metaPixelId() !== '';
    }

    public function enabled(): bool
    {
        return $this->ga4Enabled() || $this->metaEnabled();
    }

    /**
     * Note something worth reporting. Silently does nothing when no tag is
     * configured, so call sites never have to ask.
     *
     * @param  array{currency?: string, value?: float, transaction_id?: string, search_term?: string, items?: array<int, array{id: int|string, name?: string, price?: float, quantity?: int}>}  $payload
     */
    public function record(string $event, array $payload = []): void
    {
        if (! $this->enabled() || ! isset(self::EVENT_NAMES[$event])) {
            return;
        }

        $request = request();

        if (BotDetector::isBot($request?->userAgent()) || ! $request?->hasSession()) {
            return;
        }

        $pending = $this->pendingFromSession();

        if (count($pending) >= self::MAX_PENDING) {
            return;
        }

        $pending[] = ['event' => $event, 'payload' => $payload];

        session()->put(self::SESSION_KEY, $pending);
    }

    /**
     * Report a completed order once. Returns false when this session has
     * already reported it — a reloaded confirmation page, most often.
     */
    public function recordPurchaseOnce(string $orderReference, array $payload): bool
    {
        if (! $this->enabled() || $orderReference === '' || ! request()?->hasSession()) {
            return false;
        }

        $reported = (array) session()->get(self::REPORTED_PURCHASES_KEY, []);

        if (in_array($orderReference, $reported, true)) {
            return false;
        }

        $this->record('purchase', $payload + ['transaction_id' => $orderReference]);

        $reported[] = $orderReference;
        session()->put(self::REPORTED_PURCHASES_KEY, array_slice($reported, -20));

        return true;
    }

    /**
     * Take everything collected so far, shaped the way each tag expects it.
     * Reading empties the queue: an event is reported once.
     *
     * @return array<int, array{ga4: array{name: string, params: array<string, mixed>}, meta: array{name: string, params: array<string, mixed>, event_id: string|null}}>
     */
    public function flushForBrowser(): array
    {
        $pending = $this->pendingFromSession();

        if ($pending === []) {
            return [];
        }

        session()->forget(self::SESSION_KEY);

        $rendered = [];

        foreach ($pending as $entry) {
            $event = (string) ($entry['event'] ?? '');
            $payload = (array) ($entry['payload'] ?? []);

            if (! isset(self::EVENT_NAMES[$event])) {
                continue;
            }

            [$ga4Name, $metaName] = self::EVENT_NAMES[$event];

            $rendered[] = [
                'ga4' => ['name' => $ga4Name, 'params' => $this->ga4Parameters($payload)],
                'meta' => [
                    'name' => $metaName,
                    'params' => $this->metaParameters($payload),
                    // Lets a future server-side Conversions API call be matched
                    // to this browser event instead of counted beside it.
                    'event_id' => isset($payload['transaction_id']) ? (string) $payload['transaction_id'] : null,
                ],
            ];
        }

        return $rendered;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function ga4Parameters(array $payload): array
    {
        $parameters = array_filter([
            'currency' => isset($payload['currency']) ? (string) $payload['currency'] : null,
            'value' => isset($payload['value']) ? round((float) $payload['value'], 2) : null,
            'transaction_id' => isset($payload['transaction_id']) ? (string) $payload['transaction_id'] : null,
            'search_term' => isset($payload['search_term']) ? (string) $payload['search_term'] : null,
        ], fn ($value): bool => $value !== null);

        $items = [];

        foreach ((array) ($payload['items'] ?? []) as $item) {
            $items[] = array_filter([
                'item_id' => (string) ($item['id'] ?? ''),
                'item_name' => isset($item['name']) ? (string) $item['name'] : null,
                'price' => isset($item['price']) ? round((float) $item['price'], 2) : null,
                'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : null,
            ], fn ($value): bool => $value !== null && $value !== '');
        }

        if ($items !== []) {
            $parameters['items'] = $items;
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function metaParameters(array $payload): array
    {
        $parameters = array_filter([
            'currency' => isset($payload['currency']) ? (string) $payload['currency'] : null,
            'value' => isset($payload['value']) ? round((float) $payload['value'], 2) : null,
            'search_string' => isset($payload['search_term']) ? (string) $payload['search_term'] : null,
        ], fn ($value): bool => $value !== null);

        $contents = [];

        foreach ((array) ($payload['items'] ?? []) as $item) {
            $contents[] = array_filter([
                'id' => (string) ($item['id'] ?? ''),
                'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : null,
                'item_price' => isset($item['price']) ? round((float) $item['price'], 2) : null,
            ], fn ($value): bool => $value !== null && $value !== '');
        }

        if ($contents !== []) {
            $parameters['contents'] = $contents;
            $parameters['content_ids'] = array_column($contents, 'id');
            $parameters['content_type'] = 'product';
        }

        return $parameters;
    }

    /**
     * @return array<int, array{event: string, payload: array<string, mixed>}>
     */
    private function pendingFromSession(): array
    {
        $pending = session()->get(self::SESSION_KEY, []);

        return is_array($pending) ? array_values($pending) : [];
    }
}
