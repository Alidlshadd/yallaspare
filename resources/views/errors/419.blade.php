@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('user.shop.home') ? route('user.shop.home') : url('/');
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : $homeUrl;
    $contactUrl = \Illuminate\Support\Facades\Route::has('legal.contact') ? route('legal.contact') : url('/contact');
@endphp

@include('errors.partials.show', [
    'errorCode' => '419',
    'errorBadge' => 'Session Expired',
    'errorTitle' => 'Your session token is no longer valid.',
    'errorDescription' => 'This usually happens after a long idle period, a stale tab, or a form submission from an expired session.',
    'primaryAction' => ['label' => __('Return Home'), 'url' => $homeUrl],
    'secondaryAction' => ['label' => __('Open Shop'), 'url' => $shopUrl],
    'tertiaryAction' => ['label' => __('Contact Support'), 'url' => $contactUrl],
    'metaCards' => [
        [
            'label' => __('Possible Cause'),
            'title' => __('Expired browser session'),
            'description' => __('The security token stored in the page no longer matches the active session on the server.'),
        ],
        [
            'label' => __('Suggested Fix'),
            'title' => __('Reload and retry the action'),
            'description' => __('Refresh the page, then repeat the last step from a fresh session state.'),
        ],
    ],
    'recoverySteps' => [
        [
            'title' => __('Refresh the page first'),
            'description' => __('A fresh reload usually restores the valid token and clears the expired form state.'),
        ],
        [
            'title' => __('Repeat the previous action'),
            'description' => __('Submit the form again only after the page has fully reloaded.'),
        ],
        [
            'title' => __('Use support if the issue keeps repeating'),
            'description' => __('Frequent session expiry may indicate a browser, cookie, or account flow issue.'),
        ],
    ],
])
