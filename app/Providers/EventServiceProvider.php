<?php

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogSentEmail;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // The verification code email is sent explicitly by the registration
        // controllers (web + mobile) so a mail failure never aborts sign-up.
        MessageSent::class => [
            LogSentEmail::class,
        ],
        Failed::class => [
            LogFailedLogin::class,
        ],
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            \SocialiteProviders\Apple\AppleExtendSocialite::class.'@handle',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * The framework would auto-listen SendEmailVerificationNotification on
     * Registered. The registration controllers (web + mobile) send the code
     * explicitly instead, so a mail failure never aborts sign-up.
     */
    protected function configureEmailVerification(): void
    {
        //
    }
}
