<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('My Account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="p-6 text-gray-900">
                    <p class="text-sm font-semibold uppercase tracking-[0.12em] text-emerald-700">Welcome back</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">{{ auth()->user()->name }}</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Manage your profile or continue shopping from your account area.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('shop.index') }}" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Browse Products
                        </a>
                        <a href="{{ route('cart.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
                            Open Cart
                        </a>
                        <a href="{{ route('profile.edit') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
