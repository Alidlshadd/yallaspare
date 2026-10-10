<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Two letters naming a language. Left readable so a 404 for an
        // address with no route can be shown in it without a session.
        SetLocale::COOKIE,
    ];
}
