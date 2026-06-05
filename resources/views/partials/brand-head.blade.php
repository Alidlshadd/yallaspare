@php
    $iconVersion = '20260605';
    $siteName = (string) ($systemSettings['site_name'] ?? config('app.name', 'YallaSpare'));
    $defaultMetaTitle = __('YallaSpare | Auto Spare Parts Platform in Iraq');
    $pageTitle = trim($__env->yieldContent('title'));
    $metaTitle = $pageTitle !== '' ? $pageTitle : $defaultMetaTitle;
    $description = trim($__env->yieldContent('meta_description'));
    $description = $description !== ''
        ? $description
        : __('YallaSpare is an auto spare parts platform built for Iraq, helping customers find trusted parts, check vehicle compatibility, order easily, and get reliable support.');
    $siteLogoUrl = (string) ($systemSettings['site_logo_url'] ?? '');
    $siteLogoIconUrl = $siteLogoUrl !== ''
        ? (str_starts_with($siteLogoUrl, 'http://') || str_starts_with($siteLogoUrl, 'https://')
            ? $siteLogoUrl
            : url($siteLogoUrl))
        : null;
    $socialImageUrl = asset('icons/yallaspare-og-preview.png') . '?v=' . $iconVersion;
@endphp
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $iconVersion }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ $iconVersion }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $iconVersion }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $iconVersion }}">
@if($siteLogoIconUrl)
    <link rel="icon" type="image/png" href="{{ $siteLogoIconUrl }}">
@endif
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $iconVersion }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}?v={{ $iconVersion }}">
<meta name="theme-color" content="#0f172a">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $socialImageUrl }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $siteName }} logo">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $socialImageUrl }}">
