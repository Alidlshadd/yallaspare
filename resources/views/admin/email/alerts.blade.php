<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ __('alerts.title') }}</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('alerts.subtitle') }}</p>
            </div>
            <a href="{{ route('admin.email.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-700 dark:text-white">{{ __('Back to Email Center') }}</a>
        </div>
        @if(session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.email-alerts.update') }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach(['order', 'system'] as $type)
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('alerts.'.$type) }}</h3>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ __('alerts.recipient_count', ['count' => count($service->recipients($type))]) }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('alerts.'.$type.'_description') }}</p>
                        <input type="hidden" name="{{ $type }}_enabled" value="0">
                        <label class="my-4 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="{{ $type }}_enabled" value="1" @checked(old($type.'_enabled', $service->enabled($type))) class="rounded border-slate-300 text-primary">
                            {{ __('alerts.enabled') }}
                        </label>
                        <label for="{{ $type }}_recipients" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('alerts.recipients') }}</label>
                        <textarea id="{{ $type }}_recipients" name="{{ $type }}_recipients" rows="5" maxlength="15000" dir="ltr" aria-describedby="{{ $type }}_help" placeholder="owner@example.com&#10;team@example.com" class="mt-2 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old($type.'_recipients', implode("\n", $service->recipients($type))) }}</textarea>
                        <p id="{{ $type }}_help" class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('alerts.recipient_help') }}</p>
                    </section>
                @endforeach
            </div>
            <div class="flex flex-wrap items-end justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('alerts.language') }}
                    <select name="locale" class="ms-3 rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @foreach(['en' => 'English', 'ar' => 'العربية', 'ku' => 'کوردی'] as $locale => $label)
                            <option value="{{ $locale }}" @selected(old('locale', \App\Models\Setting::getValue('admin_alert_locale', 'en')) === $locale)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white">{{ __('alerts.save') }}</button>
            </div>
        </form>

        <div class="flex flex-wrap items-center gap-3">
            @foreach(['order', 'system'] as $type)
                <form method="POST" action="{{ route('admin.email-alerts.test') }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-white">{{ __('alerts.test_'.$type) }}</button>
                </form>
            @endforeach
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('alerts.test_help') }}</p>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="space-y-3 border-b border-slate-200 p-5 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('alerts.history') }}</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('alerts.delivery_help') }}</p>
                <nav class="flex flex-wrap gap-2" aria-label="{{ __('Status') }}">
                    <a href="{{ route('admin.email-alerts.index') }}" class="rounded-lg border px-3 py-1.5 text-sm {{ $status === '' ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-300' }}">{{ __('All') }}</a>
                    @foreach($statuses as $item)
                        <a href="{{ route('admin.email-alerts.index', ['status' => $item]) }}" class="rounded-lg border px-3 py-1.5 text-sm {{ $status === $item ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-300' }}">{{ __('alerts.status_'.$item) }} ({{ $counts[$item] ?? 0 }})</a>
                    @endforeach
                </nav>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 sm:hidden">
                @forelse($logs as $log)
                    <article class="space-y-3 p-5 text-sm text-slate-700 dark:text-slate-200">
                        <div class="flex items-center justify-between gap-2">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $log->status === 'failed' ? 'bg-red-50 text-red-700' : ($log->status === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800') }}">{{ __('alerts.status_'.$log->status) }}</span>
                            <time class="text-xs text-slate-500">{{ $log->created_at->format('Y-m-d H:i') }}</time>
                        </div>
                        <p class="break-all font-semibold" dir="ltr">{{ $log->recipient }}</p>
                        <p class="text-xs text-slate-500">{{ __('alerts.'.$log->type) }}</p>
                        <details><summary class="cursor-pointer">{{ $log->subject }}</summary><p class="mt-2 whitespace-pre-line">{{ $log->message }}</p></details>
                        @if($log->error_code)<p class="text-xs text-red-600">{{ __('alerts.failure_help') }} ({{ $log->error_code }})</p>@endif
                        @if($log->status === 'failed')
                            <form method="POST" action="{{ route('admin.email-alerts.retry', $log) }}">@csrf<button class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold">{{ __('alerts.retry') }}</button></form>
                        @endif
                    </article>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('alerts.empty') }}</p>
                @endforelse
            </div>
            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full text-start text-sm">
                    <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-300"><tr>
                        @foreach(['When', 'Email', 'Subject', 'Status'] as $heading)<th class="px-5 py-3 text-start">{{ __($heading) }}</th>@endforeach
                        <th class="px-5 py-3 text-start">{{ __('alerts.actions') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-200">
                        @forelse($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-xs">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-5 py-4" dir="ltr">{{ $log->recipient }}</td>
                                <td class="min-w-[220px] px-5 py-4">
                                    <span class="text-xs text-slate-500">{{ __('alerts.'.$log->type) }}</span>
                                    <details class="mt-1"><summary class="cursor-pointer font-medium">{{ $log->subject }}</summary><p class="mt-2 whitespace-pre-line text-sm">{{ $log->message }}</p></details>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="whitespace-nowrap rounded-full px-2 py-1 text-xs font-semibold {{ $log->status === 'failed' ? 'bg-red-50 text-red-700' : ($log->status === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800') }}">{{ __('alerts.status_'.$log->status) }}</span>
                                    @if($log->error_code)<p class="mt-2 text-xs text-red-600">{{ __('alerts.failure_help') }} ({{ $log->error_code }})</p>@endif
                                </td>
                                <td class="px-5 py-4">
                                    @if($log->status === 'failed')
                                        <form method="POST" action="{{ route('admin.email-alerts.retry', $log) }}">@csrf<button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold">{{ __('alerts.retry') }}</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">{{ __('alerts.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-5">{{ $logs->links() }}</div>
        </section>
    </div>
</x-app-layout>
