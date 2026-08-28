@extends('emails.layouts.base', [
    'preheader' => __('Inventory alert: one or more products are running low on stock.'),
    'specTag'   => 'INV / STOCK',
])

@section('content')

    {{-- Kicker --}}
    <x-email-kicker :text="__('Inventory alert')" />

    {{-- Headline --}}
    <x-email-title :text="$title ?? __('Low stock alert')" />

    {{-- Body copy --}}
    <x-email-copy>
        {{ $bodyText ?? __('One or more products in your inventory have reached the low-stock threshold and require attention.') }}
    </x-email-copy>

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
