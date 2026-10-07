<x-app-layout>
<x-slot name="header">{{ __('Customer Directory') }}</x-slot>
<div class="sales-workspace"><div class="sales-container">
@include('admin.manual-invoices.partials.workspace-header', ['directory' => true])
@include('admin.manual-invoices.partials.flash')
<div class="sales-section-heading"><h2>{{ __('sales.all_customers') }}</h2><span>{{ __('sales.directory_scope') }}</span></div>
<div class="sales-metrics sales-metrics-three">
@foreach ([
    [__('sales.total_customers'), $summary['total'], 'users-three', 'featured'],
    [__('sales.with_invoices'), $summary['with_invoices'], 'receipt', 'success'],
    [__('sales.cities'), $summary['cities'], 'truck', 'neutral'],
] as [$label, $value, $icon, $tone])
<div class="sales-metric sales-metric-{{ $tone }}">
    <div class="sales-metric-top"><span>{{ $label }}</span><span class="sales-metric-icon"><x-ph-icon :name="$icon" :size="22" /></span></div>
    <strong>{{ number_format($value) }}</strong><span class="sales-metric-line" aria-hidden="true"></span>
</div>
@endforeach
</div>
<section class="sales-panel sales-directory-panel" aria-label="{{ __('Customer Directory') }}">
<form method="GET" action="{{ route('admin.customers.index') }}" class="sales-filter-form">
    <div class="sales-search-row">
        <div class="sales-search">
            <label for="customer-search" class="sr-only">{{ __('Search by name, phone or city') }}</label>
            <x-ph-icon name="magnifying-glass" :size="21" />
            <input id="customer-search" type="search" name="q" value="{{ $search }}" placeholder="{{ __('sales.search_customers') }}">
        </div>
        <button type="submit" class="sales-button sales-button-dark">{{ __('Search') }}</button>
        @if ($search !== '')<a href="{{ route('admin.customers.index') }}" class="sales-button sales-button-light">{{ __('Clear') }}</a>@endif
    </div>
</form>
<div class="sales-list-heading"><h2>{{ __('Customer Directory') }} <span class="sales-count">{{ number_format($customers->total()) }}</span></h2><span>{{ __('sales.records', ['from' => $customers->firstItem() ?? 0, 'to' => $customers->lastItem() ?? 0, 'total' => $customers->total()]) }}</span></div>
@if ($customers->isEmpty())
<div class="sales-empty">
    <div class="sales-empty-icon"><x-ph-icon name="address-book" :size="38" /></div>
    <h3>{{ $search !== '' ? __('No customers match this search.') : __('No customers yet') }}</h3>
    <p>{{ $search !== '' ? __('sales.empty_search') : __('Add a customer to start writing invoices for them.') }}</p>
    <a class="sales-button sales-button-primary" href="{{ $search !== '' ? route('admin.customers.index') : route('admin.customers.create') }}">{{ $search !== '' ? __('Clear') : __('New Customer') }}</a>
</div>
@else
<div class="sales-table-scroll"><table class="sales-table sales-customer-table">
<thead><tr><th scope="col">{{ __('Customer') }}</th><th scope="col">{{ __('Phone') }}</th><th scope="col">{{ __('City') }}</th><th scope="col">{{ __('Invoices') }}</th><th scope="col" class="sales-align-end">{{ __('Actions') }}</th></tr></thead>
<tbody>
@foreach ($customers as $customer)
<tr>
    <td><div class="sales-person"><span class="sales-avatar sales-avatar-{{ $customer->id % 4 }}" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($customer->name), 0, 1)) }}</span><div class="sales-person-copy"><a class="sales-person-name" href="{{ route('admin.customers.edit', $customer) }}">{{ $customer->name }}</a>@if ($customer->address)<p class="sales-address" title="{{ $customer->address }}">{{ $customer->address }}</p>@endif</div></div></td>
    <td><div class="sales-contact"><a dir="ltr" href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a><a class="sales-chat" href="{{ \App\Services\Invoices\ManualInvoiceService::whatsappChatUrl($customer->whatsappNumber()) }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Open WhatsApp chat with :name', ['name' => $customer->name]) }}"><x-ph-icon name="whatsapp-logo" :size="20" /></a></div></td>
    <td><span class="sales-city">{{ $customer->city ?: '—' }}</span><span class="sales-country">{{ \App\Support\InternationalPhone::countryName($customer->country) }}</span></td>
    <td><span class="sales-invoice-count"><x-ph-icon name="receipt" :size="16" />{{ number_format($customer->invoices_count) }}<span class="sales-mobile-label">{{ __('Invoices') }}</span></span></td>
    <td><div class="sales-row-actions"><a class="sales-small-button" href="{{ route('admin.manual-invoices.create', ['customer_id' => $customer->id]) }}"><span aria-hidden="true">+</span>{{ __('New Invoice') }}</a><a class="sales-row-open" href="{{ route('admin.customers.edit', $customer) }}" aria-label="{{ __('Edit :name', ['name' => $customer->name]) }}"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m15 5 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15z"/></svg></a></div></td>
</tr>
@endforeach
</tbody></table></div>
@if ($customers->hasPages())<div class="sales-pagination">{{ $customers->links() }}</div>@endif
@endif
</section>
<p class="sales-directory-note"><x-ph-icon name="address-book" :size="17" />{{ __('sales.customer_note') }}</p>
</div></div>
</x-app-layout>