<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ __('Returns & Refunds') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Review return, exchange, and refund requests from delivered orders.') }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                {{ __('Operations') }}
            </span>
        </div>
    </x-slot>

    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
        $currentStatus = (string) request('status', '');
        $currentSearch = (string) request('search', '');

        $statusMeta = [
            'requested' => [
                'label' => __('Requested'),
                'class' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300',
                'dot' => 'bg-amber-500',
            ],
            'approved' => [
                'label' => __('Approved'),
                'class' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-300',
                'dot' => 'bg-blue-500',
            ],
            'rejected' => [
                'label' => __('Rejected'),
                'class' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300',
                'dot' => 'bg-rose-500',
            ],
            'received' => [
                'label' => __('Received'),
                'class' => 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-900/50 dark:bg-cyan-950/30 dark:text-cyan-300',
                'dot' => 'bg-cyan-500',
            ],
            'refunded' => [
                'label' => __('Refunded'),
                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300',
                'dot' => 'bg-emerald-500',
            ],
            'closed' => [
                'label' => __('Closed'),
                'class' => 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                'dot' => 'bg-slate-500',
            ],
        ];

        $typeMeta = [
            'return' => ['label' => __('Return'), 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'],
            'exchange' => ['label' => __('Exchange'), 'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300'],
            'refund' => ['label' => __('Refund'), 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'],
        ];

        $metricCards = [
            [
                'label' => __('Total Requests'),
                'value' => number_format((int) ($stats['total'] ?? 0)),
                'detail' => __('Matching current search'),
                'class' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
                'valueClass' => 'text-slate-900 dark:text-slate-100',
            ],
            [
                'label' => __('Open Workflow'),
                'value' => number_format((int) ($stats['open'] ?? 0)),
                'detail' => __('Requested, approved, or received'),
                'class' => 'border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-950/20',
                'valueClass' => 'text-blue-800 dark:text-blue-200',
            ],
            [
                'label' => __('Refunded'),
                'value' => number_format((int) ($stats['refunded'] ?? 0)),
                'detail' => __('Requests paid back'),
                'class' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/20',
                'valueClass' => 'text-emerald-800 dark:text-emerald-200',
            ],
            [
                'label' => __('Refund Value'),
                'value' => $currencyLabel . ' ' . number_format((float) ($stats['refund_total'] ?? 0), $currencyDecimals),
                'detail' => __('Approved refund amount total'),
                'class' => 'border-violet-200 bg-violet-50 dark:border-violet-900/50 dark:bg-violet-950/20',
                'valueClass' => 'text-violet-800 dark:text-violet-200',
            ],
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($metricCards as $card)
                    <article class="rounded-2xl border p-5 shadow-sm {{ $card['class'] }}">
                        <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold {{ $card['valueClass'] }}">{{ $card['value'] }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $card['detail'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                    <form method="GET" action="{{ route('admin.returns.index') }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto_auto]">
                        <label class="block">
                            <span class="sr-only">{{ __('Search') }}</span>
                            <input
                                name="search"
                                value="{{ $currentSearch }}"
                                placeholder="{{ __('Search order, customer, email, or reason...') }}"
                                class="h-11 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            >
                        </label>

                        <label class="block">
                            <span class="sr-only">{{ __('Status') }}</span>
                            <select name="status" class="h-11 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="">{{ __('All Statuses') }}</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected($currentStatus === $status)>{{ $statusMeta[$status]['label'] ?? __(ucfirst(str_replace('_', ' ', (string) $status))) }}</option>
                                @endforeach
                            </select>
                        </label>

                        <button type="submit" class="h-11 rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                            {{ __('Apply Filters') }}
                        </button>

                        @if($currentSearch !== '' || $currentStatus !== '')
                            <a href="{{ route('admin.returns.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </form>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.returns.index', array_filter(['search' => $currentSearch])) }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $currentStatus === '' ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            {{ __('All') }}
                            <span class="rounded-full bg-current/10 px-1.5 py-0.5">{{ number_format((int) ($stats['total'] ?? 0)) }}</span>
                        </a>
                        @foreach($statuses as $status)
                            @php
                                $count = (int) ($statusCounts->get($status, 0) ?? 0);
                                $meta = $statusMeta[$status] ?? ['label' => __(ucfirst((string) $status)), 'dot' => 'bg-slate-400'];
                            @endphp
                            <a href="{{ route('admin.returns.index', array_filter(['search' => $currentSearch, 'status' => $status])) }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $currentStatus === $status ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                                <span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span>
                                {{ $meta['label'] }}
                                <span class="rounded-full bg-current/10 px-1.5 py-0.5">{{ number_format($count) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Request') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Order') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Customer') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Reason') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                            @forelse($requests as $requestRow)
                                @php
                                    $rowStatusMeta = $statusMeta[$requestRow->status] ?? [
                                        'label' => __(ucfirst(str_replace('_', ' ', (string) $requestRow->status))),
                                        'class' => 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                    ];
                                    $rowTypeMeta = $typeMeta[$requestRow->type] ?? [
                                        'label' => __(ucfirst(str_replace('_', ' ', (string) $requestRow->type))),
                                        'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
                                    ];
                                    $order = $requestRow->order;
                                    $customer = $requestRow->user;
                                    $paymentMeta = $order ? \App\Models\Order::paymentStatusMeta((string) $order->payment_status) : null;
                                @endphp
                                <tr class="align-top hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="px-5 py-5">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $rowTypeMeta['class'] }}">
                                                    {{ $rowTypeMeta['label'] }}
                                                </span>
                                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $rowStatusMeta['class'] }}">
                                                    {{ $rowStatusMeta['label'] }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">#{{ $requestRow->id }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                                    {{ $requestRow->requested_at?->format('M d, Y') ?? '-' }}
                                                    @if($requestRow->requested_at)
                                                        <span class="text-slate-300 dark:text-slate-600">/</span>
                                                        {{ $requestRow->requested_at->format('h:i A') }}
                                                    @endif
                                                </p>
                                                @if($requestRow->resolved_at)
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Resolved') }}: {{ $requestRow->resolved_at->format('M d, Y') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-5">
                                        @if($order)
                                            <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                                                {{ $order->order_number }}
                                            </a>
                                            <p class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format((float) $order->total_amount, $currencyDecimals) }}</p>
                                            @if($paymentMeta)
                                                <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $paymentMeta['class'] }}">
                                                    {{ $paymentMeta['label'] }}
                                                </span>
                                            @endif
                                        @else
                                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Order unavailable') }}</p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-5">
                                        <div class="min-w-[240px] space-y-3">
                                            <div>
                                                @if($customer && Route::has('admin.users.show'))
                                                    <a href="{{ route('admin.users.show', $customer) }}" class="inline-flex max-w-[240px] items-center gap-1 truncate text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                                                        {{ $customer->name }}
                                                    </a>
                                                @else
                                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $customer?->name ?? __('Guest customer') }}</p>
                                                @endif
                                                <p class="mt-1 max-w-[240px] truncate text-xs text-slate-500 dark:text-slate-400">{{ $customer?->email ?? '-' }}</p>
                                            </div>

                                            <dl class="grid gap-2 text-xs">
                                                <div class="flex items-start justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-950">
                                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ __('Account phone') }}</dt>
                                                    <dd class="text-right font-medium text-slate-800 dark:text-slate-200">{{ $customer?->phone ?: '-' }}</dd>
                                                </div>
                                                <div class="flex items-start justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-950">
                                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ __('Delivery phone') }}</dt>
                                                    <dd class="text-right font-medium text-slate-800 dark:text-slate-200">{{ $order?->delivery_phone ?: '-' }}</dd>
                                                </div>
                                                <div class="flex items-start justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-950">
                                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ __('City') }}</dt>
                                                    <dd class="text-right font-medium text-slate-800 dark:text-slate-200">{{ $order?->delivery_city ?: '-' }}</dd>
                                                </div>
                                                @if($order?->delivery_address)
                                                    <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-950">
                                                        <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ __('Address') }}</dt>
                                                        <dd class="mt-1 line-clamp-2 text-slate-800 dark:text-slate-200">{{ $order->delivery_address }}</dd>
                                                    </div>
                                                @endif
                                                @if($customer)
                                                    <div class="flex items-start justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-950">
                                                        <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ __('Role') }}</dt>
                                                        <dd class="text-right font-medium capitalize text-slate-800 dark:text-slate-200">{{ str_replace('_', ' ', (string) $customer->role) }}</dd>
                                                    </div>
                                                @endif
                                            </dl>
                                        </div>
                                    </td>

                                    <td class="max-w-md px-5 py-5">
                                        <p class="line-clamp-4 text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $requestRow->reason }}</p>
                                        @if($requestRow->admin_note)
                                            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">
                                                <span class="font-semibold text-slate-800 dark:text-slate-100">{{ __('Admin note') }}:</span>
                                                {{ $requestRow->admin_note }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-5 py-5">
                                        <form method="POST" action="{{ route('admin.returns.update', $requestRow) }}" class="min-w-[280px] space-y-3">
                                            @csrf
                                            @method('PATCH')

                                            <div class="grid gap-2 sm:grid-cols-2">
                                                <label class="block">
                                                    <span class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Status') }}</span>
                                                    <select name="status" class="h-10 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                        @foreach($statuses as $status)
                                                            <option value="{{ $status }}" @selected($requestRow->status === $status)>{{ $statusMeta[$status]['label'] ?? __(ucfirst(str_replace('_', ' ', (string) $status))) }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>

                                                <label class="block">
                                                    <span class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Refund') }}</span>
                                                    <input
                                                        name="refund_amount"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value="{{ $requestRow->refund_amount }}"
                                                        placeholder="0"
                                                        class="h-10 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                                    >
                                                </label>
                                            </div>

                                            <label class="block">
                                                <span class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Internal note') }}</span>
                                                <textarea name="admin_note" rows="2" placeholder="{{ __('Add handling note for the team') }}" class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ $requestRow->admin_note }}</textarea>
                                            </label>

                                            <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700">
                                                {{ __('Save Workflow') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-14 text-center">
                                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('No return requests found.') }}</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Try changing the search or status filter.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($requests->hasPages())
                    <div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800">
                        {{ $requests->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
