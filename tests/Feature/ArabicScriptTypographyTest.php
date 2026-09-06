<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Arabic and Kurdish letters join, and both places that forgot it.
 *
 * The invoice was set in DejaVu Sans, which carries every Sorani codepoint but
 * not the mark positioning: ڕ and ڵ came out with the ring and the small V
 * detached and drifting, and the word split at the join — "بازاڕی" and
 * "گەڕاندنەوە" broke in half on every Kurdish invoice. The storefront had the
 * same class of fault from the other direction: the tracking utilities that
 * tighten an English heading and space out an eyebrow label pulled the joins
 * apart on ar and ku pages.
 *
 * Neither shows up in a translation check — the words are right, they are just
 * drawn wrong — so they are pinned here.
 */
class ArabicScriptTypographyTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_arabic_script_invoice_is_set_in_a_font_that_joins_its_letters(): void
    {
        $order = $this->order();

        foreach (['ku', 'ar'] as $locale) {
            $html = $this->invoiceHtml($order, $locale);

            $this->assertStringContainsString(
                'xbriyaz',
                $html,
                "The {$locale} invoice fell back to a font that draws ڕ and ڵ detached."
            );
        }
    }

    public function test_the_english_invoice_keeps_its_latin_font(): void
    {
        $html = $this->invoiceHtml($this->order(), 'en');

        $this->assertStringNotContainsString('xbriyaz', $html);
        $this->assertStringContainsString('DejaVu Sans', $html);
    }

    public function test_every_locale_still_produces_a_pdf(): void
    {
        $order = $this->order();

        foreach (['en', 'ar', 'ku'] as $locale) {
            $pdf = app(InvoiceRenderer::class)->render($order->fresh(), $locale);

            // A font mPDF cannot resolve throws rather than returning a short
            // file, but the header check costs nothing and names the failure.
            $this->assertStringStartsWith('%PDF', $pdf, "The {$locale} invoice is not a PDF.");
            $this->assertGreaterThan(10000, strlen($pdf), "The {$locale} invoice came out suspiciously small.");
        }
    }

    public function test_a_right_to_left_page_does_not_space_out_its_joins(): void
    {
        $css = (string) file_get_contents(base_path('resources/css/app.css'));

        $this->assertMatchesRegularExpression(
            "/\[dir='rtl'\]\s*:not\(\[dir='ltr'\][^)]*\)\s*\{\s*letter-spacing:\s*normal/",
            $css,
            'Nothing stops a tracking utility from breaking Arabic and Kurdish joins.'
        );
    }

    public function test_a_latin_run_inside_a_right_to_left_page_keeps_its_tracking(): void
    {
        // The exception is what makes the rule safe to apply broadly: SKUs, OEM
        // numbers, displacements and prices are marked dir="ltr" and are meant
        // to read as Latin.
        $css = (string) file_get_contents(base_path('resources/css/app.css'));

        $this->assertStringContainsString(":not([dir='ltr'], [dir='ltr'] *)", $css);
    }

    private function invoiceHtml(Order $order, string $locale): string
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return view('admin.orders.invoice', [
                'order' => $order->fresh(['user', 'items.product']),
                'invoiceNumber' => 'INV-2026-00001',
                'currency' => 'IQD',
                'logoPath' => null,
                'subtotal' => 25000.0,
                'shipping' => 5000.0,
                'discount' => 0.0,
                'grandTotal' => 30000.0,
                'locale' => $locale,
                'isRtl' => in_array($locale, ['ar', 'ku'], true),
            ])->render();
        } finally {
            app()->setLocale($previous);
        }
    }

    private function order(): Order
    {
        Category::factory()->create(['id' => 1, 'name_en' => 'Filters', 'slug' => 'filters']);

        $user = User::factory()->create(['name' => 'ئەحمەد محەمەد', 'locale_preference' => 'ku']);
        $product = Product::factory()->create(['name_en' => 'Oil Filter', 'is_active' => true]);

        $order = Order::query()->forceCreate([
            'user_id' => $user->id,
            'order_number' => 'TEST-'.Str::upper(Str::random(8)),
            'total_amount' => 25000,
            'subtotal_amount' => 25000,
            'shipping_fee' => 5000,
            'discount_amount' => 0,
            'grand_total' => 30000,
            'status' => Order::STATUS_PROCESSING,
            'payment_method' => 'cash_on_delivery',
            // The letters that were breaking: ڕ in بازاڕی, ێ in هەولێر.
            'delivery_address' => 'شەقامی ٦٠ مەتری، نزیک بازاڕی گەورە',
            'delivery_city' => 'هەولێر',
            'delivery_phone' => '+964 770 000 0000',
        ]);

        OrderItem::query()->forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'فلتەری ڕۆن بۆ ئۆتۆمبێلی گەورە',
            'product_sku' => 'SY-1721840025',
            'quantity' => 2,
            'unit_price' => 12500,
            'subtotal' => 25000,
        ]);

        return $order;
    }
}
