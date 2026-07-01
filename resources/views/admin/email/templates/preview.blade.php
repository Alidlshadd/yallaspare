@extends('emails.layouts.base', [
    'preheader'      => $subject,
    'recipientEmail' => $sampleVars['email'] ?? null,
    'recipientName'  => $sampleVars['name'] ?? null,
    'specTag'        => strtoupper($templateKey),
    'title'          => $subject,
    'locale'         => $locale,
])

@section('content')
    <h1 class="em-title" style="margin:0;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#070740;font-size:28px;line-height:34px;font-weight:700;letter-spacing:-0.6px;">
        {{ $subject }}
    </h1>
    <div class="em-copy" style="margin:16px 0 0;color:#4a4e63;font-size:15px;line-height:25px;">
        {!! $body_html !!}
    </div>
@endsection
