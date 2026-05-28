@extends('emails.layouts.base', [
    'preheader' => $preheader ?? '',
])

@section('content')
    {{-- Body HTML is already sanitized by HtmlSanitizer at write time;
         rendering with {!! !!} here is the right escape boundary. --}}
    <div style="color:#0f172a; font-size:15px; line-height:24px;">
        {!! $bodyHtml !!}
    </div>
@endsection
