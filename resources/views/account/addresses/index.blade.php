@extends('layouts.user')

@section('title', __('Address Book'))
@section('subtitle', __('Store delivery destinations for faster checkout and cleaner order handling'))
@section('actions')
    <div class="flex flex-wrap items-center justify-end gap-3">
        <a
            href="{{ route('user.account.edit') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            {{ __('Account') }}
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </a>
        <a
            href="{{ route('account.addresses.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#070740] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:opacity-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2"
        >
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 4.25a.75.75 0 0 1 .75.75v4.25H15a.75.75 0 0 1 0 1.5h-4.25V15a.75.75 0 0 1-1.5 0v-4.25H5a.75.75 0 0 1 0-1.5h4.25V5a.75.75 0 0 1 .75-.75Z" />
            </svg>
            {{ __('Add Address') }}
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
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Store delivery destinations for faster checkout and cleaner order handling') }}</p>
                </div>
                <a
                    href="{{ route('account.addresses.create') }}"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#070740] px-4 text-sm font-semibold text-white transition duration-200 hover:bg-[#10106a] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 4.25a.75.75 0 0 1 .75.75v4.25H15a.75.75 0 0 1 0 1.5h-4.25V15a.75.75 0 0 1-1.5 0v-4.25H5a.75.75 0 0 1 0-1.5h4.25V5a.75.75 0 0 1 .75-.75Z" />
                    </svg>
                    {{ __('Add Address') }}
                </a>
            </div>

            @if (session('status'))
                <x-ui.alert variant="success" :title="__('Success')">
                    {{ session('status') }}
                </x-ui.alert>
            @endif

            @if ($addresses->isEmpty())
                <section class="rounded-2xl border border-slate-200/60 bg-white p-10 text-center shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-[#070740] dark:bg-slate-800 dark:text-slate-200">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.35 7-10a7 7 0 1 0-14 0c0 5.65 7 10 7 10Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                        </svg>
                    </div>
                    <h2 class="mt-5 text-2xl font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('No saved addresses yet') }}</h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-300">{{ __('Add your first address to speed up checkout and keep customer delivery details ready for future orders.') }}</p>
                </section>
            @else
                <div class="space-y-4">
                    @foreach ($addresses as $address)
                        <article class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ $address->label ?: __('Saved Address') }}</h2>
                                        @if ($address->is_default)
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ __('Default') }}</span>
                                        @endif
                                    </div>

                                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                        {{ $address->address_line1 }}<br>
                                        @if ($address->address_line2)
                                            {{ $address->address_line2 }}<br>
                                        @endif
                                        {{ $address->city }}, {{ $address->country }}
                                    </p>

                                    @if ($address->phone || $address->notes)
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            @if ($address->phone)
                                                <div class="rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Phone') }}</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ $address->phone }}</p>
                                                </div>
                                            @endif
                                            @if ($address->notes)
                                                <div class="rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Notes') }}</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ $address->notes }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                    @unless ($address->is_default)
                                        <form method="POST" action="{{ route('account.addresses.default', $address) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                                {{ __('Make Default') }}
                                            </button>
                                        </form>
                                    @endunless

                                    <a href="{{ route('account.addresses.edit', $address) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                        {{ __('Edit') }}
                                    </a>

                                    <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm('Delete this address?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-medium text-rose-700 transition duration-200 hover:bg-rose-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-2 dark:border-rose-900/40 dark:bg-rose-900/20 dark:text-rose-300">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
