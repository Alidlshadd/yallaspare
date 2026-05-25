<x-auth-split-layout
    :heading="__('Verify Email')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Check Your Inbox')"
    :panel-subtitle="__('Verify your email address to unlock your cart, checkout, account settings, and order history.')"
    :panel-tag="__('Email Required')"
    panel-theme="login"
    :panel-button-text="__('Verification Required')"
    panel-button-action="none"
>
    @php
        $verificationEmail = auth()->user()?->email;
    @endphp

    <div class="mt-5 space-y-5">
        @if (session('status') == 'verification-link-sent')
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-200">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-600 text-white shadow-lg shadow-red-950/30">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75h14.5v10.5H4.75z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 7.25 6.05 5.1a1.1 1.1 0 0 0 1.4 0l6.05-5.1" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('One more step') }}</p>
                <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('We sent a verification link to your email. Open it to activate full access to your account.') }}
                </p>
                @if ($verificationEmail)
                    <p class="mt-3 break-words text-sm font-semibold text-[#070740] dark:text-red-200">
                        {{ $verificationEmail }}
                    </p>
                @endif
            </div>
        </div>

        <div class="border-t border-slate-200 pt-5 dark:border-white/10">
            <div class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-center">
                <form method="POST" action="{{ route('verification.send') }}" data-auth-form>
                    @csrf

                    <button
                        type="submit"
                        class="pointer-events-auto inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-950/30 transition duration-200 hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-900 sm:w-auto"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 12a7.25 7.25 0 0 1 12.42-5.08L19.25 9" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.25 5.75V9h-3.25" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.25 12a7.25 7.25 0 0 1-12.42 5.08L4.75 15" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 18.25V15H8" />
                        </svg>
                        {{ __('Resend Verification Email') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition duration-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/10 dark:hover:text-white sm:w-auto"
                    >
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>

        <p class="text-xs leading-5 text-slate-500 dark:text-slate-400">
            {{ __('If the email is not visible, check spam or request a fresh link. The newest link is the one you should use.') }}
        </p>
    </div>
</x-auth-split-layout>
