<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Advertising measurement tags
    |--------------------------------------------------------------------------
    |
    | GA4 and the Meta pixel are off until an id is set. Off means nothing at
    | all: no script on the page, and no widening of the content security
    | policy. The application's own AnalyticsEvent tables are unaffected —
    | these tags exist so ad campaigns can be measured and audiences rebuilt,
    | which a first-party table cannot do.
    |
    */

    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID', ''),
    ],

    'meta' => [
        'pixel_id' => env('META_PIXEL_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content security policy hosts
    |--------------------------------------------------------------------------
    |
    | What each tag actually talks to. SecurityHeaders reads this so the policy
    | is described in one place and widened only for the tag that is switched
    | on. Getting this wrong is silent: the script loads, the beacon is
    | blocked, and the reports simply stay empty.
    |
    | 'script' is listed for CSP2 browsers only — under CSP3 'strict-dynamic'
    | makes host allowlists in script-src ignored, and the nonced loader is
    | what authorises these.
    |
    */

    'csp' => [

        'ga4' => [
            'script' => ['https://www.googletagmanager.com'],
            'connect' => [
                'https://*.google-analytics.com',
                'https://*.analytics.google.com',
                'https://*.googletagmanager.com',
                'https://*.g.doubleclick.net',
            ],
            'img' => [
                'https://*.google-analytics.com',
                'https://*.googletagmanager.com',
                'https://*.g.doubleclick.net',
            ],
        ],

        'meta' => [
            'script' => ['https://connect.facebook.net'],
            'connect' => ['https://www.facebook.com'],
            'img' => ['https://www.facebook.com'],
        ],

    ],

];
