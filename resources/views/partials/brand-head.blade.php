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

     Weights run 400-800 because that is what the markup asks for: font-bold
     appears 688 times, font-extrabold 234 and font-black 223, and every one
     of them was being synthesised before. Synthetic weight is worse under
     Sora than it was under the old face — Sora is already wide, so smearing
     it wider blurs exactly the headings the face was chosen for.

     900 is not loaded and not available in Sora anyway; font-black lands on
     800, which is a real cut rather than a faked one. The better fix is to
     take font-black and font-extrabold out of the markup — 900 is noise at
     screen sizes — but that is a hierarchy decision to make deliberately,
     not a side effect of changing the typeface. --}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link href="https://fonts.bunny.net/css?family=sora:600,700,800|inter:400,500,600,700,800|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />
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
