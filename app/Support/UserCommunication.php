<?php

namespace App\Support;

use App\Mail\OperationalNotificationMail;
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
                'total' => number_format((float) $order->total_amount, (int) Setting::getValue('currency_decimals', 0)) . ' ' . (string) Setting::getValue('currency_code', 'IQD'),
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

            [$subject, $message] = self::renderTemplate($user, 'order_status_updated',
                __('Order Status Updated'),
                implode(PHP_EOL, [
                    __('Your order status has changed.'),
                    __('Order: :ref', ['ref' => $context['order_number']]),
                    __('From: :from', ['from' => $context['from']]),
                    __('To: :to', ['to' => $context['to']]),
                ]),
                $context
            );

            return self::dispatch($user, 'order_status_updated', $subject, $message, $context);
        });
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

        if ($user->whatsapp_notifications && $user->phone) {
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
            Log::info(Str::before($settingKey, '_provider') . ' notification queued', $context + [
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
            $subject = str_replace('{{' . $key . '}}', (string) $value, $subject);
            $body = str_replace('{{' . $key . '}}', (string) $value, $body);
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
