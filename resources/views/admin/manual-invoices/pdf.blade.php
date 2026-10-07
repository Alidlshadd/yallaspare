<!DOCTYPE html>
<html lang="{{ $locale ?? str_replace('_', '-', app()->getLocale()) }}" dir="{{ !empty($isRtl) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    @include('partials.brand-head')
    @include('admin.orders.partials.invoice-styles')
</head>
<body class="{{ !empty($isRtl) ? 'rtl' : 'ltr' }}">
    @include('admin.manual-invoices.partials.document')
</body>
</html>
