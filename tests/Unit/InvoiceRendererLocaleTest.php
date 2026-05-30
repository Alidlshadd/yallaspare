<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\InvoiceRenderer;
use Tests\TestCase;

class InvoiceRendererLocaleTest extends TestCase
{
    private InvoiceRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new InvoiceRenderer();
    }

    public function test_explicit_lang_wins_over_everything(): void
    {
        $user = new User(['locale_preference' => 'en']);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => 'en']));
        app()->setLocale('ku');

        $this->assertSame('ar', $this->renderer->resolveLocale('ar', $order, $user));
    }

    public function test_unknown_explicit_lang_is_ignored(): void
    {
        $user = new User(['locale_preference' => 'ar']);
        $order = new Order();
        $order->setRelation('user', null);

        $this->assertSame('ar', $this->renderer->resolveLocale('tr', $order, $user));
    }

    public function test_order_owner_preference_beats_app_locale(): void
    {
        $user = new User(['locale_preference' => null]);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => 'ar']));
        app()->setLocale('ku');

        $this->assertSame('ar', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_authed_user_preference_when_order_owner_has_none(): void
    {
        $user = new User(['locale_preference' => 'ku']);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => null]));
        app()->setLocale('en');

        $this->assertSame('ku', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_app_locale_when_no_user_preferences(): void
    {
        $user = new User(['locale_preference' => null]);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => null]));
        app()->setLocale('ku');

        $this->assertSame('ku', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_falls_back_to_english_when_nothing_set(): void
    {
        $user = new User(['locale_preference' => null]);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => null]));
        app()->setLocale('fr');

        $this->assertSame('en', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_null_user_is_tolerated(): void
    {
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => 'ar']));
        app()->setLocale('en');

        $this->assertSame('ar', $this->renderer->resolveLocale(null, $order, null));
    }
}
