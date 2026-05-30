@php
    $seoLocaleMap = [
        'en' => ['hreflang' => 'en', 'og' => 'en_US'],
        'ar' => ['hreflang' => 'ar', 'og' => 'ar_IQ'],
        'ku' => ['hreflang' => 'ckb', 'og' => 'ckb_IQ'],
    ];
    $currentLocale = app()->getLocale();
    $currentLocale = isset($seoLocaleMap[$currentLocale]) ? $currentLocale : 'en';
    $canonicalUrl = url()->current();
    $separator = str_contains($canonicalUrl, '?') ? '&' : '?';
@endphp
<link rel="canonical" href="{{ $canonicalUrl }}">
@foreach ($seoLocaleMap as $code => $meta)
    <link rel="alternate" hreflang="{{ $meta['hreflang'] }}" href="{{ $canonicalUrl . $separator . 'lang=' . $code }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">
<meta property="og:locale" content="{{ $seoLocaleMap[$currentLocale]['og'] }}">
@foreach ($seoLocaleMap as $code => $meta)
    @if ($code !== $currentLocale)
        <meta property="og:locale:alternate" content="{{ $meta['og'] }}">
    @endif
@endforeach
