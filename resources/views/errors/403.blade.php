<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Access Denied
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
            <h3 class="text-lg font-semibold text-gray-900">You do not have permission to view this page.</h3>
            <p class="text-sm text-gray-600 mt-2">
                If you believe this is a mistake, contact an administrator.
            </p>
            <div class="mt-6">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-900">
                    Go to Home
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
