<x-app-layout>
    <x-slot name="header">{{ __('Manual Invoices') }}</x-slot>

    @php
        $inputClass = 'h-11 w-full px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900';
        $labelClass = 'block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 mb-1.5';
        $hasFilters = collect($filters)->filter(fn ($value) => $value !== '')->isNotEmpty();
    @endphp

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        @include('admin.manual-invoices.partials.hero', [
            'eyebrow' => __('Sales · In store and by phone'),
            'title' => __('Manual Invoices'),
            'subtitle' => __('Invoices for customers who buy in the shop or order by phone, without a site account.'),
            'actions' => [
                ['href' => route('admin.customers.index'), 'label' => __('Customer Directory')],
                ['href' => route('admin.manual-invoices.create'), 'label' => __('New Invoice'), 'primary' => true],
            ],
        ])

        @include('admin.manual-invoices.partials.flash')

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            @foreach ([
                [__('Invoices'), number_format($summary['count'])],
                [__('Finalized sales'), $service->money($summary['finalized_total'])],
                [__('Paid'), $service->money($summary['paid_total'])],
                [__('Outstanding'), $service->money($summary['outstanding_total'])],
            ] as [$label, $value])
                <div class="bg-white border border-slate-200/70 rounded-2xl p-4 bento-shadow">
                    <div class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500">{{ $label }}</div>
                    <div class="mt-1.5 text-lg font-bold text-slate-900">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        <p class="mb-4 text-xs text-slate-500">{{ __('Drafts are not counted as sales. These figures are separate from the online revenue reports, which are built from site orders only.') }}</p>

        <form method="GET" action="{{ route('admin.manual-invoices.index') }}" class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 bg-white border border-slate-200/70 rounded-2xl p-4 bento-shadow">
            <div class="lg:col-span-2">
                <label for="invoice-search" class="{{ $labelClass }}">{{ __('Customer, phone or invoice number') }}</label>
                <input id="invoice-search" type="search" name="q" value="{{ $filters['q'] }}" class="{{ $inputClass }}">
            </div>
            <div>
                <label for="invoice-status" class="{{ $labelClass }}">{{ __('Status') }}</label>
                <select id="invoice-status" name="status" class="{{ $inputClass }}">
                    <option value="">{{ __('All') }}</option>
                    <option value="draft" @selected($filters['status'] === 'draft')>{{ __('Draft') }}</option>
                    <option value="finalized" @selected($filters['status'] === 'finalized')>{{ __('Finalized') }}</option>
                </select>
            </div>
            <div>
                <label for="invoice-payment" class="{{ $labelClass }}">{{ __('Payment status') }}</label>
                <select id="invoice-payment" name="payment_status" class="{{ $inputClass }}">
                    <option value="">{{ __('All') }}</option>
                    @foreach (\App\Models\ManualInvoice::paymentStatusLabels() as $value => $label)
                        <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="invoice-from" class="{{ $labelClass }}">{{ __('From date') }}</label>
                <input id="invoice-from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="{{ $inputClass }}">
            </div>
            <div>
                <label for="invoice-to" class="{{ $labelClass }}">{{ __('To date') }}</label>
                <input id="invoice-to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="{{ $inputClass }}">
            </div>
            <div class="sm:col-span-2 lg:col-span-6 flex items-center gap-2">
                <button type="submit" class="h-10 px-5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition dark:text-slate-900 dark:hover:bg-slate-100">{{ __('Search') }}</button>
                @if ($hasFilters)
                    <a href="{{ route('admin.manual-invoices.index') }}" class="h-10 px-4 inline-flex items-center rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100 transition dark:hover:bg-slate-800">{{ __('Clear') }}</a>
                @endif
            </div>
        </form>

        @if ($invoices->isEmpty())
            <div class="bg-white border border-slate-200/70 rounded-2xl p-10 text-center bento-shadow">
                <h3 class="text-sm font-bold text-slate-900">{{ $hasFilters ? __('No invoices match these filters.') : __('No manual invoices yet') }}</h3>
                <p class="text-xs text-slate-500 mt-1.5">{{ __('Create an invoice for a walk-in or phone customer.') }}</p>
            </div>
        @else
            <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden bento-shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-[10.5px] font-bold uppercase tracking-widest text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Invoice') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Customer') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Date') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Payment status') }}</th>
                                <th scope="col" class="px-4 py-3 text-end">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.manual-invoices.show', $invoice) }}" class="font-mono font-bold text-slate-900 hover:underline">{{ $invoice->number }}</a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900">{{ $invoice->customer_name }}</div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span dir="ltr" class="font-mono text-xs text-slate-500">{{ $invoice->customer_phone }}</span>
                                            <a href="{{ \App\Services\Invoices\ManualInvoiceService::whatsappChatUrl($invoice->whatsappNumber()) }}"
                                               target="_blank" rel="noopener noreferrer"
                                               aria-label="{{ __('Open WhatsApp chat with :name', ['name' => $invoice->customer_name]) }}"
                                               title="{{ __('Open WhatsApp chat') }}"
                                               class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-100 transition">
                                                <i class="fab fa-whatsapp text-xs" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ $invoice->invoice_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $invoice->isFinalized() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $invoice->statusLabel() }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $invoice->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($invoice->payment_status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700') }}">{{ $invoice->paymentStatusLabel() }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-end font-bold text-slate-900 whitespace-nowrap">{{ $service->money((float) $invoice->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($invoices->hasPages())
                <div class="mt-5">{{ $invoices->links() }}</div>
            @endif
        @endif

    </div>
    </div>
    </div>
</x-app-layout>
