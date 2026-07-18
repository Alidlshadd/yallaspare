<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class OperationalNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Operational customer emails are queued so checkout and order updates
     * do not depend on real-time SMTP availability.
     */
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $bodyText,
        public readonly array $context = [],
    ) {
        $this->onQueue('mail');
    }

    public function build(): self
    {
        $locale = in_array((string) ($this->context['locale'] ?? app()->getLocale()), ['en', 'ar', 'ku'], true)
            ? (string) ($this->context['locale'] ?? app()->getLocale())
            : 'en';

        if (in_array($locale, ['en', 'ar', 'ku'], true)) {
            App::setLocale($locale);
        }

        $viewData = $this->emailViewData();

        return $this
            ->subject($this->subjectLine)
            ->view($viewData['view'], $viewData)
            ->text('emails.text.generic', $viewData);
    }

    private function emailViewData(): array
    {
        $type = (string) ($this->context['type'] ?? 'operational');
        $locale = in_array((string) ($this->context['locale'] ?? app()->getLocale()), ['en', 'ar', 'ku'], true)
            ? (string) ($this->context['locale'] ?? app()->getLocale())
            : 'en';

        $data = [
            'view' => 'emails.operational.notification',
            'locale' => $locale,
            'subjectLine' => $this->subjectLine,
            'title' => $this->subjectLine,
            'bodyText' => $this->bodyText,
            'preheader' => Str::limit(strip_tags($this->bodyText), 120),
            'eyebrow' => __('YallaSpare notification'),
            'metaItems' => $this->metaItemsFromContext(),
            'actionUrl' => (string) ($this->context['action_url'] ?? url('/')),
            'actionText' => (string) ($this->context['action_text'] ?? __('Open YallaSpare')),
        ];

        if (in_array($type, ['order_placed', 'order_status_updated'], true)) {
            return array_merge($data, $this->buildOrderViewData($type));
        }

        if ($type === 'back_in_stock') {
            $data['eyebrow'] = __('Back in stock');
        } elseif (str_contains($type, 'stock') || str_contains($type, 'inventory')) {
            $data['view'] = 'emails.inventory.low-stock-alert';
            $data['eyebrow'] = __('Inventory alert');
        } elseif (str_contains($type, 'dealer')) {
            $data['view'] = 'emails.dealer.notification';
            $data['eyebrow'] = __('Dealer notification');
        } elseif (str_contains($type, 'security') || str_contains($type, 'activity')) {
            $data['view'] = 'emails.admin.security-alert';
            $data['eyebrow'] = __('Security alert');
        }

        return $data;
    }

    private function buildOrderViewData(string $type): array
    {
        $order = $this->resolveOrder();
        $toStatus = strtolower((string) ($this->context['to'] ?? $this->context['status'] ?? ''));
        $isPlaced = $type === 'order_placed';
        $isShipped = str_contains($toStatus, 'shipped');
        $isDelivered = str_contains($toStatus, 'delivered');
        $isRefunded = str_contains($toStatus, 'refunded');

        $title = match (true) {
            $isPlaced => __('Order confirmation'),
            $isShipped => __('Your order is on the way'),
            $isDelivered => __('Your order was delivered'),
            $isRefunded => __('Refund approved'),
            default => __('Order status updated'),
        };

        $eyebrow = match (true) {
            $isPlaced => __('Order confirmation'),
            $isShipped => __('Shipping update'),
            $isDelivered => __('Delivered order'),
            $isRefunded => __('Refund approved'),
            default => __('Order update'),
        };

        return [
            'view' => 'emails.orders.status',
            'title' => $title,
            'eyebrow' => $eyebrow,
            'intro' => $this->bodyText,
            'bodyText' => $this->bodyText,
            'preheader' => Str::limit($this->bodyText, 120),
            'metaItems' => $this->orderMetaItems($order),
            'orderRows' => $this->orderRows($order),
            'totals' => $this->orderTotals($order),
            'shippingAddress' => $this->shippingAddress($order),
            'actionUrl' => $order ? route('account.orders.show', $order) : url('/account/orders'),
            'actionText' => $isShipped ? __('Track order') : __('View order'),
        ];
    }

    private function resolveOrder(): ?Order
    {
        $orderId = $this->context['order_id'] ?? null;

        if (! $orderId) {
            return null;
        }

        return Order::query()
            ->with(['items.product'])
            ->find($orderId);
    }

    private function metaItemsFromContext(): array
    {
        return collect($this->context)
            ->except(['type', 'locale', 'order_id', 'broadcast_id', 'purpose', 'action_url', 'action_text'])
            ->take(6)
            ->map(fn ($value, $key) => [
                'label' => __(Str::headline((string) $key)),
                'value' => is_scalar($value) ? (string) $value : json_encode($value),
            ])
            ->values()
            ->all();
    }

    private function orderMetaItems(?Order $order): array
    {
        return [
            ['label' => __('Order'), 'value' => (string) ($order?->order_number ?? $this->context['order_number'] ?? '')],
            ['label' => __('Status'), 'value' => (string) ($this->context['to'] ?? $this->context['status'] ?? $order?->status ?? '')],
            ['label' => __('Total'), 'value' => $order ? $this->formatMoney((float) ($order->grand_total ?: $order->total_amount)) : (string) ($this->context['total'] ?? '')],
        ];
    }

    private function orderRows(?Order $order): array
    {
        if (! $order) {
            return [];
        }

        return $order->items->map(fn ($item) => [
            'name' => (string) ($item->product?->name ?? __('Product')),
            'sku' => (string) ($item->product?->sku ?? ''),
            'quantity' => (int) $item->quantity,
            'subtotal' => $this->formatMoney((float) $item->subtotal),
        ])->values()->all();
    }

    private function orderTotals(?Order $order): array
    {
        if (! $order) {
            return [];
        }

        return [
            ['label' => __('Subtotal'), 'value' => $this->formatMoney((float) $order->subtotal_amount)],
            ['label' => __('Shipping'), 'value' => $this->formatMoney((float) $order->shipping_fee)],
            ['label' => __('Discount'), 'value' => $this->formatMoney((float) $order->discount_amount)],
            ['label' => __('Total'), 'value' => $this->formatMoney((float) ($order->grand_total ?: $order->total_amount))],
        ];
    }

    private function shippingAddress(?Order $order): string
    {
        if (! $order) {
            return '';
        }

        return trim(implode(', ', array_filter([
            (string) $order->delivery_address,
            (string) $order->delivery_city,
            (string) $order->delivery_phone,
        ])));
    }

    private function formatMoney(float $amount): string
    {
        $decimals = (int) Setting::getValue('currency_decimals', 0);
        $code = (string) Setting::getValue('currency_code', 'IQD');

        return number_format($amount, $decimals) . ' ' . $code;
    }
}
