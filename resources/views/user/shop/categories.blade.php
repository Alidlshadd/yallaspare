@extends('layouts.user')

@section('title', __('Categories'))
@section('meta_description', __('Browse all product categories and find the right spare parts faster.'))

@section('content')
    <div class="space-y-7">
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
            <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end lg:p-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-600 dark:text-orange-300">{{ __('Catalog') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Categories') }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ __('Browse by product system, compare available groups, and open the category you need.') }}
                    </p>
                </div>

                <a
                    href="{{ route('shop.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-[#070740] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0a0a55] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#070740]/20"
                >
                    {{ __('View all products') }}
                </a>
            </div>
        </section>

        <section>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($categories as $category)
                    @php
                        $imagePath = $hasCategoryImage ? trim((string) $category->image) : '';
                        $imageUrl = $imagePath !== '' ? asset('storage/' . ltrim($imagePath, '/')) : null;
                    @endphp

                    <a
                        href="{{ route('shop.index', ['category' => $category->slug ?: $category->id]) }}"
                        class="group flex h-full min-h-72 flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 transition duration-200 hover:-translate-y-0.5 hover:border-[#070740]/20 hover:shadow-md hover:shadow-slate-900/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 dark:hover:border-[#070740]/30 dark:hover:shadow-black/20"
                    >
                        <div class="relative flex h-44 items-center justify-center overflow-hidden bg-slate-100 p-3 text-[#070740] dark:bg-slate-800 dark:text-slate-200">
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $category->name }}" class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.04]" loading="lazy">
                            @else
                                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6M9 14h4" />
                                </svg>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div>
                                <h2 class="line-clamp-2 text-base font-semibold text-slate-950 transition group-hover:text-[#070740] dark:text-white dark:group-hover:text-slate-200">
                                    {{ $category->name }}
                                </h2>
                            </div>

                            @if (filled($category->localized_description))
                                <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $category->localized_description }}</p>
                            @endif

                            <span class="mt-auto pt-5 text-sm font-semibold text-[#070740] dark:text-slate-200">
                                {{ __('Open category') }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
                        <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ __('No categories found.') }}</h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Create categories in admin to show them here.') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($categories->hasPages())
                <div class="mt-5 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    {{ $categories->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
