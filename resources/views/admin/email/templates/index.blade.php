<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.email.index') }}"
                   class="h-10 w-10 rounded-xl border border-slate-200 bg-white text-slate-600 grid place-items-center hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                   title="{{ __('Back to Email Center') }}">
                    <i class="fas fa-arrow-left text-xs"></i>
                </a>
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-400 font-bold leading-none">
                        <a href="{{ route('admin.email.index') }}" class="hover:text-primary dark:hover:text-white">{{ __('Email Center') }}</a>
                        <span class="mx-1 text-slate-300">/</span>
                        <span class="text-primary dark:text-white">{{ __('Template Editor') }}</span>
                    </p>
                    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mt-1">{{ __('Template Editor') }}</h2>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if(! $templateAvailable)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                {{ __('Email templates table is not installed yet. Run the pending migrations to enable saving edits.') }}
            </div>
        @endif

        {{-- Header card --}}
        <div class="relative rounded-2xl border border-slate-200/70 bg-white overflow-hidden shadow-sm dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-primary via-indigo-500 to-amber-400"></div>
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-primary to-indigo-700 text-white grid place-items-center shadow-lg shadow-primary/20">
                        <i class="fas fa-file-pen text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.22em] text-slate-400 font-bold leading-none">{{ __('Transactional templates') }}</p>
                        <p class="text-2xl font-semibold text-slate-900 dark:text-white leading-tight mt-1 tracking-tight">{{ count($rows) }} {{ __('templates') }} · {{ count($rows) * count(\App\Models\EmailTemplate::LOCALES) }} {{ __('locales') }}</p>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5">EN · AR · KU</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.email.index') }}#templates" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i class="fas fa-eye text-[10px]"></i> {{ __('Preview templates') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Templates list --}}
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($rows as $key => $row)
                    <li class="px-5 py-4">
                        <div class="flex items-start gap-4">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border
                                {{ $row['tone'] === 'blue' ? 'border-blue-200 bg-blue-50 text-blue-700' :
                                   ($row['tone'] === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' :
                                   ($row['tone'] === 'rose' ? 'border-rose-200 bg-rose-50 text-rose-700' :
                                   ($row['tone'] === 'amber' ? 'border-amber-200 bg-amber-50 text-amber-700' :
                                   ($row['tone'] === 'violet' ? 'border-violet-200 bg-violet-50 text-violet-700' :
                                   ($row['tone'] === 'cyan' ? 'border-cyan-200 bg-cyan-50 text-cyan-700' :
                                   ($row['tone'] === 'indigo' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' :
                                   ($row['tone'] === 'orange' ? 'border-orange-200 bg-orange-50 text-orange-700' :
                                   'border-slate-200 bg-slate-50 text-slate-700'))))))) }}">
                                <i class="fas {{ $row['icon'] }} text-xs"></i>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-primary dark:text-slate-100">{{ $row['title'] }}</p>
                                    <span class="font-mono text-[10px] uppercase tracking-widest text-slate-400">{{ $key }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5 dark:text-slate-400">{{ $row['description'] }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    @foreach($row['locales'] as $locale => $info)
                                        <a href="{{ route('admin.email.templates.edit', ['key' => $key, 'locale' => $locale]) }}"
                                           class="group inline-flex items-center gap-1.5 rounded-lg border {{ $info['has_override'] ? 'border-primary/30 bg-primary/5 text-primary dark:border-primary/50 dark:bg-primary/10 dark:text-indigo-200' : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }} px-2.5 py-1 text-[11px] font-bold hover:bg-primary hover:text-white hover:border-primary transition">
                                            <span class="uppercase">{{ $locale }}</span>
                                            @if($info['has_override'])
                                                <i class="fas fa-check text-[9px] text-emerald-500 group-hover:text-white"></i>
                                            @endif
                                            @if($info['updated_at'])
                                                <span class="font-mono text-[9px] text-slate-400 group-hover:text-white/70">· {{ $info['updated_at']->diffForHumans() }}</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="text-[10px] font-mono text-slate-400">
            <i class="fas fa-info-circle"></i>
            {{ __('Green check = admin override saved. Empty = using default hardcoded content.') }}
        </p>

    </div>
    </div>
    </div>
</x-app-layout>
