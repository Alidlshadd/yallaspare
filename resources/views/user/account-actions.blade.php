@extends('layouts.user')

@section('title', __('Account Actions'))
@section('subtitle', __('Sensitive operations and account-level requests'))
@section('actions')
    <a
        href="{{ route('user.account.edit') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    >
        {{ __('Account') }}
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            @include('user.partials.account-nav')
        </aside>

        <div class="space-y-6">
            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <h2 class="text-base font-semibold text-app">{{ __('Requests') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('These actions are prepared as dedicated account workflows.') }}</p>
                    </div>
                </x-slot>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <a href="mailto:support@yallaspare.com?subject=Freeze%20Account%20Request" class="rounded-app border border-app bg-surface-1 px-4 py-4 transition duration-150 hover:bg-[var(--surface-2)]">
                        <p class="text-sm font-semibold text-app">{{ __('Freeze Account') }}</p>
                        <p class="mt-1 text-sm text-muted">{{ __('Temporary access restriction request routed to support.') }}</p>
                    </a>
                    <a href="mailto:support@yallaspare.com?subject=Data%20Export%20Request" class="rounded-app border border-app bg-surface-1 px-4 py-4 transition duration-150 hover:bg-[var(--surface-2)]">
                        <p class="text-sm font-semibold text-app">{{ __('Request My Data') }}</p>
                        <p class="mt-1 text-sm text-muted">{{ __('Ask for a copy of your data before major account changes.') }}</p>
                    </a>
                </div>
            </x-ui.card>

            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <h2 class="text-base font-semibold text-app">{{ __('user.danger_zone') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('Deleting your account is permanent and cannot be undone.') }}</p>
                    </div>
                </x-slot>

                <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4 rounded-app border border-[var(--danger)]/30 bg-[rgba(180,35,24,0.04)] p-4">
                    @csrf
                    @method('DELETE')

                    <x-ui.input
                        name="password"
                        type="password"
                        label="Confirm password to delete account"
                        :error="$errors->userDeletion->first('password')"
                    />

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <a href="mailto:support@yallaspare.com?subject=Data%20Export%20Before%20Account%20Deletion" class="inline-flex h-10 items-center justify-center rounded-app border border-[var(--border)] bg-[var(--surface-2)] px-4 text-sm font-medium text-[var(--text)] transition duration-150 hover:bg-[var(--surface-1)] focus:outline-none focus:ring-4 ring-focus">
                            {{ __('Export Data First') }}
                        </a>
                        <x-ui.button type="submit" variant="danger">
                            {{ __('Delete Account') }}
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
@endsection
