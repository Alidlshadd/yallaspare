@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('user.shop.home') ? route('user.shop.home') : url('/');
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : $homeUrl;
    $contactUrl = \Illuminate\Support\Facades\Route::has('legal.contact') ? route('legal.contact') : url('/contact');
@endphp

@include('errors.partials.show', [
    'errorCode' => '401',
    'errorBadge' => 'Authentication Required',
    'errorTitle' => 'You need to sign in before opening this page.',
    'errorDescription' => 'The requested destination requires an authenticated session and the current request could not be verified as signed in.',
    'primaryAction' => ['label' => __('Return Home'), 'url' => $homeUrl],
    'secondaryAction' => ['label' => __('Browse Shop'), 'url' => $shopUrl],
    'tertiaryAction' => ['label' => __('Contact Support'), 'url' => $contactUrl],
    'metaCards' => [
        [
            'label' => __('Possible Cause'),
            'title' => __('Missing or expired login state'),
            'description' => __('The session may not be authenticated, or the user may have been signed out before reaching the page.'),
        ],
        [
            'label' => __('Suggested Fix'),
            'title' => __('Sign in again and retry'),
            'description' => __('Return to a public route, authenticate, then reopen the protected destination.'),
        ],
    ],
    'recoverySteps' => [
        [
            'title' => __('Return to the storefront home'),
            'description' => __('Use a public route first so the browsing session is stable again.'),
        ],
        [
            'title' => __('Sign in with the correct account'),
            'description' => __('Use the account that should have access to the requested page or action.'),
        ],
        [
            'title' => __('Ask support if login keeps failing'),
            'description' => __('If the system still rejects the session, support can help verify the account state.'),
        ],
    ],
])
