<x-app-layout>
    <x-slot name="header">{{ $invoice->number }}</x-slot>

    @php
        $cardClass = 'bg-white border border-slate-200/70 rounded-2xl p-5 sm:p-6 bento-shadow';
        $labelClass = 'block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 mb-1.5';
        $ghostButton = 'inline-flex w-full items-center justify-center gap-2 h-10 px-4 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-100 transition dark:hover:bg-slate-800';
        $fieldClass = 'h-10 w-full px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30';
        $shareUrl = $invoice->shareUrl();
        $pdfLocales = ['en' => 'English', 'ar' => 'العربية', 'ku' => 'کوردی'];
    @endphp

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        @include('admin.manual-invoices.partials.hero', [
            'eyebrow' => __('Manual invoice') . ' · ' . $invoice->statusLabel(),
            'title' => $invoice->number,
            'subtitle' => $invoice->customer_name . ' · ' . $invoice->invoice_date?->format('Y-m-d'),
            'actions' => [
                ['href' => route('admin.manual-invoices.index'), 'label' => __('Back to invoices')],
            ],
        ])

        @include('admin.manual-invoices.partials.flash')

        @if ($invoice->isDraft())
            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <p class="font-bold">{{ __('This is a draft.') }}</p>
                <p class="mt-1 text-xs">{{ __('No stock has been deducted and it is not counted as a sale. Finalizing deducts stock for the catalogue items once, records the movement under this invoice number, and locks the invoice.') }}</p>
            </div>
        @elseif ($invoice->isVoid())
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-900">
                <p class="font-bold">{{ __('Voided on :date.', ['date' => $invoice->voided_at?->format('Y-m-d H:i')]) }}</p>
                <p class="mt-1 text-xs">{{ __('This invoice no longer counts as a sale. Stock for its catalogue items was returned and its share link was withdrawn. It is kept for the record.') }}</p>
                <p class="mt-1 text-xs"><span class="font-bold">{{ __('Reason') }}:</span> {{ $invoice->void_reason }}</p>
            </div>
        @else
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                <p class="font-bold">{{ __('Finalized on :date.', ['date' => $invoice->finalized_at?->format('Y-m-d H:i')]) }}</p>
                <p class="mt-1 text-xs">{{ __('Stock was deducted for the catalogue items and the invoice is locked. Later changes to the catalogue or to the customer do not alter it. Only the payment status and the share link can still change.') }}</p>
            </div>
        @endif

        @if (! empty($rateDrift))
            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <p class="font-bold">{{ __('The exchange rate has changed since this draft was priced.') }}</p>
                <p class="mt-1 text-xs">
                    {{ __('Its USD-priced lines use 1 USD = :old IQD; the current rate is 1 USD = :new IQD.', [
                        'old' => \App\Support\Pricing\ExchangeRate::perDollar($rateDrift['old_rate']),
                        'new' => \App\Support\Pricing\ExchangeRate::perDollar($rateDrift['new_rate']),
                    ]) }}
                </p>
                <dl class="mt-3 grid max-w-md grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-xs">
                    <dt>{{ __('Total as priced') }}</dt><dd class="text-end font-bold">{{ $service->money($rateDrift['old_total']) }}</dd>
                    <dt>{{ __('Total at the current rate') }}</dt><dd class="text-end font-bold">{{ $service->money($rateDrift['new_total']) }}</dd>
                    <dt>{{ __('Difference') }}</dt><dd class="text-end font-bold" dir="ltr">{{ $rateDrift['difference'] > 0 ? '+' : '' }}{{ $service->money($rateDrift['difference']) }}</dd>
                </dl>
                <form method="POST" action="{{ route('admin.manual-invoices.reprice', $invoice) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition dark:text-slate-900 dark:hover:bg-slate-100">
                        <i class="fas fa-rotate text-[11px]" aria-hidden="true"></i>{{ __('Recalculate at the current rate') }}
                    </button>
                </form>
                <p class="mt-2 text-[11px]">{{ __('Leaving it as it is keeps the prices already quoted. Either way the rate used is saved on the invoice.') }}</p>
            </div>
        @endif

        @php
            // Profit is only as complete as the costs behind it: a manual
            // line, or a product with no cost entered, has none to subtract.
            $costedItems = $invoice->items->filter(fn ($item) => $item->unit_cost !== null);
            $invoiceCost = (float) $costedItems->sum(fn ($item) => (float) $item->unit_cost * (int) $item->quantity);
            $costedRevenue = (float) $costedItems->sum(fn ($item) => (float) $item->line_total);
            $uncostedLines = $invoice->items->count() - $costedItems->count();
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-4">
            <div class="space-y-4 min-w-0">
                <section class="{{ $cardClass }}">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">{{ __('Customer') }}</h2>
                    <div class="font-bold text-slate-900">{{ $invoice->customer_name }}</div>
                    <div class="mt-1 flex items-center gap-2">
                        <span dir="ltr" class="font-mono text-sm text-slate-700">{{ $invoice->customer_phone }}</span>
                        <a href="{{ \App\Services\Invoices\ManualInvoiceService::whatsappChatUrl($invoice->whatsappNumber()) }}"
                           target="_blank" rel="noopener noreferrer"
                           aria-label="{{ __('Open WhatsApp chat with :name', ['name' => $invoice->customer_name]) }}"
                           title="{{ __('Open WhatsApp chat') }}"
                           class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-100 transition">
                            <i class="fab fa-whatsapp text-sm" aria-hidden="true"></i>
                        </a>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">{{ collect([$invoice->customer_address, $invoice->customer_city, \App\Support\InternationalPhone::countryName($invoice->customer_country)])->filter()->implode(' · ') }}</p>
                </section>

                <section class="{{ $cardClass }}">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">{{ __('Products and services') }}</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500">
                                <tr>
                                    <th scope="col" class="py-2 pe-3 text-start">{{ __('Description') }}</th>
                                    <th scope="col" class="py-2 pe-3 text-start">{{ __('Part code') }}</th>
                                    <th scope="col" class="py-2 pe-3 text-start">{{ __('Quantity') }}</th>
                                    <th scope="col" class="py-2 pe-3 text-end">{{ __('Unit price') }}</th>
                                    <th scope="col" class="py-2 text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($invoice->items as $item)
                                    <tr>
                                        <td class="py-2.5 pe-3">
                                            <div class="font-bold text-slate-900">{{ $item->description }}</div>
                                            <div class="text-[11px] text-slate-500">
                                                @if ($item->product_id)
                                                    {{ __('Catalogue item') }}@if ($invoice->isDraft() && $item->product) · {{ __('In stock: :count', ['count' => (int) $item->product->stock_quantity]) }}@endif
                                                @else
                                                    {{ __('Manual line — no stock is affected') }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-2.5 pe-3 font-mono text-xs text-slate-600">{{ $item->sku ?: '—' }}</td>
                                        <td class="py-2.5 pe-3 text-slate-800">{{ number_format($item->quantity) }}</td>
                                        <td class="py-2.5 pe-3 text-end text-slate-800 whitespace-nowrap">
                                            {{ $service->money((float) $item->unit_price) }}
                                            @if ($item->usd_unit_price !== null && $item->usd_rate_per_100 !== null)
                                                <div class="text-[11px] text-slate-500" dir="ltr">{{ __('$:usd · 1 USD = :rate IQD', ['usd' => number_format((float) $item->usd_unit_price, 2), 'rate' => \App\Support\Pricing\ExchangeRate::perDollar((string) $item->usd_rate_per_100)]) }}</div>
                                            @endif
                                            @if (isset($rateDrift['lines'][$item->id]))
                                                <div class="text-[11px] font-bold text-amber-700">{{ __('At the current rate: :amount', ['amount' => $service->money($rateDrift['lines'][$item->id]['new'])]) }}</div>
                                            @endif
                                        </td>
                                        <td class="py-2.5 text-end font-bold text-slate-900 whitespace-nowrap">{{ $service->money((float) $item->line_total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <dl class="mt-4 ms-auto max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Subtotal') }}</dt><dd class="font-bold text-slate-900">{{ $service->money((float) $invoice->subtotal) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Discount') }}</dt><dd class="font-bold text-slate-900">- {{ $service->money((float) $invoice->discount_amount) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Delivery fee') }}</dt><dd class="font-bold text-slate-900">{{ $service->money((float) $invoice->delivery_fee) }}</dd></div>
                        <div class="flex justify-between gap-3 border-t border-slate-200 pt-3 text-base"><dt class="font-bold text-slate-900">{{ __('Grand total') }}</dt><dd class="font-bold text-slate-900">{{ $service->money((float) $invoice->total) }}</dd></div>
                    </dl>

                    @if ($costedItems->isNotEmpty() && auth()->user()?->can(\App\Models\User::PERMISSION_FINANCE_VIEW))
                        <div class="mt-4 ms-auto max-w-xs rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            <p class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500">{{ __('Staff only — not shown on the invoice') }}</p>
                            <dl class="mt-2 space-y-1.5">
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Cost of goods') }}</dt><dd class="font-bold text-slate-900">{{ $service->money($invoiceCost) }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Net profit') }}</dt><dd class="font-bold text-slate-900" dir="ltr">{{ $service->money($costedRevenue - $invoiceCost - (float) $invoice->discount_amount) }}</dd></div>
                            </dl>
                            <p class="mt-2 text-[11px] text-slate-500">
                                {{ __('Sales of the lines with a purchase price, minus their cost and the discount. Delivery is not counted.') }}
                                @if ($uncostedLines > 0)
                                    {{ __(':count lines have no purchase price and are left out.', ['count' => $uncostedLines]) }}
                                @endif
                            </p>
                        </div>
                    @endif

                    @if ($invoice->notes)
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span class="font-bold">{{ __('Note') }}:</span> {{ $invoice->notes }}
                        </div>
                    @endif
                </section>
            </div>

            <aside class="space-y-4">
                @if ($invoice->isDraft())
                    <section class="{{ $cardClass }} space-y-2">
                        <h2 class="text-sm font-bold text-slate-900 mb-1">{{ __('Draft actions') }}</h2>
                        <a href="{{ route('admin.manual-invoices.edit', $invoice) }}" class="{{ $ghostButton }}">
                            <i class="fas fa-pen text-[11px]" aria-hidden="true"></i>{{ __('Edit draft') }}
                        </a>
                        <form method="POST" action="{{ route('admin.manual-invoices.finalize', $invoice) }}"
                              data-danger-confirm
                              data-danger-title="{{ __('Finalize invoice') }}"
                              data-danger-action="{{ __('Finalize invoice') }}"
                              data-danger-description="{{ __('Stock will be deducted for the catalogue items and the invoice can no longer be edited.') }}">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 h-10 px-4 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition dark:text-slate-900 dark:hover:bg-slate-100">
                                <i class="fas fa-check text-[11px]" aria-hidden="true"></i>{{ __('Finalize invoice') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.manual-invoices.destroy', $invoice) }}"
                              data-danger-confirm
                              data-danger-title="{{ __('Delete draft') }}"
                              data-danger-description="{{ __('This draft will be deleted. Nothing else is affected.') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 h-10 px-4 rounded-xl border border-rose-200 bg-rose-50 text-xs font-bold text-rose-600 hover:bg-rose-100 transition">
                                <i class="fas fa-trash-can text-[11px]" aria-hidden="true"></i>{{ __('Delete draft') }}
                            </button>
                        </form>
                    </section>
                @endif

                @if (! $invoice->isDraft())
                    <section class="{{ $cardClass }}">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h2 class="text-sm font-bold text-slate-900">{{ __('Payments') }}</h2>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $invoice->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($invoice->payment_status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700') }}">{{ $invoice->paymentStatusLabel() }}</span>
                        </div>
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Grand total') }}</dt><dd class="font-bold text-slate-900">{{ $service->money((float) $invoice->total) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Paid so far') }}</dt><dd class="font-bold text-slate-900">{{ $service->money((float) $invoice->paid_amount) }}</dd></div>
                            <div class="flex justify-between gap-3 border-t border-slate-200 pt-2"><dt class="font-bold text-slate-900">{{ __('Balance due') }}</dt><dd class="font-bold text-slate-900">{{ $service->money($invoice->balance()) }}</dd></div>
                        </dl>

                        @if ($invoice->payments->isNotEmpty())
                            <ul class="mt-3 divide-y divide-slate-100 border-t border-slate-100">
                                @foreach ($invoice->payments as $payment)
                                    <li class="py-2 text-xs">
                                        <div class="flex justify-between gap-3">
                                            <span class="font-bold {{ $payment->isRefund() ? 'text-rose-600' : 'text-slate-900' }}">{{ $payment->isRefund() ? __('Refund') : __('Payment') }}</span>
                                            <span dir="ltr" class="font-bold {{ $payment->isRefund() ? 'text-rose-600' : 'text-slate-900' }}">{{ $service->money((float) $payment->amount) }}</span>
                                        </div>
                                        <div class="text-slate-500 mt-0.5">
                                            {{ $payment->paid_on?->format('Y-m-d') }}@if ($payment->method) · {{ \App\Models\ManualInvoicePayment::methodLabels()[$payment->method] ?? $payment->method }}@endif @if ($payment->recorder) · {{ $payment->recorder->name }}@endif
                                        </div>
                                        @if ($payment->note)
                                            <div class="text-slate-600 mt-0.5">{{ $payment->note }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($invoice->isFinalized())
                            <form method="POST" action="{{ route('admin.manual-invoices.payments.store', $invoice) }}" class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                                @csrf
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label for="payment_kind" class="{{ $labelClass }}">{{ __('Entry') }}</label>
                                        <select id="payment_kind" name="kind" class="{{ $fieldClass }}">
                                            <option value="payment" @selected(old('kind', 'payment') === 'payment')>{{ __('Payment') }}</option>
                                            <option value="refund" @selected(old('kind') === 'refund')>{{ __('Refund') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="payment_amount" class="{{ $labelClass }}">{{ __('Amount') }} ({{ $service->currencyLabel() }})</label>
                                        <input id="payment_amount" type="number" name="amount" min="0" step="any" required
                                               value="{{ old('amount', $invoice->balance() > 0 ? $invoice->balance() : '') }}" class="{{ $fieldClass }}">
                                    </div>
                                    <div>
                                        <label for="payment_date" class="{{ $labelClass }}">{{ __('Date') }}</label>
                                        <input id="payment_date" type="date" name="paid_on" required max="{{ now()->format('Y-m-d') }}"
                                               value="{{ old('paid_on', now()->format('Y-m-d')) }}" class="{{ $fieldClass }}">
                                    </div>
                                    <div>
                                        <label for="payment_method" class="{{ $labelClass }}">{{ __('Method') }}</label>
                                        <select id="payment_method" name="method" class="{{ $fieldClass }}">
                                            <option value="">—</option>
                                            @foreach (\App\Models\ManualInvoicePayment::methodLabels() as $value => $label)
                                                <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="payment_note" class="{{ $labelClass }}">{{ __('Note (optional)') }}</label>
                                    <input id="payment_note" type="text" name="note" maxlength="255" value="{{ old('note') }}" class="{{ $fieldClass }}">
                                </div>
                                <button type="submit" class="inline-flex w-full items-center justify-center h-10 px-4 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition dark:text-slate-900 dark:hover:bg-slate-100">{{ __('Record entry') }}</button>
                                <p class="text-[11px] text-slate-500">{{ __('The status is worked out from these entries. A refund is kept as its own line; nothing is erased.') }}</p>
                            </form>
                        @endif
                    </section>
                @endif

                <section class="{{ $cardClass }}">
                    <h2 class="text-sm font-bold text-slate-900 mb-1">{{ __('PDF and print') }}</h2>
                    <p class="text-[11px] text-slate-500 mb-3">{{ __('Choose the language the invoice is written in.') }}</p>
                    <div class="space-y-2">
                        @foreach ($pdfLocales as $code => $name)
                            <div class="flex items-center gap-2">
                                <span class="flex-1 text-sm font-bold text-slate-800">{{ $name }}</span>
                                <a href="{{ route('admin.manual-invoices.pdf', ['manual_invoice' => $invoice, 'doc_lang' => $code]) }}"
                                   aria-label="{{ __('Download PDF') }} — {{ $name }}"
                                   class="inline-flex h-9 items-center gap-1.5 px-3 rounded-lg border border-slate-200 bg-white text-[11px] font-bold text-slate-700 hover:bg-slate-100 transition dark:hover:bg-slate-800">
                                    <i class="fas fa-download text-[10px]" aria-hidden="true"></i>{{ __('Download PDF') }}
                                </a>
                                <a href="{{ route('admin.manual-invoices.pdf', ['manual_invoice' => $invoice, 'doc_lang' => $code, 'inline' => 1]) }}"
                                   target="_blank" rel="noopener"
                                   aria-label="{{ __('Print') }} — {{ $name }}"
                                   class="inline-flex h-9 items-center gap-1.5 px-3 rounded-lg border border-slate-200 bg-white text-[11px] font-bold text-slate-700 hover:bg-slate-100 transition dark:hover:bg-slate-800">
                                    <i class="fas fa-print text-[10px]" aria-hidden="true"></i>{{ __('Print') }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="{{ $cardClass }}">
                    <h2 class="text-sm font-bold text-slate-900 mb-1">{{ __('Share with the customer') }}</h2>

                    @if ($invoice->isVoid())
                        <p class="text-xs text-slate-500">{{ __('A void invoice cannot be shared.') }}</p>
                    @elseif ($invoice->isDraft())
                        <p class="text-xs text-slate-500">{{ __('Finalize the invoice before sharing it.') }}</p>
                    @elseif (! $shareUrl)
                        <p class="text-[11px] text-slate-500 mb-3">{{ __('Creates a private link that opens this invoice only. It needs no sign-in, cannot be guessed, and can be revoked at any time.') }}</p>
                        <form method="POST" action="{{ route('admin.manual-invoices.share', $invoice) }}">
                            @csrf
                            <button type="submit" class="{{ $ghostButton }}">
                                <i class="fas fa-link text-[11px]" aria-hidden="true"></i>{{ __('Create share link') }}
                            </button>
                        </form>
                    @else
                        <label for="shareLink" class="{{ $labelClass }} mt-2">{{ __('Private link') }}</label>
                        <div class="flex items-center gap-2">
                            <input id="shareLink" type="text" readonly dir="ltr" value="{{ $shareUrl }}"
                                   class="h-10 w-full min-w-0 px-3 rounded-xl border border-slate-200 bg-slate-50 font-mono text-[11px] text-slate-700">
                            <button type="button" id="shareLinkCopy" data-copied="{{ __('Copied') }}"
                                    class="h-10 px-3 rounded-xl border border-slate-200 bg-white text-[11px] font-bold text-slate-700 hover:bg-slate-100 transition dark:hover:bg-slate-800">{{ __('Copy') }}</button>
                        </div>

                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                           class="mt-3 inline-flex w-full items-center justify-center gap-2 h-11 px-4 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition">
                            <i class="fab fa-whatsapp text-base" aria-hidden="true"></i>{{ __('Send via WhatsApp') }}
                        </a>
                        <ul class="mt-3 space-y-1 text-[11px] text-slate-500 list-disc ps-4">
                            <li>{{ __('WhatsApp opens with the message written. Review it and press send yourself — nothing is sent automatically.') }}</li>
                            <li>{{ __('The PDF is not attached. The message carries the link; to send the file itself, download the PDF and attach it in WhatsApp.') }}</li>
                            <li>{{ __('The link opens this invoice only. It shows no other invoice and no customer list.') }}</li>
                        </ul>

                        <form method="POST" action="{{ route('admin.manual-invoices.share.revoke', $invoice) }}" class="mt-3"
                              data-danger-confirm
                              data-danger-title="{{ __('Revoke share link') }}"
                              data-danger-action="{{ __('Revoke share link') }}"
                              data-danger-description="{{ __('The link already sent to the customer will stop working. You can create a new one afterwards.') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 h-10 px-4 rounded-xl border border-rose-200 bg-rose-50 text-xs font-bold text-rose-600 hover:bg-rose-100 transition">
                                <i class="fas fa-link-slash text-[11px]" aria-hidden="true"></i>{{ __('Revoke share link') }}
                            </button>
                        </form>
                    @endif
                </section>

                @if ($invoice->isFinalized())
                    <section class="{{ $cardClass }}">
                        <h2 class="text-sm font-bold text-slate-900 mb-1">{{ __('Void invoice') }}</h2>
                        <p class="text-[11px] text-slate-500 mb-3">{{ __('For a sale that was cancelled or entered by mistake. Stock for the catalogue items is returned once, the share link is withdrawn, and the invoice is kept marked as void.') }}</p>
                        @if ($invoice->paid_amount > 0)
                            <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">{{ __('Refund the :paid already paid before voiding this invoice.', ['paid' => $service->money((float) $invoice->paid_amount)]) }}</p>
                        @else
                            <form method="POST" action="{{ route('admin.manual-invoices.void', $invoice) }}" class="space-y-2"
                                  data-danger-confirm
                                  data-danger-title="{{ __('Void invoice') }}"
                                  data-danger-action="{{ __('Void invoice') }}"
                                  data-danger-description="{{ __('The invoice will be marked void and its stock returned. This cannot be undone.') }}">
                                @csrf
                                <label for="void_reason" class="{{ $labelClass }}">{{ __('Reason') }}</label>
                                <input id="void_reason" type="text" name="void_reason" required minlength="3" maxlength="500" value="{{ old('void_reason') }}" class="{{ $fieldClass }}">
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 h-10 px-4 rounded-xl border border-rose-200 bg-rose-50 text-xs font-bold text-rose-600 hover:bg-rose-100 transition">
                                    <i class="fas fa-ban text-[11px]" aria-hidden="true"></i>{{ __('Void invoice') }}
                                </button>
                            </form>
                        @endif
                    </section>
                @endif
            </aside>
        </div>

    </div>
    </div>
    </div>

    @if ($shareUrl)
        <script nonce="{{ $cspNonce }}">
            (function () {
                const button = document.getElementById('shareLinkCopy');
                const input = document.getElementById('shareLink');
                if (!button || !input) { return; }

                button.addEventListener('click', () => {
                    const done = () => {
                        const label = button.textContent;
                        button.textContent = button.dataset.copied;
                        setTimeout(() => { button.textContent = label; }, 1500);
                    };
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(input.value).then(done).catch(() => input.select());
                    } else {
                        input.select();
                    }
                });
            })();
        </script>
    @endif
</x-app-layout>
