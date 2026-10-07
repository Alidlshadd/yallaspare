<header class="sales-hero">
    <div class="sales-hero-copy">
        <div class="sales-eyebrow"><span></span> {{ __('sales.workspace') }}</div>
        <h1>{{ $directory ? __('Customer Directory') : __('Manual Invoices') }}</h1>
        <p>{{ $directory ? __('sales.customer_intro') : __('sales.invoice_intro') }}</p>
        <a class="sales-button sales-button-primary" href="{{ $directory ? route('admin.customers.create') : route('admin.manual-invoices.create') }}">
            <span class="sales-plus" aria-hidden="true">+</span>
            {{ $directory ? __('New Customer') : __('New Invoice') }}
        </a>
    </div>
    <div class="sales-hero-art" aria-hidden="true">
        <div class="sales-orbit sales-orbit-one"></div><div class="sales-orbit sales-orbit-two"></div>
        <div class="sales-art-tile"><x-ph-icon :name="$directory ? 'address-book' : 'invoice'" :size="66" /></div>
        <div class="sales-art-badge"><x-ph-icon :name="$directory ? 'handshake' : 'coins'" :size="24" /></div>
        <span class="sales-art-dot"></span>
    </div>
    <div class="sales-hero-footer"><x-ph-icon name="receipt" :size="16" /> {{ __('Sales · In store and by phone') }}</div>
</header>
<nav class="sales-tabs" aria-label="{{ __('sales.workspace') }}">
    <a href="{{ route('admin.manual-invoices.index') }}" @if (!$directory) aria-current="page" @endif><x-ph-icon name="invoice" :size="19" />{{ __('Manual Invoices') }}</a>
    <a href="{{ route('admin.customers.index') }}" @if ($directory) aria-current="page" @endif><x-ph-icon name="address-book" :size="19" />{{ __('Customer Directory') }}</a>
</nav>
