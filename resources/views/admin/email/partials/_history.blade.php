<div x-data="historyTab()" x-init="init()">
    <div x-show="loading" x-cloak class="rounded-2xl border border-slate-200 bg-white p-10 text-center dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">{{ __('Loading…') }}</p>
    </div>

    <div x-show="!loading && items.length === 0" x-cloak class="rounded-2xl border border-slate-200 bg-white p-10 text-center dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('No broadcasts yet') }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ __('Send your first broadcast from the Broadcast tab.') }}</p>
    </div>

    <div x-show="!loading && items.length > 0" class="space-y-2" x-cloak>
        <template x-for="b in items" :key="b.id">
            <div @click="openDetail(b.id)" class="cursor-pointer rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white" x-text="b.subject"></p>
                        <p class="mt-0.5 text-xs text-slate-500" x-text="b.admin_email + ' · ' + b.created_at_human + ' · ' + b.filter_summary"></p>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <div><span class="block text-sm font-bold text-slate-900 dark:text-white" x-text="b.recipient_count"></span><span class="text-slate-500">{{ __('total') }}</span></div>
                        <div><span class="block text-sm font-bold text-emerald-600 dark:text-emerald-300" x-text="b.sent_count"></span><span class="text-slate-500">{{ __('sent') }}</span></div>
                        <div><span class="block text-sm font-bold text-rose-600 dark:text-rose-400" x-text="b.failed_count"></span><span class="text-slate-500">{{ __('failed') }}</span></div>
                        <span :class="badgeClasses(b.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase" x-text="b.status"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Drawer --}}
    <div x-show="detail" x-cloak class="fixed inset-0 z-50 flex">
        <div class="flex-1 bg-slate-900/40 backdrop-blur-sm" @click="detail = null"></div>
        <aside class="w-full max-w-md overflow-y-auto bg-white p-6 shadow-2xl dark:bg-slate-950">
            <button @click="detail = null" class="text-sm text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">&larr; {{ __('Close') }}</button>
            <template x-if="detail">
                <div class="mt-4 space-y-4">
                    <div>
                        <p class="text-lg font-bold text-slate-900 dark:text-white" x-text="detail.subject"></p>
                        <p class="text-xs text-slate-500" x-text="(detail.admin?.email || '—') + ' · ' + new Date(detail.created_at).toLocaleString()"></p>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-lg bg-slate-100 p-3 dark:bg-slate-900"><p class="text-xl font-bold" x-text="detail.recipient_count"></p><p class="text-xs text-slate-500">{{ __('total') }}</p></div>
                        <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-950/40"><p class="text-xl font-bold text-emerald-700 dark:text-emerald-300" x-text="detail.sent_count"></p><p class="text-xs text-slate-500">{{ __('sent') }}</p></div>
                        <div class="rounded-lg bg-rose-50 p-3 dark:bg-rose-950/40"><p class="text-xl font-bold text-rose-700 dark:text-rose-400" x-text="detail.failed_count"></p><p class="text-xs text-slate-500">{{ __('failed') }}</p></div>
                    </div>
                    <div>
                        <p class="mb-1 text-xs uppercase tracking-wider text-slate-500">{{ __('Recipients (first 100)') }}</p>
                        <ul class="space-y-1 text-xs">
                            <template x-for="(r, idx) in detail.recipients_preview" :key="idx + r.email">
                                <li class="flex justify-between gap-2">
                                    <span class="truncate text-slate-700 dark:text-slate-300" x-text="r.email"></span>
                                    <span :class="r.status === 'sent' ? 'text-emerald-600' : (r.status === 'failed' ? 'text-rose-600' : 'text-slate-500')" x-text="r.status"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>
        </aside>
    </div>
</div>

<script>
function historyTab() {
    return {
        loading: true,
        items: [],
        detail: null,
        async init() {
            const res = await fetch('{{ route('admin.email.broadcasts.history') }}', { headers: { 'Accept': 'text/html' } });
            const html = await res.text();
            const data = new DOMParser().parseFromString(html, 'text/html').querySelector('script#history-data');
            this.items = data ? JSON.parse(data.textContent) : [];
            this.loading = false;
        },
        async openDetail(id) {
            const res = await fetch('/admin/email/broadcasts/' + id, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.detail = data.broadcast;
        },
        badgeClasses(status) {
            const map = {
                queued: 'bg-amber-100 text-amber-700',
                sending: 'bg-blue-100 text-blue-700',
                completed: 'bg-emerald-100 text-emerald-700',
                failed: 'bg-rose-100 text-rose-700',
            };
            return map[status] ?? 'bg-slate-100 text-slate-700';
        },
    };
}
</script>
