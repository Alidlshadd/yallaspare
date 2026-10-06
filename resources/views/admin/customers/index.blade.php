<x-app-layout>
    <x-slot name="header">{{ __('Customer Directory') }}</x-slot>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        @include('admin.manual-invoices.partials.hero', [
            'eyebrow' => __('Sales · In store and by phone'),
            'title' => __('Customer Directory'),
            'subtitle' => __('Customers you invoice by hand. Saving someone here does not create a site account or send them anything.'),
            'actions' => [
                ['href' => route('admin.manual-invoices.index'), 'label' => __('Manual Invoices')],
                ['href' => route('admin.customers.create'), 'label' => __('New Customer'), 'primary' => true],
            ],
        ])

        @include('admin.manual-invoices.partials.flash')

        <form method="GET" action="{{ route('admin.customers.index') }}" class="mb-4 flex flex-wrap items-end gap-3 bg-white border border-slate-200/70 rounded-2xl p-4 bento-shadow">
            <div class="flex-1 min-w-[220px]">
                <label for="customer-search" class="block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 mb-1.5">{{ __('Search by name, phone or city') }}</label>
                <input id="customer-search" type="search" name="q" value="{{ $search }}"
                       class="h-11 w-full px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900">
            </div>
            <button type="submit" class="h-11 px-5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition dark:text-slate-900 dark:hover:bg-slate-100">{{ __('Search') }}</button>
            @if ($search !== '')
                <a href="{{ route('admin.customers.index') }}" class="h-11 px-4 inline-flex items-center rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100 transition dark:hover:bg-slate-800">{{ __('Clear') }}</a>
            @endif
        </form>

        @if ($customers->isEmpty())
            <div class="bg-white border border-slate-200/70 rounded-2xl p-10 text-center bento-shadow">
                <h3 class="text-sm font-bold text-slate-900">{{ $search !== '' ? __('No customers match this search.') : __('No customers yet') }}</h3>
                <p class="text-xs text-slate-500 mt-1.5">{{ __('Add a customer to start writing invoices for them.') }}</p>
            </div>
        @else
            <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden bento-shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-[10.5px] font-bold uppercase tracking-widest text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Customer') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Phone') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('City') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Invoices') }}</th>
                                <th scope="col" class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($customers as $customer)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900">{{ $customer->name }}</div>
                                        @if ($customer->address)
                                            <div class="text-xs text-slate-500 mt-0.5 max-w-xs truncate">{{ $customer->address }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span dir="ltr" class="font-mono text-xs text-slate-800">{{ $customer->phone }}</span>
                                            <a href="{{ \App\Services\Invoices\ManualInvoiceService::whatsappChatUrl($customer->whatsappNumber()) }}"
                                               target="_blank" rel="noopener noreferrer"
                                               aria-label="{{ __('Open WhatsApp chat with :name', ['name' => $customer->name]) }}"
                                               title="{{ __('Open WhatsApp chat') }}"
                                               class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-100 transition">
                                                <i class="fab fa-whatsapp text-sm" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $customer->city ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ number_format($customer->invoices_count) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.manual-invoices.create', ['customer_id' => $customer->id]) }}"
                                               class="inline-flex h-8 items-center rounded-lg px-3 text-[11px] font-bold text-slate-700 bg-slate-50 border border-slate-200 hover:bg-slate-100 transition dark:hover:bg-slate-800">
                                                {{ __('New Invoice') }}
                                            </a>
                                            <a href="{{ route('admin.customers.edit', $customer) }}"
                                               aria-label="{{ __('Edit :name', ['name' => $customer->name]) }}"
                                               class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 bg-slate-50 border border-slate-200 hover:text-slate-800 hover:bg-slate-100 dark:hover:text-slate-100 transition">
                                                <i class="fas fa-pen text-[11px]" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($customers->hasPages())
                <div class="mt-5">{{ $customers->links() }}</div>
            @endif
        @endif

    </div>
    </div>
    </div>
</x-app-layout>
