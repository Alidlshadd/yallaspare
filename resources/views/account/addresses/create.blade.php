@extends('layouts.user')

@section('title', __('Add Address'))
@section('subtitle', __('Create a delivery address for faster checkout'))
@section('actions')
    <a
        href="{{ route('account.addresses.index') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 dark:bg-slate-900 dark:hover:bg-slate-800"
    >
        {{-- The link out was already here, but pointing forward and named
             after its destination, so it read as another step rather than the
             way back to the list. Same target, back-facing now. --}}
        <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M12.78 4.97a.75.75 0 0 1 0 1.06L9.06 9.75l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
        </svg>
        {{ __('Back to Address Book') }}
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            @include('user.partials.account-nav')
        </aside>

        <div class="space-y-6">
            <div class="mx-auto w-full max-w-3xl">
                <section class="rounded-2xl border border-slate-200/60 bg-white p-8 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
                    @include('account.addresses._form', ['address' => $address])
                </section>
            </div>
        </div>
    </div>
@endsection
