<x-app-layout>
    <x-slot name="header">{{ __('Create Category') }}</x-slot>

    <style>
        .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═════════════ Hero ═════════════ --}}
        <div class="admin-theme-panel relative overflow-hidden rounded-2xl mb-4 p-6 text-white">
            <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
            <div class="absolute top-0 bottom-0 left-0 w-[3px]" style="background: linear-gradient(180deg, #ff8a3d 0%, #e65c00 100%);"></div>
            <div class="absolute -top-16 -right-16 h-64 w-64 rounded-full bg-accent/10 blur-[60px] pointer-events-none"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="font-mono text-[10px] font-bold uppercase tracking-[0.28em] text-accent">{{ __('Catalog · Taxonomy') }}</div>
                    <h1 class="text-2xl font-bold mt-2 leading-tight">{{ __('Create Category') }}</h1>
                    <p class="text-sm text-white/65 mt-1.5">{{ __('Add a new category to the catalog.') }}</p>
                </div>
                <a href="{{ route('admin.categories.index') }}"
                   class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-bold text-white bg-white/10 border border-white/15 hover:bg-white/15 backdrop-blur-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to categories') }}
                </a>
            </div>
        </div>

        @include('admin.categories._form')

    </div>
    </div>
    </div>
</x-app-layout>
