@props([
    'title',
    'subtitle' => null,
])

@php
    $locale = app()->getLocale();
    $isRtl = str_starts_with($locale, 'ar') || str_starts_with($locale, 'ku');
    $actionContent = isset($actions) ? trim((string) $actions) : '';
    $brand = (string) ($systemSettings['site_name'] ?? 'YallaSpare');
    $brandLogoUrl = $systemSettings['site_logo_url'] ?? null;
    $authUser = auth()->user();
    $userInitial = strtoupper(substr((string) ($authUser->name ?? 'U'), 0, 1));
    $userProfilePhotoUrl = !empty($authUser?->profile_photo_path)
        ? asset('storage/' . ltrim((string) $authUser->profile_photo_path, '/'))
        : null;
@endphp

<header class="sticky top-0 z-40">
    <div class="bg-[linear-gradient(180deg,#070740_0%,#0a0d3f_100%)] text-white shadow-sm">
        <div class="mx-auto grid h-16 max-w-6xl grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 sm:px-6 lg:px-8">
            <div></div>

            <a href="{{ route('user.shop.home') }}" class="app-logo app-logo-dark app-logo-user focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25">
                <x-brand-mark
                    :logo-url="$brandLogoUrl"
                    :brand="$brand"
                    wrapper-class="app-logo-mark logo-remove-white-bg"
                    img-class="h-full w-auto object-contain"
                    fallback-class="inline-flex h-full w-full items-center justify-center"
                    fallback-text-class="text-[11px] font-semibold tracking-[0.18em] text-white"
                />
                <span class="app-logo-text">{{ $brand }}</span>
            </a>

            <div class="justify-self-end">
                <div class="flex items-center gap-2">
                    <x-language-switcher variant="dark" />

                <div class="relative">
                    <button
                        type="button"
                        class="inline-flex h-10 items-center gap-3 rounded-xl border border-white/10 bg-white/10 px-3 text-sm font-medium text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25"
                        @click="accountOpen = !accountOpen"
                        :aria-expanded="accountOpen.toString()"
                        aria-haspopup="menu"
                    >
                        @if($userProfilePhotoUrl)
                            <img src="{{ $userProfilePhotoUrl }}" alt="{{ $authUser->name ?? 'User' }} profile photo" class="h-7 w-7 rounded-full object-cover border border-white/30">
                        @else
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-[#070740]">
                                {{ $userInitial }}
                            </span>
                        @endif
                        <span class="hidden sm:block">{{ auth()->user()->name ?? __('Account') }}</span>
                        <svg class="h-4 w-4 text-white/65" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.126l3.71-3.895a.75.75 0 1 1 1.08 1.04l-4.25 4.46a.75.75 0 0 1-1.08 0l-4.25-4.46a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div
                        x-cloak
                        x-show="accountOpen"
                        x-transition
                        @click.outside="accountOpen = false"
                        class="absolute {{ $isRtl ? 'left-0' : 'right-0' }} top-full z-50 mt-3 w-56 overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-2 text-slate-900 shadow-2xl shadow-slate-900/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:shadow-black/30"
                        role="menu"
                    >
                        <div class="rounded-2xl border border-slate-100 px-4 py-3 dark:border-slate-800">
                            <p class="truncate text-sm font-semibold">{{ auth()->user()->name ?? __('User') }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email ?? '' }}</p>
                        </div>
                        <div class="mt-2 space-y-1">
                            <a href="{{ route('user.account.edit') }}" class="flex rounded-2xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:hover:bg-slate-900 dark:hover:text-white dark:focus-visible:ring-[#070740]/30" role="menuitem">
                                {{ __('Profile') }}
                            </a>
                            <a href="{{ route('user.settings.edit') }}" class="flex rounded-2xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:hover:bg-slate-900 dark:hover:text-white dark:focus-visible:ring-[#070740]/30" role="menuitem">
                                {{ __('Settings') }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full rounded-2xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:hover:bg-slate-900 dark:hover:text-white dark:focus-visible:ring-[#070740]/30" role="menuitem">
                                    {{ __('Logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-6xl items-center justify-center gap-2 px-4 py-3 sm:px-6 lg:px-8">
                <a
                    href="{{ route('user.shop.home') }}"
                    class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-medium transition duration-200 {{ request()->routeIs('user.shop.*') || request()->routeIs('shop.index') || request()->routeIs('cart.*') ? 'bg-white text-[#070740]' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                >
                    {{ __('Home') }}
                </a>
                <a
                    href="{{ route('user.account.edit') }}"
                    class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-medium transition duration-200 {{ request()->routeIs('user.account.*') || request()->routeIs('account.*') ? 'bg-white text-[#070740]' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                >
                    {{ __('Profile') }}
                </a>
                <a
                    href="{{ route('user.settings.edit') }}"
                    class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-medium transition duration-200 {{ request()->routeIs('user.settings.*') ? 'bg-white text-[#070740]' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                >
                    {{ __('Settings') }}
                </a>
            </div>
        </div>
    </div>

    <div class="border-b border-slate-200/60 bg-white dark:border-slate-800 dark:bg-slate-950">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-[-0.03em] text-slate-900 dark:text-white sm:text-3xl">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $subtitle }}</p>
                @endif
            </div>

            @if ($actionContent !== '')
                <div class="shrink-0">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</header>
