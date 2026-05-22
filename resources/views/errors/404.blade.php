@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('user.shop.home') ? route('user.shop.home') : url('/');
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : $homeUrl;
    $contactUrl = \Illuminate\Support\Facades\Route::has('legal.contact') ? route('legal.contact') : url('/contact');
@endphp

@include('errors.partials.show', [
    'errorCode' => '404',
    'errorBadge' => 'Page Not Found',
    'errorTitle' => 'This route does not exist in the storefront.',
    'errorDescription' => 'The page may have been moved, removed, or the link may be incorrect. Use the shortcuts below to get back to the catalog or contact support.',
    'primaryAction' => ['label' => __('Return Home'), 'url' => $homeUrl],
    'secondaryAction' => ['label' => __('Browse Shop'), 'url' => $shopUrl],
    'tertiaryAction' => ['label' => __('Contact Support'), 'url' => $contactUrl],
    'metaCards' => [
        [
            'label' => __('Possible Cause'),
            'title' => __('Broken or outdated link'),
            'description' => __('The destination may have changed after a navigation or content update.'),
        ],
        [
            'label' => __('Suggested Fix'),
            'title' => __('Restart from the catalog'),
            'description' => __('Use the shop to search by SKU, part name, or category instead of the invalid URL.'),
        ],
    ],
    'recoverySteps' => [
        [
            'title' => __('Go back to the main storefront'),
            'description' => __('Use the home entry point to restore the normal browsing flow.'),
        ],
        [
            'title' => __('Search the product catalog'),
            'description' => __('Try a part keyword, SKU, or category from the shop page.'),
        ],
        [
            'title' => __('Ask support if the page should exist'),
            'description' => __('If this URL came from a promotion or shared message, support can verify the correct destination.'),
        ],
    ],
])
