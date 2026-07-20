@extends('layouts.user')

@section('title', __('Address Book'))
@section('subtitle', __('Manage saved delivery addresses and defaults'))
@section('actions')
    <div class="flex flex-wrap items-center justify-end gap-3">
        <a
            href="{{ route('user.account.edit') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            {{ __('Account') }}
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </a>
        <a
            href="{{ route('account.addresses.create') }}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition duration-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-primary/30"
        >
            {{ __('Add New Address') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            @include('user.partials.account-nav')
        </aside>

        <div class="space-y-6">
            <div class="flex flex-col gap-3 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('Address Book') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Manage saved delivery addresses and defaults') }}</p>
                </div>
                <a
                    href="{{ route('account.addresses.create') }}"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-white transition duration-200 hover:bg-[#10106a] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 4.25a.75.75 0 0 1 .75.75v4.25H15a.75.75 0 0 1 0 1.5h-4.25V15a.75.75 0 0 1-1.5 0v-4.25H5a.75.75 0 0 1 0-1.5h4.25V5a.75.75 0 0 1 .75-.75Z" />
                    </svg>
                    {{ __('Add Address') }}
                </a>
            </div>

            @if ($addresses->isEmpty())
                <x-ui.empty :title="__('No saved addresses yet')" :description="__('Create your first address to speed up checkout and order handling.')">
                    <x-slot name="action">
                        <a
                            href="{{ route('account.addresses.create') }}"
                            class="inline-flex h-10 items-center justify-center rounded-app border border-[var(--border)] bg-[var(--primary)] px-4 text-sm font-medium text-white transition duration-150 hover:bg-[var(--primary-hover)] focus:outline-none focus:ring-4 ring-focus"
                        >
                            {{ __('Add New Address') }}
                        </a>
                    </x-slot>
                </x-ui.empty>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($addresses as $savedAddress)
                        <x-ui.card>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-semibold text-app">{{ $savedAddress->label ?: __('Saved Address') }}</p>
                                        @if ($savedAddress->is_default)
                                            <span class="inline-flex rounded-full bg-[var(--primary)] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white">{{ __('Default') }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-muted">
                                        {{ $savedAddress->address_line1 }}<br>
                                        @if ($savedAddress->address_line2)
                                            {{ $savedAddress->address_line2 }}<br>
                                        @endif
                                        {{ $savedAddress->city }}, {{ $savedAddress->country }}
                                    </p>
                                </div>
                                <a href="{{ route('account.addresses.edit', $savedAddress) }}" class="text-sm font-medium text-[var(--primary)] transition duration-150 hover:opacity-80">
                                    {{ __('Edit') }}
                                </a>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @unless ($savedAddress->is_default)
                                    <form method="POST" action="{{ route('account.addresses.default', $savedAddress) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Make Default') }}</x-ui.button>
                                    </form>
                                @endunless

                                <form method="POST" action="{{ route('account.addresses.destroy', $savedAddress) }}" data-confirm="{{ __('Delete this address?') }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                                </form>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end">
                <a
                    href="{{ route('account.addresses.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition duration-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-primary/30"
                >
                    {{ __('Open Full Address Manager') }}
                </a>
            </div>
        </div>
    </div>
@endsection
