<x-app-layout>
<x-slot name="header">{{ __('Manual Invoices') }}</x-slot>
@php
    $hasFilters = collect($filters)->filter(fn ($value) => $value !== '')->isNotEmpty();
    $collectionPercent = $summary['finalized_total'] > 0 ? min(100, round($summary['paid_total'] / $summary['finalized_total'] * 100)) : 0;
@endphp
<div class="sales-workspace"><div class="sales-container">
@include('admin.manual-invoices.partials.workspace-header', ['directory' => false])
@include('admin.manual-invoices.partials.flash')
<div class="sales-section-heading"><h2>{{ __('sales.all_invoices') }}</h2><span>{{ __('sales.invoice_scope') }}</span></div>
<div class="sales-metrics">
@foreach ([
    [__('Invoices'), number_format($summary['count']), 'invoice', 'neutral'],
    [__('Finalized sales'), $service->money($summary['finalized_total']), 'chart-line-up', 'featured'],
    [__('Paid'), $service->money($summary['paid_total']), 'credit-card', 'success'],
    [__('Outstanding'), $service->money($summary['outstanding_total']), 'clock-counter-clockwise', 'warning'],
] as [$label, $value, $icon, $tone])
<div class="sales-metric sales-metric-{{ $tone }}">
    <div class="sales-metric-top"><span>{{ $label }}</span><span class="sales-metric-icon"><x-ph-icon :name="$icon" :size="22" /></span></div>
    <strong>{{ $value }}</strong><span class="sales-metric-line" aria-hidden="true"></span>
</div>
@endforeach
</div>
<div class="sales-collection">
    <div class="sales-collection-label"><x-ph-icon name="coins" :size="19" /><span>{{ __('sales.collection') }}</span></div>
    <progress max="100" value="{{ $collectionPercent }}" aria-label="{{ __('sales.collection') }}">{{ $collectionPercent }}%</progress>
    <strong>{{ $summary['finalized_total'] > 0 ? __('sales.collected', ['percent' => $collectionPercent]) : __('sales.no_sales') }}</strong>
</div>
<p class="sales-explainer">{{ __('Drafts and void invoices are not counted as sales. Paid is the money actually recorded; outstanding is what finalized invoices are still owed.') }}</p>
<section class="sales-panel" aria-label="{{ __('sales.all_invoices') }}">
<form method="GET" action="{{ route('admin.manual-invoices.index') }}" class="sales-filter-form">
    <div class="sales-search-row">
        <div class="sales-search">
            <label for="invoice-search" class="sr-only">{{ __('Customer, phone or invoice number') }}</label>
            <x-ph-icon name="magnifying-glass" :size="21" />
            <input id="invoice-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('sales.search_invoices') }}">
        </div>
        <button type="submit" class="sales-button sales-button-dark">{{ __('Search') }}</button>
        @if ($hasFilters)<a href="{{ route('admin.manual-invoices.index') }}" class="sales-button sales-button-light">{{ __('Clear') }}</a>@endif
    </div>
    <div class="sales-filter-grid">
        <div><label for="invoice-status">{{ __('Status') }}</label><select id="invoice-status" name="status">
            <option value="">{{ __('All') }}</option>
            <option value="draft" @selected($filters['status'] === 'draft')>{{ __('Draft') }}</option>
            <option value="finalized" @selected($filters['status'] === 'finalized')>{{ __('Finalized') }}</option>
            <option value="void" @selected($filters['status'] === 'void')>{{ __('Void') }}</option>
        </select></div>
        <div><label for="invoice-payment">{{ __('Payment status') }}</label><select id="invoice-payment" name="payment_status">
            <option value="">{{ __('All') }}</option>
            @foreach (\App\Models\ManualInvoice::paymentStatusLabels() as $value => $label)
            <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>{{ $label }}</option>
            @endforeach
        </select></div>
        <div><label for="invoice-from">{{ __('From date') }}</label><input id="invoice-from" type="date" name="date_from" value="{{ $filters['date_from'] }}"></div>
        <div><label for="invoice-to">{{ __('To date') }}</label><input id="invoice-to" type="date" name="date_to" value="{{ $filters['date_to'] }}"></div>
    </div>
</form>
<div class="sales-list-heading"><h2>{{ __('Invoices') }} <span class="sales-count">{{ number_format($invoices->total()) }}</span></h2><span>{{ __('sales.records', ['from' => $invoices->firstItem() ?? 0, 'to' => $invoices->lastItem() ?? 0, 'total' => $invoices->total()]) }}</span></div>
@if ($invoices->isEmpty())
<div class="sales-empty">
    <div class="sales-empty-icon"><x-ph-icon name="invoice" :size="38" /></div>
    <h3>{{ $hasFilters ? __('No invoices match these filters.') : __('No manual invoices yet') }}</h3>
    <p>{{ $hasFilters ? __('sales.empty_search') : __('Create an invoice for a walk-in or phone customer.') }}</p>
    <a class="sales-button sales-button-primary" href="{{ $hasFilters ? route('admin.manual-invoices.index') : route('admin.manual-invoices.create') }}">{{ $hasFilters ? __('Clear') : __('New Invoice') }}</a>
</div>
@else
<div class="sales-table-scroll"><table class="sales-table sales-invoice-table">
<thead><tr>
    <th scope="col">{{ __('Invoice') }}</th><th scope="col">{{ __('Customer') }}</th><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Payment status') }}</th><th scope="col" class="sales-align-end">{{ __('Total') }}</th><th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th>
</tr></thead>
<tbody>
@foreach ($invoices as $invoice)
<tr>
    <td><a class="sales-invoice-link" href="{{ route('admin.manual-invoices.show', $invoice) }}"><span class="sales-file-icon"><x-ph-icon name="invoice" :size="21" /></span><bdi>{{ $invoice->number }}</bdi></a></td>
    <td><div class="sales-person-name">{{ $invoice->customer_name }}</div><div class="sales-contact"><span dir="ltr">{{ $invoice->customer_phone }}</span><a class="sales-chat" href="{{ \App\Services\Invoices\ManualInvoiceService::whatsappChatUrl($invoice->whatsappNumber()) }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Open WhatsApp chat with :name', ['name' => $invoice->customer_name]) }}"><x-ph-icon name="whatsapp-logo" :size="18" /></a></div></td>
    <td class="sales-date"><bdi>{{ $invoice->invoice_date?->format('d M Y') }}</bdi></td>
    <td><span class="sales-badge {{ $invoice->isFinalized() ? 'sales-badge-success' : ($invoice->isVoid() ? 'sales-badge-danger' : 'sales-badge-neutral') }}">{{ $invoice->statusLabel() }}</span></td>
    <td><span class="sales-badge {{ $invoice->payment_status === 'paid' ? 'sales-badge-success' : ($invoice->payment_status === 'partial' ? 'sales-badge-warning' : 'sales-badge-danger') }}">{{ $invoice->paymentStatusLabel() }}</span></td>
    <td class="sales-amount">{{ $service->money((float) $invoice->total) }}</td>
    <td><a class="sales-row-open" href="{{ route('admin.manual-invoices.show', $invoice) }}" aria-label="{{ __('sales.view_invoice', ['number' => $invoice->number]) }}"><span aria-hidden="true">↗</span></a></td>
</tr>
@endforeach
</tbody></table></div>
@if ($invoices->hasPages())<div class="sales-pagination">{{ $invoices->links() }}</div>@endif
@endif
</section>
</div></div>
</x-app-layout>