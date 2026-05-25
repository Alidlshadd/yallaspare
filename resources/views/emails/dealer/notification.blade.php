@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 8px;color:#0891b2;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ __('Dealer notification') }}</p>
    <h1 class="email-title" style="margin:0;color:#070740;font-size:28px;line-height:36px;font-weight:900;letter-spacing:-0.4px;">{{ $title ?? __('Dealer account update') }}</h1>
    <p class="email-copy" style="margin:14px 0 0;color:#475569;font-size:16px;line-height:26px;">{{ $bodyText ?? '' }}</p>
    @include('emails.components.meta-grid', ['items' => $metaItems ?? []])
@endsection
