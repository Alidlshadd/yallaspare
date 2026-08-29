{{-- An account opened during express checkout has no email and no password:
     nobody has ever chosen a way into it. This offers one. It disappears the
     moment either is set, so a normal account never sees it, and the usual
     password screen stays where it is — that one asks for a current password,
     which is exactly what is missing here. --}}

@if (session('credentials_saved'))
    <x-ui.alert variant="success" :title="__('Account Ready')">
        {{ session('credentials_saved') }}
    </x-ui.alert>
@endif

@if (auth()->user()?->password === null && auth()->user()?->email === null)
    <section class="rounded-3xl border border-accent/40 bg-accent/[0.06] p-6 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
        <h2 class="text-xl font-semibold text-slate-950">{{ __('Keep your account') }}</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
            {{ __('We created an account for this order so you can follow it. Choose a password to sign back in with your phone number later. This is optional.') }}
        </p>

        <form method="POST" action="{{ route('account.credentials.store') }}" class="mt-5 space-y-4">
            @csrf

            <div>
                <label for="claim_email" class="block text-sm font-medium text-slate-700">
                    {{ __('Email') }} <span class="font-normal text-slate-500">({{ __('optional') }})</span>
                </label>
                <input
                    id="claim_email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder-muted focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $errors->has('email') ? 'border-rose-300' : 'border-slate-200' }}"
                >
                @error('email')
                    <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="claim_password" class="block text-sm font-medium text-slate-700">{{ __('Password') }}</label>
                    <input
                        id="claim_password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $errors->has('password') ? 'border-rose-300' : 'border-slate-200' }}"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="claim_password_confirmation" class="block text-sm font-medium text-slate-700">{{ __('Confirm Password') }}</label>
                    <input
                        id="claim_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                    >
                </div>
            </div>

            <button
                type="submit"
                class="font-display inline-flex items-center justify-center rounded-2xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2"
            >
                {{ __('Save password') }}
            </button>
        </form>
    </section>
@endif
