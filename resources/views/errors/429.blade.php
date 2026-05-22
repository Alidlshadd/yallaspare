@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('user.shop.home') ? route('user.shop.home') : url('/');
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : $homeUrl;
    $contactUrl = \Illuminate\Support\Facades\Route::has('legal.contact') ? route('legal.contact') : url('/contact');
@endphp

@include('errors.partials.show', [
    'errorCode' => '429',
    'errorBadge' => 'Too Many Requests',
    'errorTitle' => 'Request traffic is temporarily throttled.',
    'errorDescription' => 'Too many actions were sent in a short time window, so the platform paused additional requests for a moment.',
    'primaryAction' => ['label' => __('Return Home'), 'url' => $homeUrl],
    'secondaryAction' => ['label' => __('Browse Shop'), 'url' => $shopUrl],
    'tertiaryAction' => ['label' => __('Contact Support'), 'url' => $contactUrl],
    'metaCards' => [
        [
            'label' => __('Possible Cause'),
            'title' => __('Rate limit reached'),
            'description' => __('Repeated searches, form submits, or API-style requests can trigger temporary protection rules.'),
        ],
        [
            'label' => __('Suggested Fix'),
            'title' => __('Wait briefly before retrying'),
            'description' => __('Pause for a short moment, then retry the action once the throttle window has passed.'),
        ],
    ],
    'recoverySteps' => [
        [
            'title' => __('Stop retrying for a short period'),
            'description' => __('Repeated refreshes usually extend the limit instead of clearing it.'),
        ],
        [
            'title' => __('Return through normal storefront navigation'),
            'description' => __('Use the home or shop pages once the throttle window has cooled down.'),
        ],
        [
            'title' => __('Report it if the throttle looks abnormal'),
            'description' => __('If normal browsing is getting blocked too often, support should review the rate-limit behavior.'),
        ],
    ],
])
