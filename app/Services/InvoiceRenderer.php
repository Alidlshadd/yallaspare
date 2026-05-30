<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

final class InvoiceRenderer
{
    private const ALLOWED_LOCALES = ['en', 'ar', 'ku'];

    /**
     * Resolve the locale to use for an invoice PDF.
     *
     * Precedence (first hit wins):
     *   1. $explicit (?lang= query value), if in the allow-list
     *   2. Order owner's locale_preference
     *   3. Authed user's locale_preference (covers admin/dealer download of someone else's order)
     *   4. app()->getLocale() (Accept-Language middleware on mobile, session on web)
     *   5. 'en'
     */
    public function resolveLocale(?string $explicit, Order $order, ?User $user): string
    {
        $candidates = [
            $explicit,
            $order->user?->locale_preference,
            $user?->locale_preference,
            app()->getLocale(),
        ];

        foreach ($candidates as $candidate) {
            $normalized = strtolower((string) $candidate);
            if (in_array($normalized, self::ALLOWED_LOCALES, true)) {
                return $normalized;
            }
        }

        return 'en';
    }
}
