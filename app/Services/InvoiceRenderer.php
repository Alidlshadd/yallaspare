<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

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

    /**
     * Build the invoice PDF for an order in the given locale and return the raw bytes.
     *
     * The provided locale must already be normalized (use resolveLocale()).
     * Temporarily switches app locale for view rendering and restores it after.
     */
    public function render(Order $order, string $locale): string
    {
        $order->loadMissing([
            'user:id,name,email,phone,locale_preference',
            'items' => fn ($query) => $query
                ->select(['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'])
                ->with(['product:id,name_en,name_ar,name_ku,sku,brand']),
        ]);

        $subtotal = (float) ($order->subtotal_amount ?: $order->items->sum('subtotal'));
        $shipping = (float) $order->shipping_fee;
        $discount = (float) $order->discount_amount;
        $grandTotal = (float) ($order->grand_total ?: ($subtotal + $shipping - $discount));
        $year = optional($order->created_at)->format('Y') ?: now()->format('Y');
        $isRtl = in_array($locale, ['ar', 'ku'], true);

        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            $html = view('admin.orders.invoice', [
                'order' => $order,
                'invoiceNumber' => 'INV-'.$year.'-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                'currency' => 'IQD',
                'logoPath' => Branding::invoiceLogoPath(),
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'discount' => $discount,
                'grandTotal' => $grandTotal,
                'locale' => $locale,
                'isRtl' => $isRtl,
            ])->render();
        } finally {
            app()->setLocale($previousLocale);
        }

        $mpdf = $this->makeEngine($isRtl);
        $mpdf->WriteHTML($html);

        return (string) $mpdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * Render the invoice as a download response.
     */
    public function download(Order $order, string $locale): Response
    {
        return new Response($this->render($order, $locale), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$order->id.'-'.$locale.'.pdf"',
        ]);
    }

    /**
     * mPDF rather than DomPDF because DomPDF cannot shape Arabic script: it can
     * only print pre-composed presentation forms, and Unicode defines none for
     * ڕ, ڵ or ێ. Those three letters are everywhere in Sorani, so every Kurdish
     * invoice came out with words broken apart mid-join. mPDF applies the
     * font's own OpenType joining, which handles them and Arabic correctly.
     *
     * autoScriptToLang/autoLangToFont stay off so the invoice keeps one font in
     * every locale — DejaVu Sans shapes all three scripts here.
     */
    private function makeEngine(bool $isRtl): Mpdf
    {
        $tempDir = storage_path('framework/cache/mpdf');
        File::ensureDirectoryExists($tempDir);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'default_font' => 'dejavusans',
            'useOTL' => 0xFF,
            'useKashida' => 0,
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
        ]);

        if ($isRtl) {
            $mpdf->SetDirectionality('rtl');
        }

        return $mpdf;
    }
}
