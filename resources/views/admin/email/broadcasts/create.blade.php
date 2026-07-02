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
                        <span class="text-primary dark:text-white">{{ __('Create Broadcast') }}</span>
                    </p>
                    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mt-1">{{ __('Create Broadcast') }}</h2>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(! $broadcastsAvailable)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                    {{ __('Email broadcast table is not installed yet. Run the pending migrations before sending broadcasts.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.email.broadcast') }}" id="broadcast-form">
                @csrf

                <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">

                    {{-- LEFT: Compose --}}
                    <div class="space-y-6">

                        {{-- Step 1: Audience --}}
                        <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-5 py-3 border-b border-slate-200/70 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold dark:text-slate-400">{{ __('Step 1 · Audience') }}</p>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2 dark:text-slate-300">{{ __('Who should receive this?') }}</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2" id="audience-tiles">
                                        <button type="button" data-audience="all"
                                                class="audience-tile rounded-xl border-2 border-primary bg-primary/5 px-3 py-3 text-xs font-bold text-primary text-center dark:bg-primary/10">
                                            <i class="fas fa-users block mb-1 text-base"></i>{{ __('All eligible') }}
                                            <p class="font-mono text-[10px] text-slate-400 mt-1">{{ __('Verified users') }}</p>
                                        </button>
                                        <button type="button" data-audience="role"
                                                class="audience-tile rounded-xl border border-slate-200 px-3 py-3 text-xs font-bold text-slate-600 hover:bg-slate-50 text-center dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                            <i class="fas fa-user-group block mb-1 text-base"></i>{{ __('Role group') }}
                                            <p class="font-mono text-[10px] text-slate-400 mt-1">{{ __('Customers / Dealers') }}</p>
                                        </button>
                                        <button type="button" data-audience="user"
                                                class="audience-tile rounded-xl border border-slate-200 px-3 py-3 text-xs font-bold text-slate-600 hover:bg-slate-50 text-center dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                            <i class="fas fa-user block mb-1 text-base"></i>{{ __('Single user') }}
                                            <p class="font-mono text-[10px] text-slate-400 mt-1">{{ __('By email') }}</p>
                                        </button>
                                    </div>
                                    <input type="hidden" name="audience_type" id="audience_type" value="{{ old('audience_type', 'all') }}">
                                </div>

                                <div id="audience-role-wrap" style="display:none">
                                    <label for="audience_role" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Role group') }}</label>
                                    <select id="audience_role" name="audience_role"
                                            class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                        <option value="">{{ __('Choose role') }}</option>
                                        @foreach($audienceRoles as $role => $label)
                                            <option value="{{ $role }}" @selected(old('audience_role') === $role)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="audience-user-wrap" style="display:none">
                                    <label for="recipient_email" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Single user email') }}</label>
                                    <input id="recipient_email" type="email" name="recipient_email" value="{{ old('recipient_email') }}" placeholder="customer@example.com"
                                           class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2 dark:text-slate-300">{{ __('Purpose') }}</label>
                                    <div class="inline-flex w-full max-w-sm rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-slate-700 dark:bg-slate-950" id="purpose-toggle">
                                        <button type="button" data-purpose="promotional"
                                                class="purpose-btn flex-1 rounded-lg bg-primary text-white px-3 py-1.5 text-xs font-bold">{{ __('Promotional') }}</button>
                                        <button type="button" data-purpose="operational"
                                                class="purpose-btn flex-1 rounded-lg text-slate-600 px-3 py-1.5 text-xs font-bold dark:text-slate-300">{{ __('Operational') }}</button>
                                    </div>
                                    <input type="hidden" name="purpose" id="purpose" value="{{ old('purpose', 'promotional') }}">
                                    <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">{{ __('Promotional broadcasts only go to users who opted into marketing.') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: Content --}}
                        <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-5 py-3 border-b border-slate-200/70 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold dark:text-slate-400">{{ __('Step 2 · Content') }}</p>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <label for="broadcast_subject" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Subject line') }}</label>
                                    <input id="broadcast_subject" type="text" name="subject" value="{{ old('subject') }}" placeholder="{{ __('Happy Newroz from YallaSpare') }}" required maxlength="160"
                                           class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 font-semibold focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <div class="mt-1 flex items-center justify-between text-[10px] font-mono text-slate-400">
                                        <span>{{ __('keep it short and clear') }}</span>
                                        <span><span id="subject-counter">0</span> / 160</span>
                                    </div>
                                </div>

                                <div>
                                    <label for="broadcast_message" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Message body') }}</label>
                                    <div class="rounded-xl border border-slate-200 overflow-hidden focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-slate-700">
                                        {{-- Toolbar (markdown-lite, plain text insertion only) --}}
                                        <div class="rt-toolbar flex items-center flex-wrap gap-0.5 border-b border-slate-200 bg-slate-50/60 px-2 py-1.5 dark:border-slate-800 dark:bg-slate-950">
                                            <button type="button" data-md="bold" title="{{ __('Bold (Markdown)') }}" class="rt-btn"><i class="fas fa-bold text-xs"></i></button>
                                            <button type="button" data-md="italic" title="{{ __('Italic (Markdown)') }}" class="rt-btn"><i class="fas fa-italic text-xs"></i></button>
                                            <button type="button" data-md="strike" title="{{ __('Strike') }}" class="rt-btn"><i class="fas fa-strikethrough text-xs"></i></button>
                                            <span class="rt-sep"></span>
                                            <button type="button" data-md="h1" title="{{ __('Heading') }}" class="rt-btn"><i class="fas fa-heading text-xs"></i></button>
                                            <span class="rt-sep"></span>
                                            <button type="button" data-md="ul" title="{{ __('Bullet list') }}" class="rt-btn"><i class="fas fa-list-ul text-xs"></i></button>
                                            <button type="button" data-md="ol" title="{{ __('Numbered list') }}" class="rt-btn"><i class="fas fa-list-ol text-xs"></i></button>
                                            <button type="button" data-md="quote" title="{{ __('Quote') }}" class="rt-btn"><i class="fas fa-quote-right text-xs"></i></button>
                                            <span class="rt-sep"></span>
                                            <button type="button" data-md="link" title="{{ __('Link') }}" class="rt-btn"><i class="fas fa-link text-xs"></i></button>
                                            <button type="button" data-md="divider" title="{{ __('Divider') }}" class="rt-btn"><i class="fas fa-minus text-xs"></i></button>
                                            <span class="rt-sep"></span>
                                            <button type="button" data-md="line-break" title="{{ __('Line break') }}" class="rt-btn"><i class="fas fa-paragraph text-xs"></i></button>
                                            <span class="ml-auto"></span>
                                            <span class="font-mono text-[10px] text-slate-400 pr-2">{{ __('plain text · markdown supported') }}</span>
                                        </div>
                                        <textarea id="broadcast_message" name="message" rows="12" required maxlength="5000"
                                                  placeholder="{{ __('Write the email body. Plain text is safest and line breaks are preserved.') }}"
                                                  class="w-full border-0 bg-white px-4 py-4 text-sm leading-7 focus:outline-none focus:ring-0 dark:bg-slate-900 dark:text-slate-100 resize-y">{{ old('message') }}</textarea>
                                        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50/60 px-3 py-1.5 text-[10px] font-mono text-slate-400 dark:border-slate-800 dark:bg-slate-950">
                                            <span><span id="msg-words">0</span> {{ __('words') }} · <span id="msg-chars">0</span> {{ __('chars') }}</span>
                                            <span><span id="msg-remaining">5000</span> {{ __('remaining') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 3: CTA --}}
                        <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-5 py-3 border-b border-slate-200/70 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold dark:text-slate-400">{{ __('Step 3 · Call to action (optional)') }}</p>
                            </div>
                            <div class="p-5 grid gap-3 md:grid-cols-2">
                                <div>
                                    <label for="action_url" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Button URL') }}</label>
                                    <input id="action_url" type="url" name="action_url" value="{{ old('action_url') }}" placeholder="{{ url('/') }}" maxlength="2048"
                                           class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 font-mono focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <p class="mt-1 text-[10px] font-mono text-slate-400">{{ __('Must point to the YallaSpare website') }}</p>
                                </div>
                                <div>
                                    <label for="action_text" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Button text') }}</label>
                                    <input id="action_text" type="text" name="action_text" value="{{ old('action_text') }}" placeholder="{{ __('Shop now') }}" maxlength="80"
                                           class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 font-semibold focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                            {{ __('Promotional broadcasts only go to verified users who allow email and marketing messages. Single-user broadcasts are sent immediately. Group and all-user broadcasts are queued and require a running queue worker.') }}
                        </div>

                    </div>

                    {{-- RIGHT: sticky preview + actions --}}
                    <aside class="space-y-6 xl:sticky xl:top-24 self-start">

                        {{-- Recipient summary --}}
                        <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-5 py-3 border-b border-slate-200/70 bg-slate-50/60 flex items-center justify-between dark:border-slate-800 dark:bg-slate-900">
                                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold dark:text-slate-400">{{ __('Recipients') }}</p>
                                <span class="font-mono text-[10px] text-slate-400">{{ __('estimated') }}</span>
                            </div>
                            <div class="p-5">
                                <p class="text-3xl font-black text-slate-900 dark:text-white" id="recipient-count">—</p>
                                <p class="text-[11px] text-slate-500 mt-0.5 dark:text-slate-400" id="recipient-label">{{ __('Pick an audience to estimate') }}</p>
                            </div>
                        </div>

                        {{-- Live preview --}}
                        <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-5 py-3 border-b border-slate-200/70 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold dark:text-slate-400">{{ __('Live preview') }}</p>
                            </div>
                            <div class="p-4 bg-slate-100 dark:bg-slate-950">
                                <div class="mx-auto max-w-sm rounded-xl overflow-hidden border border-slate-200 bg-white shadow-sm dark:border-slate-700">
                                    <div class="flex items-center justify-between bg-primary px-4 py-3 text-white">
                                        <span class="text-xs font-bold tracking-wide">YALLASPARE</span>
                                        <span class="font-mono text-[10px] uppercase tracking-[0.16em] text-amber-300">{{ __('BROADCAST') }}</span>
                                    </div>
                                    <div class="h-0.5 bg-amber-500"></div>
                                    <div class="p-4 space-y-3 text-[12px] leading-relaxed">
                                        <p class="font-black text-slate-900 text-base" id="preview-subject">{{ __('Your subject will appear here') }}</p>
                                        <div class="text-slate-700 whitespace-pre-wrap" id="preview-body">{{ __('Your message body will appear here once you start typing.') }}</div>
                                        <div id="preview-cta" style="display:none">
                                            <a class="inline-block rounded-lg bg-primary px-4 py-2 text-white text-xs font-bold" id="preview-cta-text">{{ __('Shop now') }}</a>
                                        </div>
                                        <p class="text-[10px] text-slate-400 pt-2 border-t border-slate-100">YallaSpare · <a class="underline">{{ __('unsubscribe') }}</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                            <div class="flex flex-col gap-2">
                                <button type="submit" @disabled(! $broadcastsAvailable)
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-md hover:bg-primary-hover transition disabled:cursor-not-allowed disabled:bg-slate-400">
                                    <i class="fas fa-paper-plane"></i>
                                    {{ __('Send Broadcast') }}
                                </button>
                                <a href="{{ route('admin.email.index') }}"
                                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                    <i class="fas fa-xmark"></i>
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>

    <style>
        .rt-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:8px; color:#475569; transition:background .12s, color .12s; }
        .rt-btn:hover { background:#f1f5f9; color:#070740; }
        .dark .rt-btn { color:#cbd5e1; }
        .dark .rt-btn:hover { background:#1e293b; color:#fff; }
        .rt-sep { width:1px; height:18px; background:#e2e8f0; margin:0 4px; }
        .dark .rt-sep { background:#334155; }
    </style>

    <script nonce="{{ $cspNonce }}">
        (function () {
            const audienceTiles = document.querySelectorAll('.audience-tile');
            const audienceInput = document.getElementById('audience_type');
            const roleWrap = document.getElementById('audience-role-wrap');
            const userWrap = document.getElementById('audience-user-wrap');

            function updateAudienceUI(value) {
                audienceTiles.forEach(tile => {
                    const isActive = tile.dataset.audience === value;
                    if (isActive) {
                        tile.classList.add('border-2','border-primary','bg-primary/5','text-primary');
                        tile.classList.remove('border','border-slate-200','text-slate-600','hover:bg-slate-50','dark:border-slate-700','dark:text-slate-300','dark:hover:bg-slate-800');
                    } else {
                        tile.classList.remove('border-2','border-primary','bg-primary/5','text-primary');
                        tile.classList.add('border','border-slate-200','text-slate-600','hover:bg-slate-50','dark:border-slate-700','dark:text-slate-300','dark:hover:bg-slate-800');
                    }
                });
                roleWrap.style.display = value === 'role' ? '' : 'none';
                userWrap.style.display = value === 'user' ? '' : 'none';
                updateRecipientEstimate(value);
            }

            audienceTiles.forEach(tile => {
                tile.addEventListener('click', () => {
                    audienceInput.value = tile.dataset.audience;
                    updateAudienceUI(tile.dataset.audience);
                });
            });
            updateAudienceUI(audienceInput.value || 'all');

            // Purpose toggle
            const purposeButtons = document.querySelectorAll('.purpose-btn');
            const purposeInput = document.getElementById('purpose');
            function updatePurposeUI(value) {
                purposeButtons.forEach(b => {
                    const isActive = b.dataset.purpose === value;
                    if (isActive) {
                        b.classList.add('bg-primary','text-white');
                        b.classList.remove('text-slate-600','dark:text-slate-300');
                    } else {
                        b.classList.remove('bg-primary','text-white');
                        b.classList.add('text-slate-600','dark:text-slate-300');
                    }
                });
            }
            purposeButtons.forEach(b => b.addEventListener('click', () => {
                purposeInput.value = b.dataset.purpose;
                updatePurposeUI(b.dataset.purpose);
            }));
            updatePurposeUI(purposeInput.value || 'promotional');

            // Subject counter
            const subject = document.getElementById('broadcast_subject');
            const subjectCounter = document.getElementById('subject-counter');
            const previewSubject = document.getElementById('preview-subject');
            function updateSubject() {
                subjectCounter.textContent = subject.value.length;
                previewSubject.textContent = subject.value.trim() || '{{ __('Your subject will appear here') }}';
            }
            subject.addEventListener('input', updateSubject);
            updateSubject();

            // Message counter + preview
            const message = document.getElementById('broadcast_message');
            const msgWords = document.getElementById('msg-words');
            const msgChars = document.getElementById('msg-chars');
            const msgRemaining = document.getElementById('msg-remaining');
            const previewBody = document.getElementById('preview-body');
            function updateMessage() {
                const v = message.value;
                msgChars.textContent = v.length;
                msgWords.textContent = v.trim() ? v.trim().split(/\s+/).length : 0;
                msgRemaining.textContent = Math.max(0, 5000 - v.length);
                previewBody.textContent = v.trim() || '{{ __('Your message body will appear here once you start typing.') }}';
            }
            message.addEventListener('input', updateMessage);
            updateMessage();

            // CTA preview
            const actionUrl = document.getElementById('action_url');
            const actionText = document.getElementById('action_text');
            const previewCta = document.getElementById('preview-cta');
            const previewCtaText = document.getElementById('preview-cta-text');
            function updateCta() {
                const hasUrl = actionUrl.value.trim().length > 0;
                const txt = actionText.value.trim() || '{{ __('Shop now') }}';
                previewCta.style.display = hasUrl ? '' : 'none';
                previewCtaText.textContent = txt;
            }
            actionUrl.addEventListener('input', updateCta);
            actionText.addEventListener('input', updateCta);
            updateCta();

            // Recipient estimate (purely visual hint, no AJAX)
            function updateRecipientEstimate(audience) {
                const el = document.getElementById('recipient-count');
                const lbl = document.getElementById('recipient-label');
                if (audience === 'user') {
                    el.textContent = '1';
                    lbl.textContent = '{{ __('Single user') }}';
                } else if (audience === 'role') {
                    el.textContent = '—';
                    lbl.textContent = '{{ __('Estimated after role pick') }}';
                } else {
                    el.textContent = '—';
                    lbl.textContent = '{{ __('All eligible verified users') }}';
                }
            }

            // Markdown-lite toolbar (plain text insertion only)
            function wrap(open, close) {
                const s = message.selectionStart, e = message.selectionEnd;
                const sel = message.value.slice(s, e) || 'text';
                const v = message.value.slice(0, s) + open + sel + close + message.value.slice(e);
                message.value = v;
                message.focus();
                message.selectionStart = s + open.length;
                message.selectionEnd = s + open.length + sel.length;
                updateMessage();
            }
            function insertLinePrefix(prefix) {
                const s = message.selectionStart;
                const before = message.value.slice(0, s);
                const after = message.value.slice(s);
                const newlineIdx = before.lastIndexOf('\n') + 1;
                const v = before.slice(0, newlineIdx) + prefix + before.slice(newlineIdx) + after;
                message.value = v;
                message.focus();
                message.selectionStart = message.selectionEnd = s + prefix.length;
                updateMessage();
            }
            function insertBlock(block) {
                const s = message.selectionStart;
                const v = message.value.slice(0, s) + block + message.value.slice(s);
                message.value = v;
                message.focus();
                message.selectionStart = message.selectionEnd = s + block.length;
                updateMessage();
            }
            document.querySelectorAll('[data-md]').forEach(btn => {
                btn.addEventListener('click', () => {
                    switch (btn.dataset.md) {
                        case 'bold': wrap('**','**'); break;
                        case 'italic': wrap('*','*'); break;
                        case 'strike': wrap('~~','~~'); break;
                        case 'h1': insertLinePrefix('# '); break;
                        case 'ul': insertLinePrefix('- '); break;
                        case 'ol': insertLinePrefix('1. '); break;
                        case 'quote': insertLinePrefix('> '); break;
                        case 'link': wrap('[','](https://)'); break;
                        case 'divider': insertBlock('\n\n---\n\n'); break;
                        case 'line-break': insertBlock('\n\n'); break;
                    }
                });
            });
        })();
    </script>
</x-app-layout>
