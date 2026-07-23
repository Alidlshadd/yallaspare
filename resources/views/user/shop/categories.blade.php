@extends('layouts.user')

@section('title', __('Categories'))
@section('meta_description', __('Browse all product categories and find the right spare parts faster.'))

@section('content')
    <div class="space-y-7">
        <section class="relative overflow-hidden rounded-3xl bg-primary p-6 text-white sm:p-8 lg:p-10">
            <div class="pointer-events-none absolute -top-16 end-[-4rem] h-56 w-56 rounded-full bg-amber-400/20 blur-3xl" aria-hidden="true"></div>

            <div class="relative">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-400">{{ __('Catalog') }}</p>
                <h1 class="mt-2 max-w-xl text-2xl font-bold tracking-[-0.02em] sm:text-3xl lg:text-[34px]">
                    {{ __('Browse every category') }}
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/75">
                    {{ __('Compare available groups and open the category you need, all in one place.') }}
                </p>

                <div class="mt-6 flex max-w-md items-center gap-2.5 rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm" data-category-search>
                    <svg class="h-4 w-4 shrink-0 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path stroke-linecap="round" d="m21 21-4.3-4.3" />
                    </svg>
                    <input
                        type="text"
                        data-category-search-input
                        placeholder="{{ __('Search categories…') }}"
                        class="w-full bg-transparent text-sm text-white placeholder-white/50 outline-none"
                        aria-label="{{ __('Search categories') }}"
                    >
                </div>

                <div class="mt-7 flex flex-wrap gap-8">
                    <div>
                        <p class="text-2xl font-bold tracking-[-0.02em]">{{ number_format($categories->total()) }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-white/60">{{ __('Categories') }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold tracking-[-0.02em]">{{ number_format($totalActiveProducts) }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-white/60">{{ __('Parts listed') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-category-grid>
                @forelse ($categories as $category)
                    @php
                        $imagePath = $hasCategoryImage ? trim((string) $category->image) : '';
                        $imageUrl = $imagePath !== '' ? asset('storage/' . ltrim($imagePath, '/')) : null;
                    @endphp

                    <a
                        href="{{ route('shop.index', ['category' => $category->slug ?: $category->id]) }}"
                        class="group relative flex h-56 flex-col overflow-hidden rounded-3xl border border-slate-200/80 shadow-sm shadow-slate-900/5 transition duration-200 hover:shadow-lg hover:shadow-slate-900/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:ring-offset-2 dark:border-slate-800 dark:shadow-black/10"
                        data-category-card
                        data-category-name="{{ Str::lower($category->name) }}"
                        data-category-description="{{ Str::lower((string) $category->localized_description) }}"
                    >
                        <div class="absolute inset-0 flex items-center justify-center bg-slate-100 text-primary dark:bg-slate-800 dark:text-slate-200">
                            @if ($imageUrl)
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="{{ $category->name }}"
                                    class="h-full w-full object-contain p-8 transition duration-300 group-hover:scale-[1.06]"
                                    loading="lazy"
                                >
                            @else
                                <svg class="h-12 w-12 transition duration-300 group-hover:scale-[1.06]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6M9 14h4" />
                                </svg>
                            @endif
                        </div>

                        @if ((int) $category->active_products_count > 0)
                            <span class="absolute right-3 top-3 z-10 inline-flex rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold text-primary shadow-sm dark:bg-slate-900/90 dark:text-slate-200">
                                {{ trans_choice(':count part|:count parts', $category->active_products_count, ['count' => number_format($category->active_products_count)]) }}
                            </span>
                        @endif

                        <div class="absolute inset-x-0 bottom-0 flex flex-col-reverse bg-gradient-to-t from-primary via-primary/95 to-transparent px-5 pb-4 pt-9">
                            <p class="text-base font-semibold leading-snug text-white">{{ $category->name }}</p>
                            @if (filled($category->localized_description))
                                <p class="mb-0 line-clamp-2 max-h-0 overflow-hidden text-xs leading-5 text-white/80 opacity-0 transition-all duration-200 group-hover:mb-2 group-hover:max-h-10 group-hover:opacity-100 group-focus-visible:mb-2 group-focus-visible:max-h-10 group-focus-visible:opacity-100">
                                    {{ $category->localized_description }}
                                </p>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900 sm:col-span-2 lg:col-span-3 xl:col-span-4">
                        <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ __('No categories found.') }}</h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Create categories in admin to show them here.') }}</p>
                    </div>
                @endforelse
            </div>

            <p class="hidden py-10 text-center text-sm font-medium text-slate-500 dark:text-slate-400" data-category-empty>
                {{ __('No categories match your search.') }}
            </p>

            @if ($categories->hasPages())
                <div class="mt-5 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    {{ $categories->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
