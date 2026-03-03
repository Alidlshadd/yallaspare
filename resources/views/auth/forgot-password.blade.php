<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Forgot Password | {{ $systemSettings['site_name'] ?? 'YallaSpare' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased selection:bg-red-600 selection:text-white">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 sm:px-6">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(30,64,175,0.30),_transparent_40%),radial-gradient(circle_at_82%_78%,_rgba(220,38,38,0.12),_transparent_28%)] motion-safe:animate-pulse"></div>

        <main class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-2xl shadow-black/40 backdrop-blur sm:p-8 motion-safe:[animation:fadeIn_0.6s_ease-out] motion-safe:[@keyframes_fadeIn{0%{opacity:0;transform:translateY(12px)}100%{opacity:1;transform:translateY(0)}}] motion-safe:[animation:float_6s_ease-in-out_infinite] motion-safe:[@keyframes_float{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}]">
            <header class="text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center text-3xl font-bold tracking-tight text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 rounded-md">
                    YallaSpare
                </a>
                <p class="mt-2 text-sm font-medium text-slate-200">Reset Your Password</p>
                <p class="mt-2 text-sm text-slate-400">
                    Enter your email to receive a secure password reset link.
                </p>
            </header>

            <x-auth-session-status class="mt-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-300" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('Email')" class="text-sm font-medium text-slate-300" />
                    <x-text-input
                        id="email"
                        class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 transition focus:border-red-500 focus:ring-red-500"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        placeholder="you@example.com"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-400" />
                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-950/40 transition hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
                >
                    {{ __('Email Password Reset Link') }}
                </button>
            </form>

            <div class="mt-5 text-center">
                <a
                    href="{{ route('login') }}"
                    class="text-sm text-slate-400 underline decoration-slate-600 underline-offset-4 transition hover:text-red-300 hover:decoration-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 rounded-sm"
                >
                    Back to Login
                </a>
            </div>
        </main>
    </div>
</body>
</html>
