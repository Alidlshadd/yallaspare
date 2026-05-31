@extends('layouts.user')

@section('title', __('Communication'))
@section('subtitle', __('Choose how messages, alerts, and marketing updates should reach you'))
@section('actions')
    <a
        href="{{ route('user.settings.edit') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
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
                    <h2 class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Communication Preferences') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Keep operational alerts enabled where you want them and separate them from marketing consent.') }}</p>
                </div>

                <form action="{{ route('user.settings.communication.update') }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    @method('PATCH')

                    @foreach ([
                        ['name' => 'email_notifications', 'title' => __('Email Notifications'), 'description' => __('Receive order and account messages by email.')],
                        ['name' => 'sms_notifications', 'title' => __('SMS Notifications'), 'description' => __('Receive short delivery and order alerts by text message.')],
                        ['name' => 'whatsapp_notifications', 'title' => __('WhatsApp Notifications'), 'description' => __('Allow WhatsApp updates for delivery coordination and status changes.')],
                        ['name' => 'marketing_consent', 'title' => __('Marketing Consent'), 'description' => __('Allow promotional offers and campaign announcements.')],
                    ] as $item)
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition duration-200 hover:border-primary/20 hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:hover:border-primary/30 dark:hover:bg-slate-900">
                            <input type="checkbox" name="{{ $item['name'] }}" value="1" @checked(old($item['name'], $user->{$item['name']} ?? false)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30">
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-slate-900 dark:text-white">{{ $item['title'] }}</span>
                                <span class="mt-1 block text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $item['description'] }}</span>
                            </span>
                        </label>
                    @endforeach

                    <div class="flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:opacity-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                            {{ __('Save Communication') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
