        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-700 shadow-sm">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
