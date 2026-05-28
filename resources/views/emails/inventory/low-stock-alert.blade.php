@extends('emails.layouts.base', [
    'preheader' => __('Inventory alert: one or more products are running low on stock.'),
])

@section('content')

    {{-- Eyebrow --}}
    <p style="margin:0 0 10px;color:#d97706;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
        {{ __('Inventory alert') }}
    </p>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ $title ?? __('Low stock alert') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
        {{ $bodyText ?? __('One or more products in your inventory have reached the low-stock threshold and require attention.') }}
    </p>

    {{-- Meta grid --}}
    @include('emails.components.meta-grid', ['items' => $metaItems ?? []])

    {{-- Warning alert --}}
    @include('emails.components.alert', [
        'tone'    => 'warning',
        'message' => __('Update your inventory levels as soon as possible to avoid missed orders and customer disappointment.'),
    ])

    {{-- CTA --}}
    @if (!empty($actionUrl))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0 8px;">
    <tr><td>
        @include('emails.components.button', [
            'url'   => $actionUrl,
            'label' => $actionText ?? __('Manage inventory'),
        ])
    </td></tr>
    </table>
    @endif

@endsection
