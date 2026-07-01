<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">{{ __('Search Insights') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('See what customers search for and which searches need better product coverage.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ __('Keywords') }}</p>
                    <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ number_format($summary['keywords']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ __('Searches') }}</p>
                    <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ number_format($summary['searches']) }}</p>
                </article>
                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-rose-800 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-200">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] opacity-75">{{ __('No Match On Page') }}</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($summary['zero_result_on_page']) }}</p>
                </article>
                <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 text-indigo-800 shadow-sm dark:border-indigo-900/50 dark:bg-indigo-950/20 dark:text-indigo-200">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] opacity-75">{{ __('Top Keyword') }}</p>
                    <p class="mt-2 truncate text-xl font-black">{{ $summary['top_keyword'] ?: __('N/A') }}</p>
                </article>
            </section>

            <form method="GET" action="{{ route('admin.search-insights.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid gap-3 md:grid-cols-5">
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search keyword') }}" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 md:col-span-2">
                    <select name="sort" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach(['search_count' => __('Search count'), 'last_searched_at' => __('Last searched'), 'keyword' => __('Keyword')] as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="dir" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="desc" @selected($direction === 'desc')>{{ __('Descending') }}</option>
                        <option value="asc" @selected($direction === 'asc')>{{ __('Ascending') }}</option>
                    </select>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('Filter') }}</button>
                </div>
            </form>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left">{{ __('Keyword') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Search Count') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Matching Products') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Last Searched') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($keywords as $keyword)
                                <tr class="{{ (int) $keyword->matching_products_count === 0 ? 'bg-rose-50/40 dark:bg-rose-950/10' : '' }}">
                                    <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ $keyword->keyword }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-200">{{ number_format((int) $keyword->search_count) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-bold {{ (int) $keyword->matching_products_count === 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200' }}">
                                            {{ number_format((int) $keyword->matching_products_count) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-500">{{ optional($keyword->last_searched_at)->diffForHumans() ?? __('N/A') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">{{ __('No search analytics found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $keywords->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
