@php
    use App\Support\SocialProviders;

    $socialProviders = array_values(array_filter([
        [
            'key' => 'google',
            'label' => __('Continue with Google'),
            'href' => route('auth.google.redirect'),
            'enabled' => SocialProviders::googleEnabled(),
        ],
        [
            'key' => 'apple',
            'label' => __('Continue with Apple'),
            'href' => route('auth.apple.redirect'),
            'enabled' => SocialProviders::appleEnabled(),
        ],
    ], fn (array $provider): bool => $provider['enabled']));

    $buttonBase = 'group inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-sm shadow-slate-900/5 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100 dark:shadow-black/20';
    $buttonActive = 'transition-all duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md hover:shadow-slate-900/10 active:translate-y-0 active:scale-[0.98] motion-reduce:transform-none motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:hover:shadow-black/30 dark:focus-visible:ring-offset-slate-900';
@endphp

@if ($socialProviders !== [])
    <div class="mt-5">
        <div class="relative flex items-center">
            <div class="h-px flex-1 bg-slate-700/80"></div>
            <span class="px-3 text-xs font-medium uppercase tracking-[0.18em] text-slate-500">{{ __('or') }}</span>
            <div class="h-px flex-1 bg-slate-700/80"></div>
        </div>

        <div class="mt-4 grid gap-3 {{ count($socialProviders) > 1 ? 'sm:grid-cols-2' : '' }}">
            @foreach ($socialProviders as $provider)
                <a
                    href="{{ $provider['href'] }}"
                    class="{{ $buttonBase }} {{ $buttonActive }} {{ $provider['key'] === 'google' ? 'hover:border-red-200 hover:bg-red-50 dark:hover:border-slate-500 dark:hover:bg-slate-800' : 'hover:border-slate-400 hover:bg-slate-50 dark:hover:border-slate-500 dark:hover:bg-slate-800' }}"
                    aria-label="{{ $provider['label'] }}"
                >
                    <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center transition-transform duration-200 ease-out group-hover:scale-110 motion-reduce:transform-none" aria-hidden="true">
                        @if ($provider['key'] === 'google')
                            <svg viewBox="0 0 24 24" class="h-5 w-5" role="img">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.24 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06L5.84 9.9C6.71 7.31 9.14 5.38 12 5.38z"/>
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-slate-950 dark:fill-white" role="img">
                                <path d="M16.37 1.43c0 1.02-.39 1.97-1.11 2.76-.75.82-1.98 1.45-2.96 1.36-.13-.98.36-2.02 1.02-2.78.73-.84 2.01-1.47 3.05-1.34z"/>
                                <path d="M20.8 17.23c-.51 1.17-.75 1.69-1.4 2.72-.91 1.42-2.19 3.19-3.78 3.2-1.41.01-1.78-.93-3.7-.92-1.93.01-2.33.94-3.74.93-1.59-.02-2.8-1.61-3.71-3.03-2.54-3.96-2.8-8.61-1.24-11.08 1.11-1.76 2.86-2.79 4.5-2.79 1.67 0 2.72.92 4.1.92 1.34 0 2.16-.92 4.09-.92 1.46 0 3 .8 4.1 2.18-3.6 1.98-3.01 7.12.78 8.79z"/>
                            </svg>
                        @endif
                    </span>
                    <span class="min-w-0 truncate text-slate-700 dark:text-slate-100">{{ $provider['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
