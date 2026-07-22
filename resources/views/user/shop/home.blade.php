@extends('layouts.user')

@section('meta_description', __('YallaSpare is an auto spare parts platform built for Iraq, helping customers find trusted parts, check vehicle compatibility, order easily, and get reliable support.'))

@section('content')
    @php
        $heroVideoPath = trim((string) data_get($heroSettings ?? [], 'video', '')) ?: 'home/hero-video.mp4';
        $heroImagePath = trim((string) data_get($heroSettings ?? [], 'image', '')) ?: 'home/hero-banner.jpg';
        $heroVideoUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($heroVideoPath)
            ? asset('storage/' . $heroVideoPath)
            : null;
        $heroImageUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($heroImagePath)
            ? asset('storage/' . $heroImagePath)
            : null;
        $heroTitle = __(trim((string) data_get($heroSettings ?? [], 'title', '')) ?: 'Find the right spare parts faster');
        $heroSubtitle = __(trim((string) data_get($heroSettings ?? [], 'subtitle', '')) ?: 'Browse saved categories, filter by vehicle, and shop available parts from one clean catalog.');
        $heroButtonLabel = __(trim((string) data_get($heroSettings ?? [], 'button_label', '')) ?: 'Shop now');
        $heroButtonUrl = trim((string) data_get($heroSettings ?? [], 'button_url', '')) ?: route('shop.index');
        $authUser = auth()->user();
        $isCustomerAuthenticated = $authUser && ! $authUser->isAdminPanelUser();
    @endphp

    <div class="space-y-6 sm:space-y-8 lg:space-y-10">
        <section class="relative mx-auto w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-950 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:shadow-black/10 sm:rounded-3xl">
            <div class="absolute inset-0 overflow-hidden">
                @if ($heroVideoUrl)
                    @if ($heroImageUrl)
                        <img
                            src="{{ $heroImageUrl }}"
                            alt="{{ __('Auto parts banner') }}"
                            class="absolute inset-0 h-full w-full object-cover"
                            data-hero-video-fallback
                        >
                    @else
                        <div
                            class="absolute inset-0 h-full w-full bg-[linear-gradient(135deg,#070740_0%,#111827_52%,#1f2937_100%)]"
                            data-hero-video-fallback
                        ></div>
                    @endif
                    <video
                        class="hero-background-video absolute inset-0 h-full w-full object-cover pointer-events-none opacity-0 transition-opacity duration-300"
                        data-hero-background-video
                        autoplay
                        muted
                        loop
                        playsinline
                        webkit-playsinline
                        preload="auto"
                        disablepictureinpicture
                        disableremoteplayback
                        controlslist="nodownload nofullscreen noremoteplayback"
                        x-webkit-airplay="deny"
                        aria-hidden="true"
                        tabindex="-1"
                        @if ($heroImageUrl) poster="{{ $heroImageUrl }}" @endif
                    >
                        <source src="{{ $heroVideoUrl }}" type="video/mp4">
                    </video>
                @elseif ($heroImageUrl)
                    <img src="{{ $heroImageUrl }}" alt="{{ __('Auto parts banner') }}" class="absolute inset-0 h-full w-full object-cover">
                @else
                    <div class="absolute inset-0 h-full w-full bg-[linear-gradient(135deg,#070740_0%,#111827_52%,#1f2937_100%)]"></div>
                @endif

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/40 to-slate-950/15"></div>
            </div>

            <div class="relative grid grid-cols-1 items-end gap-5 p-4 pt-48 sm:p-6 sm:pt-56 lg:min-h-[470px] lg:grid-cols-[minmax(0,1fr)_360px] lg:items-end lg:gap-8 lg:p-11 lg:pt-28">
                <div>
                    <span class="inline-block rounded-full border border-white/30 px-3 py-1.5 font-mono text-[10px] uppercase tracking-[0.16em] text-slate-100">
                        {{ __('Genuine & OEM parts') }}
                    </span>
                    <h1 class="mt-3 max-w-xl text-2xl font-bold leading-tight tracking-[-0.03em] text-white sm:mt-4 sm:text-3xl lg:text-[44px] lg:leading-[1.06]">
                        {{ $heroTitle }}
                    </h1>
                    <p class="mt-2 max-w-xl text-xs leading-5 text-slate-300 sm:mt-3 sm:text-sm sm:leading-6 lg:text-base">
                        {{ $heroSubtitle }}
                    </p>
                    <a
                        href="{{ $heroButtonUrl }}"
                        class="mt-4 inline-flex items-center justify-center rounded-xl border border-white/35 px-4 py-2 text-xs font-semibold text-white transition duration-200 hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/50 sm:mt-5 sm:rounded-2xl sm:py-2.5 sm:text-sm"
                    >
                        {{ $heroButtonLabel }}
                    </a>
                </div>

                <form
                    id="vehicle-finder"
                    method="GET"
                    action="{{ route('shop.index') }}"
                    class="flex flex-col gap-2.5 rounded-2xl border border-white/25 bg-white/10 p-4 backdrop-blur-xl sm:p-5"
                    data-vehicle-finder
                    data-model-map='@json($modelOptionsByBrand)'
                    data-model-placeholder="{{ __('Model') }}"
                    data-all-models-placeholder="{{ __('Select brand first') }}"
                    data-no-models-placeholder="{{ __('No models for this brand yet') }}"
                >
                    <p class="text-sm font-semibold text-white">{{ __('Find parts for your vehicle') }}</p>

                    <select
                        name="brand"
                        data-vehicle-brand
                        class="w-full rounded-xl border-0 bg-white/95 px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:ring-4 focus:ring-white/30"
                    >
                        <option value="">{{ __('Brand') }}</option>
                        @foreach ($brandOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>

                    <select
                        name="model"
                        data-vehicle-model
                        class="w-full rounded-xl border-0 bg-white/95 px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:ring-4 focus:ring-white/30"
                    >
                        <option value="">{{ __('Model') }}</option>
                        @foreach ($modelOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>

                    <select
                        name="vehicle"
                        class="w-full rounded-xl border-0 bg-white/95 px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:ring-4 focus:ring-white/30"
                    >
                        <option value="">{{ __('Engine / Year') }}</option>
                        @foreach ($engineOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>

                    <button
                        type="submit"
                        class="mt-1 inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-primary transition duration-200 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/50"
                    >
                        {{ __('Find parts') }}
                    </button>
                </form>
            </div>
        </section>

        <section class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ __('Browse Categories') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Start from the system you need and narrow down quickly.') }}</p>
                </div>
                <a
                    href="{{ route('categories.index') }}"
                    class="inline-flex items-center rounded-full px-3 py-2 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-primary/30"
                >
                    {{ __('View catalog') }}
                </a>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 md:grid-cols-3 lg:grid-cols-5">
                @forelse ($categories as $category)
                    <a
                        href="{{ route('shop.index', ['category' => data_get($category, 'slug') ?: data_get($category, 'id')]) }}"
                        class="group flex min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 transition duration-200 hover:-translate-y-0.5 hover:border-primary/20 hover:shadow-md hover:shadow-slate-900/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 dark:hover:border-primary/30 dark:hover:shadow-black/20 sm:min-h-56 sm:rounded-3xl"
                    >
                        <div class="flex h-28 items-center justify-center overflow-hidden bg-slate-100 p-1.5 text-primary transition duration-200 group-hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:group-hover:bg-slate-700 sm:h-36">
                            @if (data_get($category, 'image'))
                                <img src="{{ data_get($category, 'image') }}" alt="{{ data_get($category, 'name') }}" class="h-full w-full scale-[1.08] object-contain transition duration-300 group-hover:scale-[1.1]" loading="lazy">
                            @else
                                <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6M9 14h4" />
                                </svg>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ data_get($category, 'name') }}</p>
                            @if (filled(data_get($category, 'description')))
                                <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ data_get($category, 'description') }}</p>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 sm:col-span-2 lg:col-span-5">
                        {{ __('No categories found.') }}
                    </div>
                @endforelse
            </div>
        </section>

        @include('user.shop.partials.home-product-rail', [
            'title' => __('New Arrivals'),
            'subtitle' => __('Freshly added to our catalog.'),
            'products' => $newArrivals,
            'badge' => 'new',
            'viewAllUrl' => route('shop.index'),
        ])

        @include('user.shop.partials.home-product-rail', [
            'title' => __('Best Sellers'),
            'subtitle' => __('The parts our customers order the most.'),
            'products' => $bestSellers,
            'badge' => 'best',
            'viewAllUrl' => route('shop.index'),
        ])

        @include('user.shop.partials.home-product-rail', [
            'title' => __('Popular Right Now'),
            'subtitle' => __('Fast-moving essentials selected from current inventory.'),
            'products' => $featuredProducts,
            'badge' => 'popular',
            'viewAllUrl' => route('shop.index'),
        ])
    </div>

    @push('scripts')
        <style>
            .hero-background-video::-webkit-media-controls,
            .hero-background-video::-webkit-media-controls-panel,
            .hero-background-video::-webkit-media-controls-play-button,
            .hero-background-video::-webkit-media-controls-start-playback-button {
                display: none !important;
                -webkit-appearance: none;
                opacity: 0;
                pointer-events: none;
            }
        </style>
    @endpush
@endsection
