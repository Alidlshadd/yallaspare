<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ __('Customer Reviews') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Review product feedback and remove unsuitable comments.') }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                {{ __('Product Operations') }}
            </span>
        </div>
    </x-slot>

    @php
        $metricCards = [
            [
                'label' => __('Total Reviews'),
                'value' => number_format((int) ($stats['total'] ?? 0)),
                'detail' => __('Matching current filters'),
                'class' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
                'valueClass' => 'text-slate-900 dark:text-slate-100',
            ],
            [
                'label' => __('Average Rating'),
                'value' => number_format((float) ($stats['average'] ?? 0), 1) . ' / 5',
                'detail' => __('Across matching reviews'),
                'class' => 'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/20',
                'valueClass' => 'text-amber-800 dark:text-amber-200',
            ],
            [
                'label' => __('Five Star'),
                'value' => number_format((int) ($stats['five_star'] ?? 0)),
                'detail' => __('Highest rating reviews'),
                'class' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/20',
                'valueClass' => 'text-emerald-800 dark:text-emerald-200',
            ],
            [
                'label' => __('Low Rating'),
                'value' => number_format((int) ($stats['low_rating'] ?? 0)),
                'detail' => __('One or two star reviews'),
                'class' => 'border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/20',
                'valueClass' => 'text-rose-800 dark:text-rose-200',
            ],
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($metricCards as $card)
                    <article class="rounded-2xl border p-5 shadow-sm {{ $card['class'] }}">
                        <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold {{ $card['valueClass'] }}">{{ $card['value'] }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $card['detail'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                    <form method="GET" action="{{ route('admin.reviews.index') }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_180px_auto_auto]">
                        <label class="block">
                            <span class="sr-only">{{ __('Search') }}</span>
                            <input
                                name="search"
                                value="{{ $search }}"
                                placeholder="{{ __('Search product, SKU, customer, title, or comment...') }}"
                                class="h-11 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            >
                        </label>

                        <label class="block">
                            <span class="sr-only">{{ __('Rating') }}</span>
                            <select name="rating" class="h-11 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="0">{{ __('All Ratings') }}</option>
                                @for($value = 5; $value >= 1; $value--)
                                    <option value="{{ $value }}" @selected((int) $rating === $value)>{{ $value }} / 5</option>
                                @endfor
                            </select>
                        </label>

                        <button type="submit" class="h-11 rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                            {{ __('Apply Filters') }}
                        </button>

                        @if($search !== '' || (int) $rating > 0)
                            <a href="{{ route('admin.reviews.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </form>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.reviews.index', array_filter(['search' => $search])) }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ (int) $rating === 0 ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            {{ __('All') }}
                        </a>
                        @for($value = 5; $value >= 1; $value--)
                            <a href="{{ route('admin.reviews.index', array_filter(['search' => $search, 'rating' => $value])) }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ (int) $rating === $value ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                                <span class="text-amber-500">★</span>
                                {{ $value }}
                                <span class="rounded-full bg-current/10 px-1.5 py-0.5">{{ number_format((int) ($ratingCounts->get($value, 0) ?? 0)) }}</span>
                            </a>
                        @endfor
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Review') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Product') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Customer') }}</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                            @forelse($reviews as $review)
                                <tr class="align-top hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="max-w-xl px-5 py-5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300">
                                                {{ (int) $review->rating }} / 5
                                            </span>
                                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ optional($review->reviewed_at ?? $review->created_at)->format('M d, Y') ?: '-' }}
                                            </span>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $review->title ?: __('Customer review') }}</p>
                                        @if($review->comment)
                                            <p class="mt-2 line-clamp-4 text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $review->comment }}</p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-5">
                                        @if($review->product)
                                            <div class="flex min-w-[260px] items-center gap-3">
                                                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-950">
                                                    @if($review->product->image)
                                                        <img src="{{ asset('storage/' . ltrim((string) $review->product->image, '/')) }}" alt="{{ $review->product->name }}" class="h-full w-full object-contain">
                                                    @else
                                                        <div class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-500">
                                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16 9 11l4 4 3-3 4 4" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <a href="{{ route('admin.products.edit', $review->product) }}" class="block truncate text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                                                        {{ $review->product->name }}
                                                    </a>
                                                    <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ __('SKU:') }} {{ $review->product->sku ?: '-' }}</p>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Product unavailable') }}</p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-5">
                                        @if($review->user && Route::has('admin.users.show'))
                                            <a href="{{ route('admin.users.show', $review->user) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                                                {{ $review->user->name }}
                                            </a>
                                        @else
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $review->user?->name ?? __('Customer') }}</p>
                                        @endif
                                        <p class="mt-1 max-w-[220px] truncate text-xs text-slate-500 dark:text-slate-400">{{ $review->user?->email ?? '-' }}</p>
                                    </td>

                                    <td class="px-5 py-5 text-right">
                                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('{{ __('Delete this review?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-900 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-14 text-center">
                                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('No reviews found.') }}</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Try changing the search or rating filter.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($reviews->hasPages())
                    <div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800">
                        {{ $reviews->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
