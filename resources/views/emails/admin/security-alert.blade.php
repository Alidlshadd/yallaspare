@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 8px;color:#dc2626;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ __('Security alert') }}</p>
    <h1 class="email-title" style="margin:0;color:#070740;font-size:28px;line-height:36px;font-weight:900;letter-spacing:-0.4px;">{{ $title ?? __('Admin security alert') }}</h1>
    <p class="email-copy" style="margin:14px 0 0;color:#475569;font-size:16px;line-height:26px;">{{ $bodyText ?? '' }}</p>

    @include('emails.components.security-notice')
@endsection
