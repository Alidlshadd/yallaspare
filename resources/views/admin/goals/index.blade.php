<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-orange-600">{{ __('Business Progress Center') }}</p>
                <h2 class="mt-1 text-2xl font-bold text-[#070740] dark:text-white">{{ __('Goals & Challenges') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Track the outcomes that move your business forward.') }}</p>
            </div>
            @can(\App\Models\User::PERMISSION_GOALS_MANAGE)
                <details class="group relative z-20">
                    <summary class="cursor-pointer list-none rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-orange-700">
                        <i class="fas fa-plus me-2" aria-hidden="true"></i>{{ __('Create Goal') }}
                    </summary>
                    <div class="fixed inset-0 z-40 overflow-y-auto bg-slate-950/55 p-3 sm:p-8">
                        <div class="ms-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:p-7">
                            <div class="mb-6 flex items-start justify-between gap-4">
                                <div><h3 class="text-xl font-bold text-slate-950 dark:text-white">{{ __('Create a business goal') }}</h3><p class="mt-1 text-sm text-slate-500">{{ __('Connect it to live business data or update it manually.') }}</p></div>
                                <a href="{{ route('admin.goals.index', ['period' => $period['type'], 'anchor' => $period['anchor']]) }}" aria-label="{{ __('Close') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-slate-500 dark:border-slate-700"><i class="fas fa-xmark" aria-hidden="true"></i></a>
                            </div>
                            @include('admin.goals.partials.form', ['goal' => null, 'action' => route('admin.goals.store'), 'method' => 'POST'])
                        </div>
                    </div>
                </details>
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-3 sm:px-6 lg:px-8">
            @if(session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
                <div class="grid grid-cols-3 rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                    @foreach(\App\Models\Goal::periodTypes() as $type)
                        <a href="{{ route('admin.goals.index', ['period' => $type]) }}" class="rounded-lg px-3 py-2 text-center text-sm font-semibold transition {{ $period['type'] === $type ? 'bg-white text-[#070740] shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400' }}">{{ __(ucfirst($type)) }}</a>
                    @endforeach
                </div>
                <div class="flex items-center justify-between gap-2 sm:justify-end">
                    <a aria-label="{{ __('Previous period') }}" href="{{ route('admin.goals.index', ['period' => $period['type'], 'anchor' => $period['previous_anchor']]) }}" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300"><i class="fas fa-chevron-left rtl:rotate-180" aria-hidden="true"></i></a>
                    <div class="min-w-36 text-center"><p class="font-bold text-slate-950 dark:text-white">{{ $period['title'] }}</p><p class="text-xs text-slate-500">{{ $period['date_range'] }}</p></div>
                    <a aria-label="{{ __('Next period') }}" href="{{ route('admin.goals.index', ['period' => $period['type'], 'anchor' => $period['next_anchor']]) }}" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300"><i class="fas fa-chevron-right rtl:rotate-180" aria-hidden="true"></i></a>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl bg-[#070740] p-6 text-white shadow-xl sm:p-8">
                <div class="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div><p class="text-sm font-semibold text-orange-300">{{ $period['title'] }}</p><h3 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $overall }}% {{ __('Complete') }}</h3><p class="mt-3 max-w-xl text-sm leading-6 text-indigo-100">{{ $needsAttention > 0 ? trans_choice(':count goals need attention before the period ends.|:count goals need attention before the period ends.', $needsAttention, ['count' => $needsAttention]) : __('Great progress. Your active goals are on track.') }}</p>
                        <div class="mt-6 grid max-w-2xl grid-cols-3 gap-3"><div><p class="text-2xl font-bold">{{ $completed }} / {{ $goals->count() }}</p><p class="text-xs text-indigo-200">{{ __('Goals completed') }}</p></div><div><p class="text-2xl font-bold">{{ $period['days_remaining'] }}</p><p class="text-xs text-indigo-200">{{ __('Days remaining') }}</p></div><div><p class="text-2xl font-bold">{{ number_format($goals->where('evaluation.status', 'completed')->sum('reward_points')) }}</p><p class="text-xs text-indigo-200">{{ __('Points earned') }}</p></div></div>
                    </div>
                    <div class="relative mx-auto h-36 w-36"><svg viewBox="0 0 42 42" class="h-full w-full -rotate-90"><circle cx="21" cy="21" r="16" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="4"/><circle cx="21" cy="21" r="16" fill="none" stroke="#FF6A00" stroke-width="4" stroke-linecap="round" pathLength="100" stroke-dasharray="{{ $overall }} 100"/></svg><div class="absolute inset-0 grid place-items-center text-center"><span class="text-3xl font-bold">{{ $overall }}%</span></div></div>
                </div>
            </section>

            <section>
                <div class="mb-4 flex items-end justify-between"><div><h3 class="text-lg font-bold text-slate-950 dark:text-white">{{ __('Active goals') }}</h3><p class="text-sm text-slate-500">{{ __('Progress is calculated in the business timezone: Asia/Baghdad.') }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $goals->count() }} {{ __('goals') }}</span></div>
                <div class="grid gap-4 xl:grid-cols-2">
                    @forelse($goals as $goal)
                        @php
                            $e = $goal->evaluation;
                            $statusMeta = match($e['status']) { 'completed' => [__('Completed'),'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300','fa-check'], 'at_risk' => [__('At Risk'),'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300','fa-triangle-exclamation'], 'failed' => [__('Failed'),'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300','fa-xmark'], 'in_progress' => [__('In Progress'),'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300','fa-arrow-trend-up'], default => [__('Not Started'),'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300','fa-minus'] };
                            $unitLabel = $goal->unit === 'iqd' ? 'IQD' : __(ucfirst($goal->unit));
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start gap-4"><div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#070740]/5 text-[#070740] dark:bg-white/10 dark:text-white"><i class="fas {{ $goal->tracking_mode === 'automatic' ? 'fa-bolt' : 'fa-pen-ruler' }}" aria-hidden="true"></i></div><div class="min-w-0 flex-1"><div class="flex flex-wrap items-start justify-between gap-2"><div><h4 class="font-bold text-slate-950 dark:text-white">{{ $goal->name }}</h4>@if($goal->description)<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $goal->description }}</p>@endif</div><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusMeta[1] }}"><i class="fas {{ $statusMeta[2] }} me-1" aria-hidden="true"></i>{{ $statusMeta[0] }}</span></div>
                                <div class="mt-5 flex items-end justify-between gap-3"><p class="text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($e['actual'], $goal->unit === 'iqd' ? 0 : 1) }} <span class="text-sm font-medium text-slate-400">/ {{ number_format($e['target'], $goal->unit === 'iqd' ? 0 : 1) }} {{ $unitLabel }}</span></p><span class="font-bold text-[#070740] dark:text-white">{{ $e['progress'] }}%</span></div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-orange-600" style="width: {{ $e['progress'] }}%"></div></div>
                                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500"><span><i class="far fa-calendar me-1" aria-hidden="true"></i>{{ __('Due') }} {{ $goal->deadline->isoFormat('MMM D') }} · {{ $e['days_left'] }} {{ __('days left') }}</span><span><i class="fas fa-flag me-1" aria-hidden="true"></i>{{ __(ucfirst($goal->priority)) }}</span><span><i class="fas fa-star me-1" aria-hidden="true"></i>{{ $goal->reward_points }} {{ __('points') }}</span><span><i class="fas fa-database me-1" aria-hidden="true"></i>{{ __(ucfirst($goal->tracking_mode)) }}</span></div>
                                @can(\App\Models\User::PERMISSION_GOALS_MANAGE)
                                    @if($goal->tracking_mode === \App\Models\Goal::TRACKING_MANUAL)
                                        <form method="POST" action="{{ route('admin.goals.progress', $goal) }}" class="mt-4 flex gap-2">@csrf @method('PATCH')<input type="number" min="0" step="0.01" name="value" required value="{{ $goal->manual_value }}" aria-label="{{ __('Current value') }}" class="min-w-0 flex-1 rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950"><button class="rounded-lg bg-[#070740] px-4 text-sm font-semibold text-white dark:bg-orange-600">{{ __('Update') }}</button></form>
                                    @endif
                                    <details class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-800"><summary class="cursor-pointer text-xs font-semibold text-slate-500">{{ __('Edit goal') }}</summary><div class="mt-4">@include('admin.goals.partials.form', ['goal' => $goal, 'action' => route('admin.goals.update', $goal), 'method' => 'PUT'])<form method="POST" action="{{ route('admin.goals.destroy', $goal) }}" class="mt-3">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600">{{ __('Archive goal') }}</button></form></div></details>
                                @endcan
                            </div></div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900"><i class="fas fa-bullseye text-3xl text-slate-300" aria-hidden="true"></i><h4 class="mt-4 font-bold text-slate-900 dark:text-white">{{ __('No goals for this period') }}</h4><p class="mt-1 text-sm text-slate-500">{{ __('Create a goal or move to another period.') }}</p></div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
