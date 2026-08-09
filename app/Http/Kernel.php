<?php

namespace App\Http;

use App\Http\Middleware\ApplyUserPreferences;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnsureAccountIsVerified;
use App\Http\Middleware\EnsureAdminTwoFactorVerified;
use App\Http\Middleware\EnsureCustomerAreaUser;
use App\Http\Middleware\EnsureCustomerHasPhone;
use App\Http\Middleware\EnsureCustomerPhoneIsVerified;
use App\Http\Middleware\EnsureUserNotBanned;
use App\Http\Middleware\EnsureUserTwoFactorVerified;
use App\Http\Middleware\IntrusionPrevention;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\MinifyHtmlResponse;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RecordAnalyticsEvent;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RejectUnsafeEmailInput;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetLocaleFromHeader;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustHosts;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ValidateSignature;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        TrustHosts::class,
        TrustProxies::class,
        HandleCors::class,
        SecurityHeaders::class,
        IntrusionPrevention::class,
        RejectUnsafeEmailInput::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            EnsureUserNotBanned::class,
            SetLocale::class,
            ApplyUserPreferences::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            ThrottleRequests::class.':web',
            SubstituteBindings::class,
            RecordAnalyticsEvent::class,
            MinifyHtmlResponse::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            SetLocaleFromHeader::class,
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'auth.session' => AuthenticateSession::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'precognitive' => HandlePrecognitiveRequests::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureAccountIsVerified::class,
        'admin' => IsAdmin::class,
        'admin.2fa' => EnsureAdminTwoFactorVerified::class,
        'user.2fa' => EnsureUserTwoFactorVerified::class,
        'customer.area' => EnsureCustomerAreaUser::class,
        'customer.phone' => EnsureCustomerHasPhone::class,
        'customer.phone.verified' => EnsureCustomerPhoneIsVerified::class,
        'not.banned' => EnsureUserNotBanned::class,
    ];
}
