<x-app-layout>
    <x-slot name="header">{{ __('New Customer') }}</x-slot>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @include('admin.manual-invoices.partials.hero', [
            'eyebrow' => __('Sales · In store and by phone'),
            'title' => __('New Customer'),
            'subtitle' => __('Name and phone are required. No site account is created and no message is sent.'),
            'actions' => [
                ['href' => route('admin.customers.index'), 'label' => __('Back to customers')],
            ],
        ])

        @include('admin.manual-invoices.partials.flash')
        @include('admin.customers._form')

    </div>
    </div>
    </div>
</x-app-layout>
