<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizedErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setMany(['site_name' => 'Yalla Spare']);
    }

    public function test_a_page_remembers_the_language_in_a_readable_cookie(): void
    {
        $response = $this->withSession(['locale' => 'ku'])->get(route('legal.about'))->assertOk();

        $response->assertPlainCookie(SetLocale::COOKIE, 'ku');
    }

    public function test_the_cookie_is_not_sent_again_once_it_is_right(): void
    {
        $response = $this->withSession(['locale' => 'ar'])
            ->withUnencryptedCookie(SetLocale::COOKIE, 'ar')
            ->get(route('legal.about'))
            ->assertOk();

        $response->assertCookieMissing(SetLocale::COOKIE);
    }

    public function test_an_address_with_no_route_is_answered_in_the_remembered_language(): void
    {
        $response = $this->withUnencryptedCookie(SetLocale::COOKIE, 'ar')
            ->get('/no-such-page-anywhere')
            ->assertNotFound();

        $response->assertSee('dir="rtl"', false);
        $response->assertSee('الصفحة غير موجودة');
        $response->assertSee('404 | يلا سبير');
        $response->assertDontSee('This route does not exist in the storefront.');
    }

    public function test_an_address_with_no_route_is_english_without_a_hint(): void
    {
        $this->get('/no-such-page-anywhere')
            ->assertNotFound()
            ->assertSee('dir="ltr"', false)
            ->assertSee('Page Not Found');
    }

    public function test_a_made_up_language_in_the_cookie_is_ignored(): void
    {
        $this->withUnencryptedCookie(SetLocale::COOKIE, '../../etc')
            ->get('/no-such-page-anywhere')
            ->assertNotFound()
            ->assertSee('Page Not Found');
    }

    public function test_sign_in_pages_name_the_store_in_the_readers_language(): void
    {
        $html = (string) $this->get(route('login').'?lang=ar')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<title>(.*?)<\/title>/s', $html, $match));
        $this->assertStringContainsString('يلا سبير', $match[1]);
        $this->assertStringNotContainsString('Yalla Spare', $match[1]);
    }
}
