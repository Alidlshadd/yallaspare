<x-app-layout>
    <x-slot name="header">{{ __('welcome.nav') }}</x-slot>
    <div class="wo-page mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @include('admin.discounts.partials._alerts')
        @include('admin.discounts.welcome-offer')
    </div>
</x-app-layout>
