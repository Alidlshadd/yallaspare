@extends('layouts.user')

@section('title', __('Checkout'))
@section('subtitle', __('Set default checkout behavior for contact and order flow'))
@section('actions')
    <a
        href="{{ route('user.settings.edit') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    >
        {{ __('Settings') }}
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            @include('user.partials.settings-nav')
        </aside>

        <div class="space-y-4">
            @if (session('success'))
                <x-ui.alert variant="success" :title="__('Success')">
                    {{ session('success') }}
                </x-ui.alert>
            @endif

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
                <div class="border-b border-slate-200/80 pb-6 dark:border-slate-800">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Section') }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Checkout Preferences') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Set defaults so ordering is faster and needs fewer repeated choices.') }}</p>
                </div>

                <form action="{{ route('user.settings.checkout.update') }}" method="POST" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="default_contact_method" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Default Contact Method') }}</label>
                        <select id="default_contact_method" name="default_contact_method" class="mt-2 block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/20 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            <option value="phone" @selected(old('default_contact_method', $user->default_contact_method ?? 'phone') === 'phone')>{{ __('Phone') }}</option>
                            <option value="email" @selected(old('default_contact_method', $user->default_contact_method ?? 'phone') === 'email')>{{ __('Email') }}</option>
                            <option value="whatsapp" @selected(old('default_contact_method', $user->default_contact_method ?? 'phone') === 'whatsapp')>{{ __('WhatsApp') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="default_delivery_note" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Default Delivery Note') }}</label>
                        <textarea id="default_delivery_note" name="default_delivery_note" rows="4" class="mt-2 block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/20 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white">{{ old('default_delivery_note', $user->default_delivery_note) }}</textarea>
                        @error('default_delivery_note')
                            <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition duration-200 hover:border-[#070740]/20 hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:hover:border-[#070740]/30 dark:hover:bg-slate-900">
                        <input type="checkbox" name="express_checkout" value="1" @checked(old('express_checkout', $user->express_checkout ?? false)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#070740] focus:ring-[#070740]/30">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-slate-900 dark:text-white">{{ __('Express Checkout') }}</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('Use your saved preferences more aggressively to reduce steps during checkout.') }}</span>
                        </span>
                    </label>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#070740] px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:opacity-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2">
                            {{ __('Save Checkout') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
