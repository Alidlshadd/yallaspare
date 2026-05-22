@extends('layouts.user')

@section('content')
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Wishlist') }}</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Saved Items') }}</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('Keep products here and come back when you are ready to buy.') }}</p>
                </div>

                <a
                    href="{{ route('user.shop.home') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                    {{ __('Home') }}
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
            @if ($items->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center dark:border-slate-700 dark:bg-slate-950">
                    <p class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Your wishlist is empty') }}</p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('Browse the catalog and save products here for later.') }}</p>

                    <div class="mt-6 flex items-center justify-center">
                        <a
                            href="{{ route('shop.index') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            {{ __('Browse Products') }}
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($items as $item)
                        @php($wishlistProduct = $item->product)
                        @if ($wishlistProduct)
                            <div class="relative">
                                <x-product-card :product="$wishlistProduct" />
                                <form action="{{ route('user.wishlist.destroy', $wishlistProduct) }}" method="POST" class="absolute right-4 top-4 z-30">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-full bg-white/95 px-3 py-1.5 text-xs font-semibold text-rose-700 shadow-sm ring-1 ring-rose-200 transition hover:bg-rose-50 dark:bg-slate-900/95 dark:text-rose-300 dark:ring-rose-800">
                                        {{ __('Remove') }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $items->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
