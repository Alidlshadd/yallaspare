@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('user.shop.home') ? route('user.shop.home') : url('/');
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : $homeUrl;
    $contactUrl = \Illuminate\Support\Facades\Route::has('legal.contact') ? route('legal.contact') : url('/contact');
@endphp

@include('errors.partials.show', [
    'errorCode' => '500',
    'errorBadge' => 'Server Error',
    'errorTitle' => 'The server could not complete this request.',
    'errorDescription' => 'An internal application error interrupted the page before it could finish rendering properly.',
    'primaryAction' => ['label' => __('Return Home'), 'url' => $homeUrl],
    'secondaryAction' => ['label' => __('Browse Shop'), 'url' => $shopUrl],
    'tertiaryAction' => ['label' => __('Contact Support'), 'url' => $contactUrl],
    'metaCards' => [
        [
            'label' => __('Possible Cause'),
            'title' => __('Unexpected server-side failure'),
            'description' => __('The request hit an application error while the page or data was being prepared.'),
        ],
        [
            'label' => __('Suggested Fix'),
            'title' => __('Retry from a stable entry point'),
            'description' => __('Start again from the storefront home or shop page after a short pause.'),
        ],
    ],
    'recoverySteps' => [
        [
            'title' => __('Retry the page once'),
            'description' => __('If the issue was temporary, a single retry may succeed immediately.'),
        ],
        [
            'title' => __('Use a different route to continue browsing'),
            'description' => __('Home and shop routes are the safest recovery points when a page fails unexpectedly.'),
        ],
        [
            'title' => __('Send support the failing URL'),
            'description' => __('Include the page address and the action you were taking before the error appeared.'),
        ],
    ],
])
