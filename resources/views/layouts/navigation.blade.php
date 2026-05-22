@php
    $dashboardRoute = Route::has('account.index')
        ? 'account.index'
        : (Route::has('admin.dashboard') ? 'admin.dashboard' : 'home');
@endphp
<nav x-data="{ open: false }" class="border-b border-slate-200/70 bg-white/95 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-4 sm:gap-8">
                <div class="flex shrink-0 items-center">
                    <div class="header-logo-area">
                    <a href="{{ route($dashboardRoute) }}" class="app-logo app-logo-light app-logo-user focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2">
                        <x-brand-mark
                            :logo-url="$systemSettings['site_logo_url'] ?? null"
                            :brand="$systemSettings['site_name'] ?? 'YallaSpare'"
                            wrapper-class="app-logo-mark rounded-lg"
                            img-class="h-full w-auto object-contain"
                            fallback-class="inline-flex h-full w-full items-center justify-center rounded-lg bg-[#070740]"
                            fallback-text-class="text-[11px] font-semibold tracking-[0.18em] text-white"
                        />
                        <span class="app-logo-text hidden sm:inline-flex">
                            {{ $systemSettings['site_name'] ?? 'YallaSpare' }}
                        </span>
                    </a>
                    </div>
                </div>

                <div class="hidden items-center gap-2 sm:flex">
                    <a
                        href="{{ route('shop.index') }}"
                        class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium transition duration-200 {{ request()->routeIs('home') || request()->routeIs('shop.index') ? 'bg-slate-100 text-slate-950' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}"
                    >
                        {{ __('Shop') }}
                    </a>
                    <a
                        href="{{ route('cart.index') }}"
                        class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium transition duration-200 {{ request()->routeIs('cart.*') ? 'bg-slate-100 text-slate-950' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}"
                    >
                        {{ __('Cart') }}
                    </a>
                    <a
                        href="{{ route($dashboardRoute) }}"
                        class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium transition duration-200 {{ request()->routeIs($dashboardRoute) ? 'bg-slate-100 text-slate-950' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}"
                    >
                        {{ __('Account') }}
                    </a>
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                <x-language-switcher />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium leading-4 text-slate-700 shadow-sm transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#070740] text-[11px] font-semibold text-white">
                                {{ strtoupper(substr((string) Auth::user()->name, 0, 1)) }}
                            </span>
                            <div class="max-w-[140px] truncate">{{ Auth::user()->name }}</div>

                            <div>
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ Auth::user()->email }}</p>
                        </div>

                        <div class="py-1">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-500 transition duration-200 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-2 border-t border-slate-200 px-4 py-4">
            <a
                href="{{ route('shop.index') }}"
                class="flex rounded-2xl px-4 py-3 text-sm font-medium transition duration-200 {{ request()->routeIs('home') || request()->routeIs('shop.index') ? 'bg-slate-100 text-slate-950' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950' }}"
            >
                {{ __('Shop') }}
            </a>
            <a
                href="{{ route('cart.index') }}"
                class="flex rounded-2xl px-4 py-3 text-sm font-medium transition duration-200 {{ request()->routeIs('cart.*') ? 'bg-slate-100 text-slate-950' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950' }}"
            >
                {{ __('Cart') }}
            </a>
            <a
                href="{{ route($dashboardRoute) }}"
                class="flex rounded-2xl px-4 py-3 text-sm font-medium transition duration-200 {{ request()->routeIs($dashboardRoute) ? 'bg-slate-100 text-slate-950' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950' }}"
            >
                {{ __('Account') }}
            </a>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#070740] text-sm font-semibold text-white">
                        {{ strtoupper(substr((string) Auth::user()->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-2">
                <x-language-switcher />

                <a
                    href="{{ route('profile.edit') }}"
                    class="flex rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 hover:text-slate-950"
                >
                    {{ __('Profile') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <a href="{{ route('logout') }}"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();"
                            class="flex rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 hover:text-slate-950">
                        {{ __('Log Out') }}
                    </a>
                </form>
            </div>
        </div>
    </div>
</nav>
