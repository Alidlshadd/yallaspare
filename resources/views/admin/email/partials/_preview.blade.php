<div x-data="{
        template: 'verify-email',
        locale: '{{ app()->getLocale() }}',
        templates: @json($previewTemplates ?? []),
        get url() {
            return '{{ url('/admin/email/preview') }}/' + this.template + '?locale=' + this.locale;
        },
     }" class="space-y-4">

    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-4 sm:grid-cols-[1fr_180px]">
            <div>
                <label for="preview-template" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Template') }}</label>
                <select id="preview-template" x-model="template" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <template x-for="t in templates" :key="t">
                        <option :value="t" x-text="t"></option>
                    </template>
                </select>
            </div>
            <div>
                <label for="preview-locale" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Locale') }}</label>
                <select id="preview-locale" x-model="locale" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="en">English</option>
                    <option value="ar">العربية (RTL)</option>
                    <option value="ku">کوردی (RTL)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 dark:border-slate-800 dark:bg-slate-950">
        <iframe :src="url" :key="url"
                class="h-[820px] w-full rounded-xl border-0 bg-white"
                title="Email preview"></iframe>
    </div>
</div>
