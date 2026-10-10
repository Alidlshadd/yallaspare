<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class StorefrontPageTitleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setMany(['site_name' => 'Yalla Spare']);
    }

    public function test_a_page_with_its_own_title_gets_the_store_name_appended(): void
    {
        $this->assertSame('Shop | Yalla Spare', $this->titleOf(route('shop.index')));
        $this->assertSame('Your cart | Yalla Spare', $this->titleOf(route('cart.index')));
        $this->assertSame('About Us | Yalla Spare', $this->titleOf(route('legal.about')));
    }

    public function test_a_title_that_already_names_the_store_is_left_as_written(): void
    {
        $this->assertSame('Terms of Service | Yalla Spare', $this->titleOf(route('legal.terms')));
        $this->assertSame('Spare part brands · Yalla Spare', $this->titleOf(url('/brands')));
    }

    public function test_a_page_without_a_title_is_just_the_store_name(): void
    {
        $this->assertSame('Yalla Spare', $this->titleOf(route('user.shop.home')));
    }

    public function test_markup_in_a_title_is_printed_as_text(): void
    {
        // The block form of a section is not escaped by Blade, so this is the
        // one way raw markup could have reached the head.
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.user')
            @section('title')</title><script>alert(1)</script>@endsection
            @section('content')@endsection
        BLADE, ['cspNonce' => 'test']);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt; | Yalla Spare</title>', $html);
    }

    public function test_the_cart_page_has_one_heading(): void
    {
        $html = (string) $this->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_the_shop_heading_names_the_catalogue_not_a_login_demand(): void
    {
        $html = (string) $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertMatchesRegularExpression('/<h1[^>]*>\s*Spare parts\s*<\/h1>/', $html);
        // A guest can order without an account, so the page must not say otherwise.
        $this->assertStringNotContainsString('Login or create an account to order', $html);
        $this->assertStringContainsString('Order without an account', $html);
    }

    public function test_opening_checkout_directly_leads_to_the_cart(): void
    {
        $this->get('/checkout')->assertRedirect(route('cart.index'));
    }

    private function titleOf(string $url): string
    {
        $html = (string) $this->get($url)->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<title>(.*?)<\/title>/s', $html, $match), 'No title on '.$url);

        return html_entity_decode(trim($match[1]), ENT_QUOTES);
    }
}
