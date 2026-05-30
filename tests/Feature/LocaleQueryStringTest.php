<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class LocaleQueryStringTest extends TestCase
{
    public function test_query_string_overrides_session_for_current_request_only(): void
    {
        $request = Request::create('/?lang=ar', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->put('locale', 'en');

        (new SetLocale())->handle($request, fn ($req) => new Response());

        $this->assertSame('ar', app()->getLocale());
        $this->assertSame('en', $request->session()->get('locale'),
            'Session locale must not be mutated by a one-shot ?lang= query.');
    }

    public function test_unsupported_query_locale_falls_back_to_session(): void
    {
        $request = Request::create('/?lang=zz', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->put('locale', 'ku');

        (new SetLocale())->handle($request, fn ($req) => new Response());

        $this->assertSame('ku', app()->getLocale());
    }

    public function test_no_query_string_uses_session_locale(): void
    {
        $request = Request::create('/', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->put('locale', 'ar');

        (new SetLocale())->handle($request, fn ($req) => new Response());

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_no_session_or_query_defaults_to_en(): void
    {
        $request = Request::create('/', 'GET');
        $request->setLaravelSession($this->app['session.store']);

        (new SetLocale())->handle($request, fn ($req) => new Response());

        $this->assertSame('en', app()->getLocale());
    }
}
