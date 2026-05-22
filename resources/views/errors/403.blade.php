@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('user.shop.home') ? route('user.shop.home') : url('/');
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : $homeUrl;
    $contactUrl = \Illuminate\Support\Facades\Route::has('legal.contact') ? route('legal.contact') : url('/contact');
@endphp

@include('errors.partials.show', [
    'errorCode' => '403',
    'errorBadge' => 'Access Restricted',
    'errorTitle' => 'You do not have permission to open this page.',
    'errorDescription' => 'The request reached the server, but this destination is restricted for the current account or session.',
    'primaryAction' => ['label' => __('Return Home'), 'url' => $homeUrl],
    'secondaryAction' => ['label' => __('Browse Shop'), 'url' => $shopUrl],
    'tertiaryAction' => ['label' => __('Contact Support'), 'url' => $contactUrl],
    'metaCards' => [
        [
            'label' => __('Possible Cause'),
            'title' => __('Protected account area'),
            'description' => __('This route may require a different role, a different account, or an admin permission.'),
        ],
        [
            'label' => __('Suggested Fix'),
            'title' => __('Switch back to an allowed path'),
            'description' => __('Return to the storefront or sign in with an account that has access to the requested page.'),
        ],
    ],
    'recoverySteps' => [
        [
            'title' => __('Return to a public page'),
            'description' => __('Use the home or shop routes to continue browsing without interruption.'),
        ],
        [
            'title' => __('Verify the active account'),
            'description' => __('If this page should be available, confirm that you are signed in with the correct account.'),
        ],
        [
            'title' => __('Contact support if access looks wrong'),
            'description' => __('Share the page URL and the account email so the permission issue can be reviewed.'),
        ],
    ],
])
