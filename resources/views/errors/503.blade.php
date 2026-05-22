@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('user.shop.home') ? route('user.shop.home') : url('/');
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : $homeUrl;
    $contactUrl = \Illuminate\Support\Facades\Route::has('legal.contact') ? route('legal.contact') : url('/contact');
@endphp

@include('errors.partials.show', [
    'errorCode' => '503',
    'errorBadge' => 'Service Unavailable',
    'errorTitle' => 'The storefront is temporarily unavailable.',
    'errorDescription' => 'The platform is likely under maintenance or handling a temporary capacity issue, so this page cannot be served right now.',
    'primaryAction' => ['label' => __('Return Home'), 'url' => $homeUrl],
    'secondaryAction' => ['label' => __('Try Shop Again'), 'url' => $shopUrl],
    'tertiaryAction' => ['label' => __('Contact Support'), 'url' => $contactUrl],
    'metaCards' => [
        [
            'label' => __('Possible Cause'),
            'title' => __('Maintenance or temporary downtime'),
            'description' => __('The service is up enough to answer, but it is not ready to process normal storefront requests yet.'),
        ],
        [
            'label' => __('Suggested Fix'),
            'title' => __('Wait and retry shortly'),
            'description' => __('This status is often temporary and clears once maintenance or recovery finishes.'),
        ],
    ],
    'recoverySteps' => [
        [
            'title' => __('Wait a short moment'),
            'description' => __('This is usually resolved faster than a normal outage or persistent application error.'),
        ],
        [
            'title' => __('Retry from the home or shop page'),
            'description' => __('Use a stable storefront entry point when checking if service has returned.'),
        ],
        [
            'title' => __('Contact support if downtime persists'),
            'description' => __('If the service does not recover soon, support can confirm whether maintenance is still active.'),
        ],
    ],
])
