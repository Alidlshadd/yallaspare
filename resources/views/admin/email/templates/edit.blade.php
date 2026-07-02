<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.email.templates.index') }}"
                   class="h-10 w-10 rounded-xl border border-slate-200 bg-white text-slate-600 grid place-items-center hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                   title="{{ __('Back to templates') }}">
                    <i class="fas fa-arrow-left text-xs"></i>
                </a>
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-400 font-bold leading-none">
                        <a href="{{ route('admin.email.index') }}" class="hover:text-primary">{{ __('Email Center') }}</a>
                        <span class="mx-1 text-slate-300">/</span>
                        <a href="{{ route('admin.email.templates.index') }}" class="hover:text-primary">{{ __('Template Editor') }}</a>
                        <span class="mx-1 text-slate-300">/</span>
                        <span class="text-primary dark:text-white">{{ $meta['title'] }}</span>
                    </p>
                    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mt-1">
                        {{ $meta['title'] }}
                        <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-primary/10 text-primary px-2.5 py-1 text-xs font-bold border border-primary/20 dark:bg-primary/20 dark:text-indigo-200 dark:border-primary/40">
                            <i class="fas fa-globe text-[10px]"></i> {{ strtoupper($locale) }}
                        </span>
                    </h2>
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

        @if(! $tableExists)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                {{ __('Email templates table is not installed yet. Run the pending migrations to enable saving edits.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.email.templates.update', ['key' => $templateKey, 'locale' => $locale]) }}"
              id="template-editor" class="grid gap-6 xl:grid-cols-[1.2fr_1fr]">
            @csrf
            @method('PATCH')

            {{-- LEFT: Editor --}}
            <div class="space-y-4">
                {{-- Locale switcher --}}
                <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-700 dark:bg-slate-900 w-fit">
                    @foreach(\App\Models\EmailTemplate::LOCALES as $loc)
                        <a href="{{ route('admin.email.templates.edit', ['key' => $templateKey, 'locale' => $loc]) }}"
                           class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold uppercase tracking-wider transition {{ $loc === $locale ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            {{ $loc }}
                        </a>
                    @endforeach
                </div>

                {{-- Subject --}}
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="border-b border-slate-200/70 px-5 py-3 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold dark:text-slate-400">{{ __('Subject line') }}</p>
                    </div>
                    <div class="p-5">
                        <input type="text" name="subject" id="editor-subject" value="{{ $subject }}" required maxlength="255" placeholder="{{ $defaults['subject'] }}"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 font-semibold text-base focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 transition dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @error('subject')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                        <p class="mt-2 text-[10px] font-mono text-slate-400">{{ __('Default') }}: {{ $defaults['subject'] }}</p>
                    </div>
                </div>

                {{-- Body editor --}}
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-3 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold dark:text-slate-400">{{ __('Body (HTML)') }}</p>
                        <div class="rt-toolbar flex items-center flex-wrap gap-0.5">
                            <button type="button" data-md="bold" title="{{ __('Bold') }}" class="rt-btn"><i class="fas fa-bold text-xs"></i></button>
                            <button type="button" data-md="italic" title="{{ __('Italic') }}" class="rt-btn"><i class="fas fa-italic text-xs"></i></button>
                            <button type="button" data-md="strike" title="{{ __('Strike') }}" class="rt-btn"><i class="fas fa-strikethrough text-xs"></i></button>
                            <span class="rt-sep"></span>
                            <button type="button" data-md="h1" title="{{ __('Heading') }}" class="rt-btn"><i class="fas fa-heading text-xs"></i></button>
                            <span class="rt-sep"></span>
                            <button type="button" data-md="ul" title="{{ __('Bullet list') }}" class="rt-btn"><i class="fas fa-list-ul text-xs"></i></button>
                            <button type="button" data-md="ol" title="{{ __('Numbered list') }}" class="rt-btn"><i class="fas fa-list-ol text-xs"></i></button>
                            <button type="button" data-md="link" title="{{ __('Link') }}" class="rt-btn"><i class="fas fa-link text-xs"></i></button>
                            <span class="rt-sep"></span>
                            <button type="button" data-md="p" title="{{ __('Paragraph') }}" class="rt-btn"><i class="fas fa-paragraph text-xs"></i></button>
                        </div>
                    </div>
                    <div class="p-5">
                        <textarea name="body_html" id="editor-body" rows="16" required maxlength="65000"
                                  class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 font-mono text-[13px] leading-relaxed focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 transition dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 resize-y">{{ $body_html }}</textarea>
                        @error('body_html')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                        <p class="mt-2 text-[10px] font-mono text-slate-400">
                            {{ __('Allowed tags') }}: p, br, strong, b, em, i, u, s, a, ul, ol, li, h1-h4, blockquote, hr, span, div.
                            {{ __('Placeholders') }}: <code class="text-primary">{name}</code>, <code class="text-primary">{code}</code>, <code class="text-primary">{url}</code>, <code class="text-primary">{email}</code>.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                    <i class="fas fa-lightbulb"></i>
                    {{ __('Only the subject and body are editable — the email chrome (logo, hero, footer) stays fixed. Variables in curly braces get substituted at send time.') }}
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    <button type="submit" @disabled(! $tableExists)
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-primary to-indigo-700 px-5 py-3 text-sm font-bold text-white shadow-md shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition disabled:cursor-not-allowed disabled:from-slate-400 disabled:to-slate-500 disabled:shadow-none">
                        <i class="fas fa-check"></i> {{ __('Save template') }}
                    </button>
                    <a href="{{ route('admin.email.templates.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i class="fas fa-xmark"></i> {{ __('Cancel') }}
                    </a>
                    @if($override)
                        <span class="ml-auto font-mono text-[10px] text-slate-400">
                            {{ __('Last edit') }}: {{ $override->updated_at->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Live preview --}}
            <aside class="space-y-4 xl:sticky xl:top-24 self-start">
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-3 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                        <div class="flex items-center gap-2">
                            <div class="h-7 w-7 rounded-lg bg-emerald-100 text-emerald-700 grid place-items-center dark:bg-emerald-900/50 dark:text-emerald-200">
                                <i class="fas fa-eye text-[10px]"></i>
                            </div>
                            <p class="text-sm font-bold text-primary leading-none dark:text-white">{{ __('Live preview') }}</p>
                        </div>
                        <span class="font-mono text-[10px] uppercase tracking-widest text-slate-400">{{ __('sample data') }}</span>
                    </div>
                    <div class="bg-slate-100 p-3 dark:bg-slate-950">
                        <div class="mx-auto max-w-md overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm dark:border-slate-700">
                            <div class="flex items-center justify-between bg-primary px-4 py-3 text-white">
                                <span class="text-xs font-bold tracking-wide">YALLASPARE</span>
                                <span class="font-mono text-[9px] uppercase tracking-[0.16em] text-amber-300">{{ strtoupper($templateKey) }}</span>
                            </div>
                            <div class="h-0.5 bg-amber-500"></div>
                            <div class="p-4 space-y-3">
                                <p class="font-black text-slate-900 text-base leading-snug" id="preview-subject">{{ $subject }}</p>
                                <div class="prose prose-sm max-w-none text-slate-700 leading-relaxed" id="preview-body">
                                    {!! $body_html !!}
                                </div>
                                <p class="text-[10px] text-slate-400 pt-2 border-t border-slate-100">YallaSpare · <a class="underline">{{ __('unsubscribe') }}</a></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="border-b border-slate-200/70 px-5 py-3 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold dark:text-slate-400">{{ __('Sample variables') }}</p>
                    </div>
                    <div class="p-4 space-y-1">
                        @php
                            $sampleVars = [
                                'brand' => 'YallaSpare',
                                'name' => 'Ahmed Al-Khalidi',
                                'email' => 'customer@example.com',
                                'code' => '847293',
                                'url' => url('/'),
                            ];
                        @endphp
                        @foreach($sampleVars as $k => $v)
                            <div class="flex items-center justify-between gap-3 text-[11px] font-mono">
                                <code class="text-primary dark:text-indigo-300">{{ '{' . $k . '}' }}</code>
                                <span class="text-slate-500 dark:text-slate-400 truncate">{{ $v }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </form>

    </div>
    </div>
    </div>

    <style>
        .rt-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; color:#475569; transition:background .12s, color .12s; }
        .rt-btn:hover { background:#f1f5f9; color:#070740; }
        .dark .rt-btn { color:#cbd5e1; }
        .dark .rt-btn:hover { background:#1e293b; color:#fff; }
        .rt-sep { width:1px; height:16px; background:#e2e8f0; margin:0 4px; }
        .dark .rt-sep { background:#334155; }
        #preview-body h1 { font-size:1.5rem; font-weight:800; color:#070740; margin:0.5rem 0; }
        #preview-body h2 { font-size:1.25rem; font-weight:700; color:#070740; margin:0.5rem 0; }
        #preview-body h3 { font-size:1.1rem; font-weight:700; color:#070740; margin:0.5rem 0; }
        #preview-body p { margin:0.75rem 0; }
        #preview-body ul { list-style:disc; padding-left:1.5rem; margin:0.5rem 0; }
        #preview-body ol { list-style:decimal; padding-left:1.5rem; margin:0.5rem 0; }
        #preview-body a { color:#070740; text-decoration:underline; }
        #preview-body strong, #preview-body b { font-weight:700; }
    </style>

    <script nonce="{{ $cspNonce }}">
    (function () {
        var subject = document.getElementById('editor-subject');
        var body = document.getElementById('editor-body');
        var previewSubject = document.getElementById('preview-subject');
        var previewBody = document.getElementById('preview-body');

        var sampleVars = {
            brand: 'YallaSpare',
            name: 'Ahmed Al-Khalidi',
            email: 'customer@example.com',
            code: '847293',
            url: '/',
            expires: '60',
            order: 'YS-104482',
            tracking: 'AR-9837-4471-IQ',
            status: 'approved',
            tier: '8%',
            device: 'Chrome 134 / Windows 11',
            ip: '93.184.216.34',
            count: '3',
            topic: 'Order issue',
            subject: 'Wrong part received'
        };

        function interpolate(html) {
            for (var token in sampleVars) {
                var re = new RegExp('\\{' + token + '\\}', 'g');
                html = html.replace(re, sampleVars[token]);
            }
            return html;
        }

        // Mirror server-side sanitize(): strip <script>/<iframe>/etc, on* handlers,
        // javascript: URLs. Belt-and-suspenders — the server sanitizes before saving,
        // but the preview should never execute arbitrary content the admin pastes.
        function sanitizeForPreview(html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var forbidden = ['script','iframe','object','embed','style','link','meta','base','form','input','button','textarea','select','frame','frameset'];
            forbidden.forEach(function (tag) {
                doc.querySelectorAll(tag).forEach(function (node) { node.remove(); });
            });
            doc.querySelectorAll('*').forEach(function (node) {
                Array.prototype.slice.call(node.attributes).forEach(function (attr) {
                    var name = attr.name.toLowerCase();
                    var value = String(attr.value || '');
                    if (name.indexOf('on') === 0) node.removeAttribute(attr.name);
                    if ((name === 'href' || name === 'src' || name === 'action') && /^\s*javascript:/i.test(value)) node.removeAttribute(attr.name);
                });
            });
            return doc.body.innerHTML;
        }

        function updatePreview() {
            previewSubject.textContent = subject.value.trim() || '{{ __('Your subject will appear here') }}';
            previewBody.innerHTML = sanitizeForPreview(interpolate(body.value));
        }

        subject.addEventListener('input', updatePreview);
        body.addEventListener('input', updatePreview);
        updatePreview();

        // Toolbar
        function wrap(open, close) {
            var s = body.selectionStart, e = body.selectionEnd;
            var sel = body.value.slice(s, e) || 'text';
            var v = body.value.slice(0, s) + open + sel + close + body.value.slice(e);
            body.value = v;
            body.focus();
            body.selectionStart = s + open.length;
            body.selectionEnd = s + open.length + sel.length;
            updatePreview();
        }
        function insertBlock(block) {
            var s = body.selectionStart;
            var v = body.value.slice(0, s) + block + body.value.slice(s);
            body.value = v;
            body.focus();
            body.selectionStart = body.selectionEnd = s + block.length;
            updatePreview();
        }
        document.querySelectorAll('[data-md]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                switch (btn.dataset.md) {
                    case 'bold':   wrap('<strong>', '</strong>'); break;
                    case 'italic': wrap('<em>', '</em>'); break;
                    case 'strike': wrap('<s>', '</s>'); break;
                    case 'h1':     wrap('<h2>', '</h2>'); break;
                    case 'ul':     insertBlock('<ul>\n  <li>item</li>\n  <li>item</li>\n</ul>'); break;
                    case 'ol':     insertBlock('<ol>\n  <li>step</li>\n  <li>step</li>\n</ol>'); break;
                    case 'link':   wrap('<a href="{url}">', '</a>'); break;
                    case 'p':      insertBlock('\n<p></p>\n'); break;
                }
            });
        });
    })();
    </script>
</x-app-layout>
