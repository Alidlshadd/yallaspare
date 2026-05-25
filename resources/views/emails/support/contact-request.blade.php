@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 8px;color:#2563eb;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ __('Support request') }}</p>
    <h1 class="email-title" style="margin:0;color:#070740;font-size:28px;line-height:36px;font-weight:900;letter-spacing:-0.4px;">{{ __('New YallaSpare support request') }}</h1>

    @include('emails.components.meta-grid', ['items' => [
        ['label' => __('Name'), 'value' => $name ?? ''],
        ['label' => __('Email'), 'value' => $email ?? ''],
        ['label' => __('Phone'), 'value' => $phone ?? ''],
        ['label' => __('Topic'), 'value' => $topic ?? ''],
        ['label' => __('Subject'), 'value' => $requestSubject ?? ''],
    ]])

    <div style="margin-top:20px;border:1px solid #e2e8f0;border-radius:16px;padding:16px;color:#334155;font-size:15px;line-height:24px;background:#f8fafc;">
        {!! nl2br(e($messageText ?? '')) !!}
    </div>
@endsection
