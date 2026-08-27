<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceRenderer;
use Tests\TestCase;

class InvoiceRendererLocaleTest extends TestCase
{
    private InvoiceRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new InvoiceRenderer;
    }

    public function test_explicit_lang_wins_over_everything(): void
    {
        $user = new User(['locale_preference' => 'en']);
        $order = new Order;
        $order->setRelation('user', new User(['locale_preference' => 'en']));
        app()->setLocale('ku');

        $this->assertSame('ar', $this->renderer->resolveLocale('ar', $order, $user));
    }

    public function test_unknown_explicit_lang_is_ignored(): void
    {
        $user = new User(['locale_preference' => 'ar']);
        $order = new Order;
        $order->setRelation('user', null);

        $this->assertSame('ar', $this->renderer->resolveLocale('tr', $order, $user));
    }

    public function test_order_owner_preference_beats_app_locale(): void
    {
        $user = new User(['locale_preference' => null]);
        $order = new Order;
        $order->setRelation('user', new User(['locale_preference' => 'ar']));
        app()->setLocale('ku');

        $this->assertSame('ar', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_authed_user_preference_when_order_owner_has_none(): void
    {
        $user = new User(['locale_preference' => 'ku']);
        $order = new Order;
        $order->setRelation('user', new User(['locale_preference' => null]));
        app()->setLocale('en');

        $this->assertSame('ku', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_app_locale_when_no_user_preferences(): void
    {
        $user = new User(['locale_preference' => null]);
        $order = new Order;
        $order->setRelation('user', new User(['locale_preference' => null]));
        app()->setLocale('ku');

        $this->assertSame('ku', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_falls_back_to_english_when_nothing_set(): void
    {
        $user = new User(['locale_preference' => null]);
        $order = new Order;
        $order->setRelation('user', new User(['locale_preference' => null]));
        app()->setLocale('fr');

        $this->assertSame('en', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_null_user_is_tolerated(): void
    {
        $order = new Order;
        $order->setRelation('user', new User(['locale_preference' => 'ar']));
        app()->setLocale('en');

        $this->assertSame('ar', $this->renderer->resolveLocale(null, $order, null));
    }

    /**
     * Kurdish is the reason the engine changed: DomPDF could only print
     * pre-composed presentation forms, which Unicode does not define for ڕ, ڵ
     * or ێ, so Sorani words came apart mid-word. This renders all three
     * locales end to end so a broken engine or font setup fails loudly here.
     */
    public function test_every_locale_renders_a_pdf(): void
    {
        $order = $this->fakeOrder();

        foreach (['en', 'ar', 'ku'] as $locale) {
            $pdf = $this->renderer->render($order, $locale);

            $this->assertStringStartsWith('%PDF-', $pdf, $locale.' invoice is not a PDF');
            $this->assertGreaterThan(2000, strlen($pdf), $locale.' invoice looks empty');
        }
    }

    public function test_download_sends_the_pdf_as_an_attachment(): void
    {
        $response = $this->renderer->download($this->fakeOrder(), 'ku');

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('invoice-4312-ku.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * Built in memory rather than in the database: rendering only needs the
     * relations the view reads, and this keeps the unit test off a connection.
     */
    private function fakeOrder(): Order
    {
        $user = new User(['name' => 'ئاری محەمەد', 'email' => 'ari@example.com', 'phone' => '+9647701234567']);
        $user->id = 7;

        $product = new Product([
            'name_en' => 'Brake Pad Set Front',
            'name_ar' => 'طقم فحمات فرامل أمامية',
            'name_ku' => 'کۆمەڵە پەڕەی بڕێک پێشەوە',
            'sku' => 'BP-4471',
            'brand' => 'Bosch',
        ]);
        $product->id = 11;

        $item = new OrderItem(['quantity' => 2, 'unit_price' => 25000, 'subtotal' => 50000]);
        $item->id = 1;
        $item->setRelation('product', $product);

        $order = new Order([
            'delivery_address' => 'شەقامی ٦٠ مەتری، نزیک بازاڕی نیشتمان',
            'delivery_city' => 'هەولێر',
            'delivery_phone' => '+964 770 123 4567',
        ]);
        $order->id = 4312;
        $order->subtotal_amount = 50000;
        $order->shipping_fee = 5000;
        $order->discount_amount = 2000;
        $order->grand_total = 53000;
        $order->created_at = now();
        $order->exists = true;
        $order->setRelation('user', $user);
        $order->setRelation('items', collect([$item]));

        return $order;
    }
}
