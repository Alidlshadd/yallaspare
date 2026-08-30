<?php

namespace App\Support;

use App\Mail\OperationalNotificationMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Security\WebhookSecurityService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserCommunication
{
    public static function sendOrderPlaced(User $user, Order $order): array
    {
        if (! self::shouldSendOperationalUpdates($user)) {
            return [];
        }

        return self::withUserLocale($user, function () use ($user, $order) {
            $context = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => ucfirst((string) $order->status),
                'total' => number_format((float) $order->total_amount, (int) Setting::getValue('currency_decimals', 0)).' '.(string) Setting::getValue('currency_code', 'IQD'),
                'customer_name' => $user->name,
            ];

            // Fallback strings translate via the active locale (set by withUserLocale).
            // If the admin has configured per-locale Settings rows, renderTemplate will
            // prefer those; otherwise this localised fallback is used directly.
            [$subject, $message] = self::renderTemplate($user, 'order_placed',
                __('Order Confirmation'),
                implode(PHP_EOL, [
                    __('Your order has been placed successfully.'),
                    __('Order: :ref', ['ref' => $context['order_number']]),
                    __('Status: :status', ['status' => $context['status']]),
                    __('Total: :total', ['total' => $context['total']]),
                ]),
                $context
            );

            return self::dispatch($user, 'order_placed', $subject, $message, $context);
        });
    }

    public static function sendOrderStatusUpdated(User $user, Order $order, string $from, string $to): array
    {
        if (! self::shouldSendOperationalUpdates($user)) {
            return [];
        }

        return self::withUserLocale($user, function () use ($user, $order, $from, $to) {
            $context = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'from' => ucfirst(str_replace('_', ' ', $from)),
                'to' => ucfirst(str_replace('_', ' ', $to)),
                'customer_name' => $user->name,
            ];

            $context += self::shipmentContext($order);

            [$subject, $message] = self::renderTemplate($user, 'order_status_updated',
                __('Order Status Updated'),
                implode(PHP_EOL, array_filter([
                    __('Your order status has changed.'),
                    __('Order: :ref', ['ref' => $context['order_number']]),
                    __('From: :from', ['from' => $context['from']]),
                    __('To: :to', ['to' => $context['to']]),
                    ...self::shipmentLines($order),
                ])),
                $context
            );

            return self::dispatch($user, 'order_status_updated', $subject, $message, $context);
        });
    }

    /**
     * Tell the customer the number to follow the parcel with.
     *
     * Sent when the number arrives after the shipment notice already went out,
     * which is the usual order of events: the order is marked shipped, and the
     * courier hands back a reference later.
     */
    public static function sendShipmentTracking(User $user, Order $order): array
    {
        if (! self::shouldSendOperationalUpdates($user)) {
            return [];
        }

        return self::withUserLocale($user, function () use ($user, $order) {
            $context = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $user->name,
                'action_url' => route('account.orders.show', $order),
                'action_text' => __('Track your order'),
            ] + self::shipmentContext($order);

            [$subject, $message] = self::renderTemplate($user, 'shipment_tracking',
                __('Your order is on its way'),
                implode(PHP_EOL, array_filter([
                    __('Your order has been handed to the carrier.'),
                    __('Order: :ref', ['ref' => $order->order_number]),
                    ...self::shipmentLines($order),
                ])),
                $context
            );

            return self::dispatch($user, 'shipment_tracking', $subject, $message, $context);
        });
    }

    /**
     * The carrier and number as message lines, or nothing at all when the
     * order has no tracking to report.
     *
     * @return array<int, string>
     */
    private static function shipmentLines(Order $order): array
    {
        if (! $order->hasShipmentTracking()) {
            return [];
        }

        $lines = [];

        if ($carrier = $order->carrierName()) {
            $lines[] = __('Carrier: :carrier', ['carrier' => $carrier]);
        }

        $lines[] = __('Tracking number: :number', ['number' => $order->tracking_number]);

        if ($trackingUrl = $order->trackingUrl()) {
            $lines[] = __('Track it here: :url', ['url' => $trackingUrl]);
        }

        return $lines;
    }

    /**
     * @return array<string, string>
     */
    private static function shipmentContext(Order $order): array
    {
        if (! $order->hasShipmentTracking()) {
            return [];
        }

        return array_filter([
            'carrier' => (string) $order->carrierName(),
            'tracking_number' => (string) $order->tracking_number,
            'tracking_url' => (string) $order->trackingUrl(),
        ], fn (string $value): bool => $value !== '');
    }

    public static function sendBackInStock(User $user, Product $product): array
    {
        if (! (bool) ($user->notify_stock_alerts ?? true)) {
            return [];
        }

        return self::withUserLocale($user, function () use ($user, $product) {
            $productName = $product->localizedName($user->preferredLocale());
            $context = [
                'product_id' => $product->id,
                'product_name' => $productName,
                'sku' => (string) ($product->sku ?? ''),
                'stock' => (int) $product->stock_quantity,
                'customer_name' => $user->name,
                'action_url' => route('shop.show', $product),
                'action_text' => __('View product'),
            ];

            [$subject, $message] = self::renderTemplate(
                $user,
                'back_in_stock',
                __(':product is back in stock', ['product' => $productName]),
                implode(PHP_EOL, [
                    __('The product you requested is available again.'),
                    __('Product: :product', ['product' => $productName]),
                    __('Order soon while stock is available.'),
                ]),
                $context
            );

            return self::dispatch($user, 'back_in_stock', $subject, $message, $context);
        });
    }

    /**
     * Remind a customer of the cart they filled and walked away from.
     *
     * The only message here the customer did not ask for, so it is the only
     * one gated on marketing consent rather than on a notification toggle.
     * The cart is written into the message as it stands now — a snapshot, not
     * a reference — so a queued reminder cannot arrive describing a cart that
     * has since changed.
     */
    public static function sendAbandonedCart(User $user, Cart $cart): array
    {
        if (! (bool) ($user->marketing_consent ?? false)) {
            return [];
        }

        return self::withUserLocale($user, function () use ($user, $cart) {
            $rows = self::cartRows($cart, $user);

            if ($rows === []) {
                return [];
            }

            $total = array_sum(array_column($rows, 'amount'));
            $context = [
                'cart_id' => $cart->getKey(),
                'cart_rows' => $rows,
                'cart_total' => self::formatMoney($total),
                'item_count' => count($rows),
                'customer_name' => $user->name,
                // The HTML mail lists the cart as rows of its own, so it takes
                // this line rather than the message body, which repeats them
                // for SMS and for plain-text readers.
                'intro' => __('The parts you picked are still waiting in your cart.'),
                'action_url' => route('cart.index'),
                'action_text' => __('Return to your cart'),
            ];

            $lines = array_map(
                static fn (array $row): string => '- '.$row['name'].' × '.$row['quantity'].' — '.$row['subtotal'],
                $rows
            );

            [$subject, $message] = self::renderTemplate(
                $user,
                'cart_reminder',
                __('You left something in your cart'),
                implode(PHP_EOL, [
                    __('The parts you picked are still waiting in your cart.'),
                    ...$lines,
                    __('Total: :total', ['total' => $context['cart_total']]),
                    __('Finish whenever you are ready — stock is not held.'),
                ]),
                $context
            );

            return self::dispatch($user, 'cart_reminder', $subject, $message, $context);
        });
    }

    /**
     * The cart as message lines: what it holds, at the price this customer
     * would actually pay.
     *
     * @return array<int, array{name: string, sku: string, quantity: int, amount: float, subtotal: string}>
     */
    private static function cartRows(Cart $cart, User $user): array
    {
        $cart->loadMissing('items.product');
        $rows = [];

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            $quantity = max(1, (int) $item->quantity);
            $amount = (float) $product->priceFor($user) * $quantity;

            $rows[] = [
                'name' => $product->localizedName($user->preferredLocale()),
                'sku' => (string) ($product->sku ?? ''),
                'quantity' => $quantity,
                'amount' => $amount,
                'subtotal' => self::formatMoney($amount),
            ];
        }

        return $rows;
    }

    private static function formatMoney(float $amount): string
    {
        return number_format($amount, (int) Setting::getValue('currency_decimals', 0))
            .' '.(string) Setting::getValue('currency_code', 'IQD');
    }

    /**
     * Run the given callback with Laravel's active locale temporarily set to
     * the user's preferred locale. Restores the previous locale even if the
     * callback throws. Used so __() and view rendering inside the callback
     * speak to the user in their language.
     */
    private static function withUserLocale(User $user, \Closure $callback): array
    {
        $previous = app()->getLocale();
        $target = method_exists($user, 'preferredLocale') ? $user->preferredLocale() : $previous;

        try {
            app()->setLocale($target);

            return $callback();
        } finally {
            app()->setLocale($previous);
        }
    }

    private static function shouldSendOperationalUpdates(User $user): bool
    {
        return (bool) ($user->notify_order_updates ?? true);
    }

    private static function dispatch(User $user, string $type, string $subject, string $message, array $context = []): array
    {
        $sentVia = [];

        $localizedContext = $context + [
            'type' => $type,
            'locale' => method_exists($user, 'preferredLocale') ? $user->preferredLocale() : app()->getLocale(),
        ];

        if (($user->email_notifications ?? true) && $user->email && self::sendEmail((string) $user->email, $subject, $message, $localizedContext)) {
            $sentVia[] = 'email';
        }

        if ($user->sms_notifications && $user->phone) {
            if (self::sendWebhook('sms_provider_webhook_url', $user->phone, $message, $context + ['type' => $type])) {
                $sentVia[] = 'sms';
            }
        }

        if ((bool) config('services.otpiq.whatsapp.user_visible', false) && $user->whatsapp_notifications && $user->phone) {
            if (self::sendWebhook('whatsapp_provider_webhook_url', $user->phone, $message, $context + ['type' => $type])) {
                $sentVia[] = 'whatsapp';
            }
        }

        return $sentVia;
    }

    private static function sendEmail(string $email, string $subject, string $message, array $context = []): bool
    {
        $mailer = self::resolveMailer();
        $context['locale'] = in_array((string) ($context['locale'] ?? app()->getLocale()), ['en', 'ar', 'ku'], true)
            ? (string) ($context['locale'] ?? app()->getLocale())
            : 'en';

        try {
            Mail::mailer($mailer)
                ->to($email)
                ->queue(new OperationalNotificationMail($subject, $message, $context));
        } catch (\Throwable $exception) {
            Log::error('Email notification failed', $context + [
                'mailer' => $mailer,
                'recipient_hash' => self::recipientHash($email),
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        Log::info('Email notification dispatched', $context + [
            'mailer' => $mailer,
            'recipient_hash' => self::recipientHash($email),
            'subject' => $subject,
            'queued' => true,
        ]);

        return true;
    }

    private static function sendWebhook(string $settingKey, string $recipient, string $message, array $context): bool
    {
        $url = trim((string) Setting::getValue($settingKey, ''));

        if ($url === '') {
            Log::info(Str::before($settingKey, '_provider').' notification queued', $context + [
                'recipient_hash' => self::recipientHash($recipient),
                'transport' => 'log',
            ]);

            return true;
        }

        if (! app(WebhookSecurityService::class)->isAllowed($url)) {
            Log::warning('Notification webhook blocked by SSRF policy', $context + [
                'setting' => $settingKey,
                'recipient_hash' => self::recipientHash($recipient),
            ]);

            return false;
        }

        try {
            Http::timeout(8)->withoutRedirecting()->post($url, [
                'recipient' => $recipient,
                'message' => $message,
                'context' => $context,
            ])->throw();

            Log::info('Notification webhook dispatched', $context + [
                'setting' => $settingKey,
                'recipient_hash' => self::recipientHash($recipient),
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Notification webhook failed', $context + [
                'setting' => $settingKey,
                'recipient_hash' => self::recipientHash($recipient),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private static function renderTemplate(User $user, string $type, string $fallbackSubject, string $fallbackBody, array $context): array
    {
        $locale = in_array((string) ($user->locale_preference ?? app()->getLocale()), ['en', 'ar', 'ku'], true)
            ? (string) ($user->locale_preference ?? app()->getLocale())
            : 'en';

        $subject = (string) Setting::getValue("notification_{$type}_{$locale}_subject", $fallbackSubject);
        $body = (string) Setting::getValue("notification_{$type}_{$locale}_body", $fallbackBody);
        $subject = trim($subject) !== '' ? $subject : $fallbackSubject;
        $body = trim($body) !== '' ? $body : $fallbackBody;

        foreach ($context as $key => $value) {
            // Only values that have a sensible text form are substitutable.
            // Structured context — a cart's rows, say — is for the message
            // template to lay out, not for a placeholder to stringify.
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $subject = str_replace('{{'.$key.'}}', (string) $value, $subject);
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }

        return [$subject, $body];
    }

    private static function resolveMailer(): string
    {
        $defaultMailer = (string) config('mail.default', 'log');
        $smtpUsername = (string) config('mail.mailers.smtp.username', '');
        $smtpPassword = (string) config('mail.mailers.smtp.password', '');
        $smtpHost = (string) config('mail.mailers.smtp.host', '');

        if ($defaultMailer === 'smtp' && (
            $smtpUsername === ''
            || $smtpPassword === ''
            || $smtpHost === ''
            || str_contains($smtpUsername, 'your-gmail-address')
            || str_contains($smtpPassword, 'your-google-app-password')
        )) {
            return 'log';
        }

        return $defaultMailer;
    }

    private static function recipientHash(string $recipient): string
    {
        return hash('sha256', strtolower(trim($recipient)));
    }
}
