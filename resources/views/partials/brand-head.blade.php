@php
    $iconVersion = '20260824';
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

    // When a logo is configured every icon slot is rendered from it, square and
    // at the exact size the slot declares. The packaged files below are the
    // no-logo fallback, not a second competing set.
    $brandIconVersion = \App\Support\BrandIcon::version();
    $brandIcon = fn (int $size) => route('brand.icon', ['size' => $size]).'?v='.$brandIconVersion;
    // A square logo is not a 1200x630 banner, so when the admin has set one the
    // card drops to the small format rather than declaring dimensions it hasn't.
    $socialImageUrl = $siteLogoIconUrl ?: asset('icons/yallaspare-og-preview.png') . '?v=' . $iconVersion;
    $socialCardType = $siteLogoIconUrl ? 'summary' : 'summary_large_image';
@endphp
{{-- Both branches declare the same slots at the same sizes, so switching
     between them can never leave a browser holding a stale link that happens
     to declare a size the new set does not. --}}
@if($brandIconVersion)
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $brandIcon(16) }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $brandIcon(32) }}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ $brandIcon(48) }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ $brandIcon(192) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $brandIcon(180) }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $iconVersion }}" sizes="16x16 32x32 48x48">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $iconVersion }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $iconVersion }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}?v={{ $iconVersion }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $iconVersion }}">
@endif
<link rel="manifest" href="{{ route('brand.manifest') }}">
{{-- The webfonts live here, not in the layouts. Only layouts/app and
     layouts/guest ever linked one, so the whole storefront — layouts/user,
     user-account, auth-split, legal — fell back to the OS font while
     tailwind.config.js still asked for a face it never downloaded. Every
     layout includes this partial, so one link covers the system.

     Three families, one job each:

     Sora carries display — headings, hero, price. Its wide, confident
     figures are the reason it was chosen, and they only pay off at size;
     below about 14px its x-height gets thin, so it stays off body text.

     Inter carries body and UI — paragraphs, forms, tables, everything
     dense. It also holds the tabular figures that admin tables need and
     Sora does not have.

     IBM Plex Sans Arabic carries Arabic and Kurdish. Sora ships latin and
     latin-ext only, so without this the ar and ku storefronts rendered in
     whatever the OS supplied. It sits last in both stacks rather than
     behind a dir="rtl" rule because font fallback is per glyph: Latin
     resolves to Sora or Inter, Arabic script falls through to Plex, and a
     mixed-script line gets both without any selector deciding. Its arabic
     subset covers U+0600-06FF, which includes the Sorani letters
     (ڕ ڵ ێ ۆ ە) a plain Arabic face would miss.

     Weights stop at 700, which is now the top of the scale. 800 and 900 were
     briefly loaded because 462 places asked for them, but those weights were
     compensating for type too small to carry emphasis on its own — and that
     type is larger now. Under Sora especially, stacking weight on an already
     wide letterform reads as heavy rather than emphatic, so the markup was
     brought down to 700 and the extra cuts came back off the wire. --}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link href="https://fonts.bunny.net/css?family=sora:600,700|inter:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />
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
