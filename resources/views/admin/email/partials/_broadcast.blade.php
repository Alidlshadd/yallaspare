@php
    use App\Models\User;
    $allRoles = [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_PRODUCT_MANAGER, User::ROLE_ORDER_MANAGER, User::ROLE_FINANCE_MANAGER, User::ROLE_INVENTORY_MANAGER, User::ROLE_SETTINGS_MANAGER, User::ROLE_DEALER, User::ROLE_USER];
    $cannedTemplates = ['campaign', 'announcement', 'dealer', 'thanks'];
    $templatesPayload = collect($cannedTemplates)->mapWithKeys(fn ($k) => [$k => __('broadcast.template.' . $k . '.body')])->toArray();
@endphp

<div x-data="broadcastForm()" x-init="init()" class="grid gap-6 lg:grid-cols-[280px_1fr_280px]">

    {{-- Column 1: filters --}}
    <aside class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Filters') }}</p>

            <div class="mt-3">
                <p class="mb-1 text-xs uppercase tracking-wider text-slate-500">{{ __('Role') }}</p>
                <div class="flex flex-wrap gap-1">
                    @foreach ($allRoles as $r)
                        <button type="button" @click="toggle('roles', '{{ $r }}')"
                                :class="filters.roles.includes('{{ $r }}') ? 'bg-red-600 text-white border-red-600' : 'border-slate-300 text-slate-700 dark:border-slate-700 dark:text-slate-300'"
                                class="rounded-full border px-2.5 py-0.5 text-xs font-semibold transition">{{ $r }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-3" x-show="filters.roles.includes('dealer')" x-cloak>
                <p class="mb-1 text-xs uppercase tracking-wider text-slate-500">{{ __('Dealer status') }}</p>
                <div class="flex flex-wrap gap-1">
                    @foreach (['active', 'inactive', 'suspended'] as $s)
                        <button type="button" @click="toggle('dealer_statuses', '{{ $s }}')"
                                :class="filters.dealer_statuses.includes('{{ $s }}') ? 'bg-red-600 text-white border-red-600' : 'border-slate-300 text-slate-700 dark:border-slate-700 dark:text-slate-300'"
                                class="rounded-full border px-2.5 py-0.5 text-xs font-semibold transition">{{ $s }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-3">
                <p class="mb-1 text-xs uppercase tracking-wider text-slate-500">{{ __('Language') }}</p>
                <div class="flex flex-wrap gap-1">
                    @foreach (['en', 'ar', 'ku'] as $loc)
                        <button type="button" @click="toggle('locales', '{{ $loc }}')"
                                :class="filters.locales.includes('{{ $loc }}') ? 'bg-red-600 text-white border-red-600' : 'border-slate-300 text-slate-700 dark:border-slate-700 dark:text-slate-300'"
                                class="rounded-full border px-2.5 py-0.5 text-xs font-semibold transition">{{ strtoupper($loc) }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-3">
                <p class="mb-1 text-xs uppercase tracking-wider text-slate-500">{{ __('Order activity') }}</p>
                <select x-model="filters.order_state" class="w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="any">{{ __('Any') }}</option>
                    <option value="active">{{ __('Active (90 days)') }}</option>
                    <option value="old">{{ __('Older than 90 days') }}</option>
                    <option value="none">{{ __('Never ordered') }}</option>
                </select>
            </div>

            <div class="mt-3">
                <p class="mb-1 text-xs uppercase tracking-wider text-slate-500">{{ __('Email status') }}</p>
                <select x-model="filters.email_verified" class="w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="any">{{ __('Any') }}</option>
                    <option value="verified">{{ __('Verified') }}</option>
                    <option value="unverified">{{ __('Unverified') }}</option>
                </select>
            </div>
        </div>
    </aside>

    {{-- Column 2: editor --}}
    <section>
        <form method="POST" action="{{ route('admin.email.broadcasts.store') }}" enctype="multipart/form-data" id="broadcast-form" class="space-y-4">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <label class="text-xs uppercase tracking-wider text-slate-500">{{ __('Subject') }}</label>
                <input type="text" name="subject" required x-model="subject" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-2 flex items-center justify-between">
                    <label class="text-xs uppercase tracking-wider text-slate-500">{{ __('Body') }}</label>
                    <select @change="loadTemplate($event.target.value); $event.target.value=''"
                            class="rounded-lg border-slate-300 bg-white text-xs dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="">{{ __('Insert template…') }}</option>
                        @foreach ($cannedTemplates as $key)
                            <option value="{{ $key }}">{{ __('broadcast.template.' . $key . '.title') }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-tiptap-mount></div>
                <input type="hidden" name="body_html" value="<p></p>">
                <script type="application/json" id="broadcast-templates">@json($templatesPayload)</script>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <label class="text-xs uppercase tracking-wider text-slate-500">{{ __('Attachments') }}</label>
                <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                       class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 dark:text-slate-300 dark:file:bg-slate-800 dark:file:text-slate-200">
                <p class="mt-1 text-xs text-slate-500">{{ __('Max 10MB per file, 25MB total. jpg/png/webp/pdf only.') }}</p>
            </div>
        </form>
    </section>

    {{-- Column 3: preview/send --}}
    <aside class="space-y-3">
        <div class="rounded-2xl border border-blue-200 bg-blue-50/40 p-4 text-center dark:border-blue-900/40 dark:bg-blue-950/40">
            <p class="text-3xl font-bold text-slate-900 dark:text-white" x-text="recipientCount"></p>
            <p class="mt-1 text-xs uppercase tracking-wider text-blue-700 dark:text-blue-300">{{ __('recipients selected') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
            <p class="mb-2 text-xs uppercase tracking-wider text-slate-500">{{ __('First 10 recipients') }}</p>
            <ul class="space-y-1 text-xs text-slate-700 dark:text-slate-300">
                <template x-for="r in firstTen" :key="r.id">
                    <li x-text="r.name + ' · ' + r.email"></li>
                </template>
                <li x-show="firstTen.length === 0" class="italic text-slate-400">{{ __('No recipients yet') }}</li>
            </ul>
        </div>

        <button type="button" @click="sendTest" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Send test to me') }}</button>
        <button type="button" @click="confirmAndSend" :disabled="recipientCount === 0" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow shadow-red-900/20 transition hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed">{{ __('Send broadcast') }}</button>
    </aside>
</div>

<script>
function broadcastForm() {
    return {
        subject: '',
        filters: { roles: [], dealer_statuses: [], locales: [], order_state: 'any', email_verified: 'any', manual_include: [], manual_exclude: [] },
        recipientCount: 0,
        firstTen: [],
        previewToken: 0,
        debounce: null,
        init() {
            this.refreshRecipients();
            this.$watch('filters', () => this.scheduleRefresh(), { deep: true });
        },
        toggle(key, value) {
            const arr = this.filters[key];
            const idx = arr.indexOf(value);
            if (idx === -1) arr.push(value); else arr.splice(idx, 1);
        },
        scheduleRefresh() {
            clearTimeout(this.debounce);
            this.debounce = setTimeout(() => this.refreshRecipients(), 300);
        },
        async refreshRecipients() {
            const token = ++this.previewToken;
            const res = await fetch('{{ route('admin.email.broadcasts.recipients-preview') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ filters: this.filters }),
            });
            if (this.previewToken !== token) return;
            if (!res.ok) return;
            const data = await res.json();
            this.recipientCount = data.count;
            this.firstTen = data.first10;
        },
        loadTemplate(key) {
            if (!key) return;
            const payload = JSON.parse(document.getElementById('broadcast-templates').textContent);
            const html = payload[key];
            if (!html) return;
            const mount = document.querySelector('[data-tiptap-mount]');
            if (mount && mount._tiptap) {
                mount._tiptap.commands.setContent(html);
                document.querySelector('input[name=body_html]').value = html;
            }
        },
        async sendTest() {
            const form = document.getElementById('broadcast-form');
            const fd = new FormData(form);
            fd.delete('attachments[]');
            await fetch('{{ route('admin.email.broadcasts.test') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: fd,
            });
            window.location.href = '{{ route('admin.email.index') }}#broadcast';
        },
        confirmAndSend() {
            if (this.recipientCount === 0) return;
            if (this.recipientCount > 100) {
                if (!confirm(`{{ __('Send to') }} ${this.recipientCount} {{ __('recipients?') }}`)) return;
            }
            const form = document.getElementById('broadcast-form');
            let hidden = form.querySelector('input[name=filters]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'filters';
                form.appendChild(hidden);
            }
            hidden.value = JSON.stringify(this.filters);
            form.submit();
        },
    };
}
</script>
