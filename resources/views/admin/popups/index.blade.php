<x-app-layout>
    <x-slot name="header">{{ __('Popups') }}</x-slot>

    <style>
        .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
        .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
        .popup-preview-scrim { background: linear-gradient(180deg, transparent 40%, rgba(6,12,28,.88) 100%); }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═════════════ Hero ═════════════ --}}
        <div
            data-animate="fade-up"
            class="relative overflow-hidden rounded-2xl mb-5 p-6 text-white"
            style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);"
        >
            <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
            <div class="absolute top-0 bottom-0 left-0 w-[3px]" style="background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);"></div>
            <div class="absolute -top-16 -right-16 h-64 w-64 rounded-full bg-amber-400/10 blur-[60px] pointer-events-none"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="font-mono text-[10px] font-extrabold uppercase tracking-[0.28em] text-amber-300">{{ __('Marketing · Announcements') }}</div>
                    <h1 class="text-2xl font-black mt-2 leading-tight">{{ __('Popups') }}</h1>
                    <p class="text-sm text-white/65 mt-1.5">{{ __('Promotional and informational popups shown on the storefront.') }}</p>
                </div>
                <a href="{{ route('admin.popups.create') }}"
                   class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-xs font-bold text-[#04042a] shadow-md shadow-amber-500/30 transition hover:brightness-105"
                   style="background: linear-gradient(180deg, #fbbf24, #f59e0b);">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    {{ __('New Popup') }}
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if($popups->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-10 text-center bento-shadow">
                <div class="mx-auto h-12 w-12 rounded-2xl bg-[#04042a] text-amber-300 grid place-items-center mb-4">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('No popups yet') }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">{{ __('Create your first popup to announce campaigns on the storefront.') }}</p>
            </div>
        @else
            {{-- ═════════════ Preview gallery ═════════════ --}}
            <div data-animate-stagger="fade-up" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach($popups as $popup)
                    @php
                        $state = $popup->scheduleState();
                        $statePill = match ($state) {
                            'running' => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', 'bg-emerald-500'],
                            'scheduled' => ['bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', 'bg-sky-500'],
                            'expired' => ['bg-slate-200 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300', 'bg-slate-400'],
                            default => ['bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300', 'bg-rose-500'],
                        };
                        $stateLabel = match ($state) {
                            'running' => __('Live'),
                            'scheduled' => __('Scheduled'),
                            'expired' => __('Expired'),
                            default => __('Inactive'),
                        };
                        $pageLabels = collect(is_array($popup->pages) ? $popup->pages : [])->map(fn ($p) => match ($p) {
                            'all' => __('All pages'),
                            'home' => __('Home'),
                            'shop' => __('Shop'),
                            'product' => __('Product detail'),
                            'cart' => __('Cart'),
                            'checkout' => __('Checkout'),
                            default => $p,
                        })->implode(', ');
                        $frequencyLabel = match ($popup->frequency) {
                            'every_visit' => __('Every visit'),
                            'once_per_session' => __('Once per session'),
                            default => __('Every :days days', ['days' => $popup->frequency_days]),
                        };
                        $imageUrl = ! empty($popup->image_path) ? asset('storage/' . ltrim($popup->image_path, '/')) : null;
                        $description = $popup->localizedDescription();
                    @endphp
                    <div data-motion-lift class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl overflow-hidden bento-shadow {{ $state === 'running' ? '' : 'opacity-90' }}">

                        {{-- Mini live preview — mirrors the real storefront poster card --}}
                        <div class="relative h-44 flex items-end overflow-hidden" style="background: linear-gradient(160deg, #0a1533 0%, #1a2f5f 45%, #35558f 100%);">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="" class="absolute inset-0 h-full w-full object-contain">
                            @endif
                            <div class="absolute inset-0 popup-preview-scrim"></div>
                            <div class="relative px-4 pb-4 pt-2 text-white">
                                <div class="font-bold leading-tight line-clamp-2" style="font-family: 'Space Grotesk', sans-serif;">{{ $popup->localizedTitle() }}</div>
                                @if($description)
                                    <p class="text-[11px] text-white/75 mt-1 line-clamp-1">{{ $description }}</p>
                                @endif
                                @if($popup->hasButton())
                                    <span class="inline-block mt-2 rounded-full bg-[#e85d2a] px-3 py-1 text-[11px] font-bold text-white">{{ $popup->localizedButtonLabel() }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statePill[0] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statePill[1] }}"></span>
                                    {{ $stateLabel }}
                                </span>
                                <form method="POST" action="{{ route('admin.popups.toggle', $popup) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            aria-pressed="{{ $popup->is_active ? 'true' : 'false' }}"
                                            aria-label="{{ $popup->is_active ? __('Deactivate') : __('Activate') }}"
                                            class="relative inline-flex h-[18px] w-[32px] items-center rounded-full transition-colors {{ $popup->is_active ? 'bg-amber-400' : 'bg-slate-300 dark:bg-slate-600' }}">
                                        <span class="inline-block h-[14px] w-[14px] rounded-full bg-white shadow transition-transform {{ $popup->is_active ? 'translate-x-[15px]' : 'translate-x-[2px]' }}"></span>
                                    </button>
                                </form>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-[11px] text-slate-500 dark:text-slate-400 mb-3">
                                <div>
                                    <div class="uppercase tracking-wide font-bold text-[9.5px]">{{ __('Show On Pages') }}</div>
                                    <div class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 truncate" title="{{ $pageLabels }}">{{ $pageLabels }}</div>
                                </div>
                                <div>
                                    <div class="uppercase tracking-wide font-bold text-[9.5px]">{{ __('Display Frequency') }}</div>
                                    <div class="font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ $frequencyLabel }}</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                    @if($popup->starts_at || $popup->ends_at)
                                        {{ $popup->starts_at?->format('d M Y') ?? '—' }} → {{ $popup->ends_at?->format('d M Y') ?? '∞' }}
                                    @else
                                        {{ __('Always on') }}
                                    @endif
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.popups.edit', $popup) }}"
                                       aria-label="{{ __('Edit') }}"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 bg-slate-50 border border-slate-200 hover:text-slate-800 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700 dark:hover:text-slate-100 transition">
                                        <i class="fas fa-pen text-[11px]"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.popups.destroy', $popup) }}"
                                          data-danger-confirm
                                          data-danger-title="{{ __('Delete Popup') }}"
                                          data-danger-description="{{ __('This action is permanent. The selected popup will be deleted and cannot be recovered.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                aria-label="{{ __('Delete') }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-500 bg-rose-50 border border-rose-200 hover:bg-rose-100 dark:bg-rose-500/10 dark:border-rose-500/30 transition">
                                            <i class="fas fa-trash-can text-[11px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($popups->hasPages())
                <div class="mt-5">{{ $popups->links() }}</div>
            @endif
        @endif

    </div>
    </div>
    </div>
</x-app-layout>
