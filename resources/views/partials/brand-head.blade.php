@php
    $iconVersion = '20260616b';
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
    // A square logo is not a 1200x630 banner, so when the admin has set one the
    // card drops to the small format rather than declaring dimensions it hasn't.
    $socialImageUrl = $siteLogoIconUrl ?: asset('icons/yallaspare-og-preview.png') . '?v=' . $iconVersion;
    $socialCardType = $siteLogoIconUrl ? 'summary' : 'summary_large_image';
@endphp
@if($siteLogoIconUrl)
    {{-- One source for every icon slot. The packaged favicons are skipped
         entirely: left in place they declare explicit sizes, and a browser
         picks those over a sizeless link, which is why the tab kept the old
         mark after an upload. --}}
    <link rel="icon" href="{{ $siteLogoIconUrl }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ $siteLogoIconUrl }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $iconVersion }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ $iconVersion }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $iconVersion }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $iconVersion }}">
@endif
<link rel="manifest" href="{{ route('brand.manifest') }}">
{{-- The webfont lives here, not in the layouts. Only layouts/app and
     layouts/guest ever linked it, so the whole storefront — layouts/user,
     user-account, auth-split, legal — fell back to the OS font while
     tailwind.config.js still asked for Figtree. Every layout includes this
     partial, so one link covers the system.

     Weight 700 is loaded because it is used: font-bold appears 688 times,
     and without the file the browser was synthesising every heading and
     price. 800 and 900 are deliberately absent — they should be edited out
     of the markup rather than downloaded. --}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
<meta name="theme-color" content="#070740">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $socialImageUrl }}">
@unless($siteLogoIconUrl)
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
@endunless
<meta property="og:image:alt" content="{{ $siteName }} logo">
<meta name="twitter:card" content="{{ $socialCardType }}">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $socialImageUrl }}">
