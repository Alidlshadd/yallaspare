<x-app-layout>
    <x-slot name="header">{{ __('Edit Category') }}</x-slot>

    <style>
        .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
    </style>

    @php
        $linkedProducts = $category->products()->count();
    @endphp

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═════════════ Hero ═════════════ --}}
        <div class="relative overflow-hidden rounded-2xl mb-4 p-6 text-white"
             style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
            <div class="absolute top-0 bottom-0 left-0 w-[3px]" style="background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);"></div>
            <div class="absolute -top-16 -right-16 h-64 w-64 rounded-full bg-amber-400/10 blur-[60px] pointer-events-none"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="font-mono text-[10px] font-extrabold uppercase tracking-[0.28em] text-amber-300">{{ __('Catalog · Taxonomy') }}</div>
                    <h1 class="text-2xl font-black mt-2 leading-tight">{{ __('Edit Category') }}</h1>
                    <p class="text-sm text-white/65 mt-1.5">
                        {{ $category->name }}
                        <span class="text-white/35 mx-1">·</span>
                        @if($linkedProducts > 0)
                            <a href="{{ route('admin.products.index', ['category_id' => $category->id]) }}"
                               class="font-bold text-amber-300 hover:text-amber-200 underline decoration-amber-300/40 underline-offset-2 transition">
                                {{ trans_choice(':count product|:count products', $linkedProducts, ['count' => number_format($linkedProducts)]) }}
                            </a>
                            {{ __('linked') }}
                        @else
                            <span class="text-white/50">{{ __('No products linked') }}</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('admin.categories.index') }}"
                   class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-xs font-bold text-white bg-white/10 border border-white/15 hover:bg-white/15 backdrop-blur-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to categories') }}
                </a>
            </div>
        </div>

        @include('admin.categories._form', ['category' => $category])

    </div>
    </div>
    </div>
</x-app-layout>
