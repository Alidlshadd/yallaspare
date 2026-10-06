<x-app-layout>
    <x-slot name="header">{{ __('Edit Customer') }}</x-slot>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @include('admin.manual-invoices.partials.hero', [
            'eyebrow' => __('Sales · In store and by phone'),
            'title' => $customer->name,
            'subtitle' => __('Changes here do not rewrite invoices that are already finalized.'),
            'actions' => [
                ['href' => route('admin.customers.index'), 'label' => __('Back to customers')],
                ['href' => route('admin.manual-invoices.create', ['customer_id' => $customer->id]), 'label' => __('New Invoice'), 'primary' => true],
            ],
        ])

        @include('admin.manual-invoices.partials.flash')
        @include('admin.customers._form')

        @if ($invoices->isNotEmpty())
            <div class="mt-5 bg-white border border-slate-200/70 rounded-2xl p-5 bento-shadow">
                <h3 class="text-sm font-bold text-slate-900 mb-3">{{ __('Recent invoices') }}</h3>
                <ul class="divide-y divide-slate-100">
                    @foreach ($invoices as $invoice)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                            <a href="{{ route('admin.manual-invoices.show', $invoice) }}" class="font-mono font-bold text-slate-900 hover:underline">{{ $invoice->number }}</a>
                            <span class="text-xs text-slate-500">{{ $invoice->invoice_date?->format('Y-m-d') }} · {{ $invoice->statusLabel() }} · {{ $invoice->paymentStatusLabel() }}</span>
                            <span class="font-bold text-slate-800">{{ app(\App\Services\Invoices\ManualInvoiceService::class)->money((float) $invoice->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>
    </div>
    </div>
</x-app-layout>
