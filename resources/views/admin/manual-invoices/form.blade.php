<x-app-layout>
    <x-slot name="header">{{ $invoice ? __('Edit Draft Invoice') : __('New Invoice') }}</x-slot>

    @php
        $inputBase = 'h-11 w-full px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-muted transition focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900';
        $cellInput = 'h-10 w-full px-2.5 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-900 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900';
        $labelClass = 'block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 mb-1.5';
        $cardClass = 'bg-white border border-slate-200/70 rounded-2xl p-5 sm:p-6 bento-shadow';
        $decimals = $service->currencyDecimals();
        $currency = $service->currencyLabel();
    @endphp

    {{-- On a phone or a tablet a five-column table means editing sideways. Below the
         laptop breakpoint each line becomes a card instead: description on
         top, then code, quantity and price, then the total and the remove
         button. The placeholders stand in for the hidden column headings. --}}
    <style>
        @media (max-width: 1023px) {
            #invoiceTable { min-width: 0; }
            #invoiceTable thead { display: none; }
            #invoiceRows tr { display: grid; grid-template-columns: 1fr 1fr; gap: 0 4px; border: 1px solid #e2e8f0; border-radius: 12px; padding: 8px; margin-bottom: 8px; }
            #invoiceRows td { display: block; padding: 4px; }
            #invoiceRows td:first-child { grid-column: 1 / -1; }
            #invoiceRows td:nth-child(2) { grid-column: 1 / -1; }
            #invoiceRows td:nth-child(5) { text-align: start; }
            #invoiceRows td:nth-child(6) { text-align: end; }
        }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        @include('admin.manual-invoices.partials.hero', [
            'eyebrow' => __('Sales · In store and by phone'),
            'title' => $invoice ? __('Edit draft :number', ['number' => $invoice->number]) : __('New Invoice'),
            'subtitle' => __('Pick a customer, add products or services, then save as a draft or finalize.'),
            'actions' => [
                ['href' => $invoice ? route('admin.manual-invoices.show', $invoice) : route('admin.manual-invoices.index'), 'label' => __('Back')],
            ],
        ])

        @include('admin.manual-invoices.partials.flash')

        <form id="invoiceForm" method="POST"
              action="{{ $invoice ? route('admin.manual-invoices.update', $invoice) : route('admin.manual-invoices.store') }}"
              class="space-y-4">
            @csrf
            @if ($invoice)
                @method('PUT')
            @endif

            {{-- ═════════════ Customer ═════════════ --}}
            <section class="{{ $cardClass }}">
                <h2 class="text-sm font-bold text-slate-900">{{ __('Customer') }}</h2>
                <p class="text-[11px] text-slate-500 mb-4">{{ __('Search the directory by name or phone, or add a new customer without leaving this page.') }}</p>

                <input type="hidden" name="customer_id" id="customerId" value="{{ $customer?->id }}">

                <div id="customerSelected" class="{{ $customer ? 'flex' : 'hidden' }} flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <div class="min-w-0">
                        <div id="customerSelectedName" class="font-bold text-slate-900">{{ $customer?->name }}</div>
                        <div class="text-xs text-slate-600 mt-0.5">
                            <span id="customerSelectedPhone" dir="ltr" class="font-mono">{{ $customer?->phone }}</span>
                            <span id="customerSelectedCity">{{ $customer ? collect([$customer->city, \App\Support\InternationalPhone::countryName($customer->country)])->filter()->map(fn ($part) => ' · '.$part)->implode('') : '' }}</span>
                        </div>
                    </div>
                    <button type="button" id="customerChange" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-100 transition dark:hover:bg-slate-800">{{ __('Change customer') }}</button>
                </div>

                <div id="customerPicker" class="{{ $customer ? 'hidden' : '' }}">
                    <label for="customerSearch" class="{{ $labelClass }}">{{ __('Search by name or phone') }}</label>
                    <input id="customerSearch" type="search" autocomplete="off" class="{{ $inputBase }}">
                    <ul id="customerResults" class="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-200 hidden"></ul>
                    <p id="customerNoResults" class="mt-2 text-xs text-slate-500 hidden">{{ __('No customer found. You can add them below.') }}</p>

                    <button type="button" id="customerNewToggle" aria-expanded="false" aria-controls="customerNew"
                            class="mt-3 inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-100 transition dark:hover:bg-slate-800">
                        <i class="fas fa-user-plus text-[11px]" aria-hidden="true"></i>
                        {{ __('New Customer') }}
                    </button>

                    <div id="customerNew" class="hidden mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label for="newCustomerName" class="{{ $labelClass }}">{{ __('Full name or business name') }}</label>
                                <input id="newCustomerName" type="text" maxlength="160" class="{{ $inputBase }}">
                            </div>
                            <div>
                                <label for="newCustomerPhone" class="{{ $labelClass }}">{{ __('Phone') }}</label>
                                @include('admin.customers.partials.phone-field', [
                                    'id' => 'newCustomerPhone', 'field' => 'phone', 'label' => __('Phone'),
                                    'nameless' => true, 'placeholder' => '0770 123 4567',
                                ])
                            </div>
                            <div>
                                <label for="newCustomerWhatsapp" class="{{ $labelClass }}">{{ __('WhatsApp number') }}</label>
                                @include('admin.customers.partials.phone-field', [
                                    'id' => 'newCustomerWhatsapp', 'field' => 'whatsapp', 'label' => __('WhatsApp number'),
                                    'nameless' => true,
                                ])
                            </div>
                            <div>
                                <label for="newCustomerAddressCountry" class="{{ $labelClass }}">{{ __('Country') }}</label>
                                <select id="newCustomerAddressCountry" class="{{ $inputBase }}">
                                    @foreach (\App\Support\InternationalPhone::countries() as $iso => $country)
                                        <option value="{{ $iso }}" @selected($iso === \App\Support\InternationalPhone::DEFAULT_COUNTRY)>{{ $country['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="newCustomerCity" class="{{ $labelClass }}">{{ __('City / Governorate') }}</label>
                                <input id="newCustomerCity" type="text" maxlength="120" class="{{ $inputBase }}">
                            </div>
                            <div class="md:col-span-2">
                                <label for="newCustomerAddress" class="{{ $labelClass }}">{{ __('Full address') }}</label>
                                <input id="newCustomerAddress" type="text" maxlength="1000" class="{{ $inputBase }}">
                            </div>
                        </div>
                        <p id="customerNewError" role="alert" class="hidden mt-3 text-xs font-medium text-rose-600"></p>
                        <div class="mt-3 flex items-center gap-3">
                            <button type="button" id="customerNewSave" class="h-9 px-4 rounded-lg bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition dark:text-slate-900 dark:hover:bg-slate-100">{{ __('Save Customer') }}</button>
                            <span class="text-[11px] text-slate-500">{{ __('No site account is created and no message is sent.') }}</span>
                        </div>
                    </div>
                </div>
                <p id="customerNotice" role="status" class="hidden mt-3 text-xs font-medium text-amber-700"></p>
            </section>

            {{-- ═════════════ Lines ═════════════ --}}
            <section class="{{ $cardClass }}">
                <h2 class="text-sm font-bold text-slate-900">{{ __('Products and services') }}</h2>
                <p class="text-[11px] text-slate-500 mb-4">{{ __('Catalogue products bring their name, part code and price. Use a manual line for a service or a part that is not in the catalogue.') }}</p>

                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end">
                    <div>
                        <label for="productSearch" class="{{ $labelClass }}">{{ __('Add from catalogue') }}</label>
                        <input id="productSearch" type="search" autocomplete="off" placeholder="{{ __('Name, SKU, OEM or part number') }}" class="{{ $inputBase }}">
                    </div>
                    <button type="button" id="addManualRow" class="h-11 px-4 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-100 transition dark:hover:bg-slate-800">
                        <i class="fas fa-plus text-[11px]" aria-hidden="true"></i>
                        {{ __('Add manual line') }}
                    </button>
                </div>
                <ul id="productResults" class="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-200 hidden"></ul>
                <p id="productNoResults" class="mt-2 text-xs text-slate-500 hidden">{{ __('No product found. Add it as a manual line instead.') }}</p>

                <div class="mt-4 overflow-x-auto">
                    <table id="invoiceTable" class="min-w-[720px] w-full text-sm">
                        <thead class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500">
                            <tr>
                                <th scope="col" class="px-1.5 py-2 text-start">{{ __('Description') }}</th>
                                <th scope="col" class="px-1.5 py-2 text-start w-36">{{ __('Part code') }}</th>
                                <th scope="col" class="px-1.5 py-2 text-start w-24">{{ __('Quantity') }}</th>
                                <th scope="col" class="px-1.5 py-2 text-start w-36">{{ __('Unit price') }}</th>
                                <th scope="col" class="px-1.5 py-2 text-end w-36">{{ __('Total') }}</th>
                                <th scope="col" class="px-1.5 py-2 w-10"><span class="sr-only">{{ __('Remove') }}</span></th>
                            </tr>
                        </thead>
                        <tbody id="invoiceRows"></tbody>
                    </table>
                </div>
                <p id="invoiceRowsEmpty" class="mt-2 rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-xs text-slate-500">{{ __('No lines yet. Add a product or a manual line.') }}</p>

                <template id="invoiceRowTemplate">
                    <tr class="align-top">
                        <td class="px-1.5 py-1.5">
                            <input type="hidden" data-field="product_id">
                            <input type="text" data-field="description" maxlength="255" required aria-label="{{ __('Description') }}" placeholder="{{ __('Description') }}" class="{{ $cellInput }}">
                            <p data-stock class="hidden mt-1 text-[11px] text-slate-500"></p>
                        </td>
                        <td class="px-1.5 py-1.5"><input type="text" data-field="sku" maxlength="120" aria-label="{{ __('Part code') }}" placeholder="{{ __('Part code') }}" class="{{ $cellInput }}"></td>
                        <td class="px-1.5 py-1.5"><input type="number" data-field="quantity" min="1" step="1" required aria-label="{{ __('Quantity') }}" placeholder="{{ __('Quantity') }}" class="{{ $cellInput }}"></td>
                        <td class="px-1.5 py-1.5"><input type="number" data-field="unit_price" min="0" step="any" required aria-label="{{ __('Unit price') }}" placeholder="{{ __('Unit price') }}" class="{{ $cellInput }}"></td>
                        <td class="px-1.5 py-1.5 text-end font-bold text-slate-900 whitespace-nowrap leading-10" data-line-total></td>
                        <td class="px-1.5 py-1.5">
                            <button type="button" data-remove aria-label="{{ __('Remove line') }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-rose-500 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition">
                                <i class="fas fa-trash-can text-[11px]" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            </section>

            {{-- ═════════════ Details and totals ═════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-4">
                <section class="{{ $cardClass }}">
                    <h2 class="text-sm font-bold text-slate-900 mb-4">{{ __('Invoice details') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="invoice_date" class="{{ $labelClass }}">{{ __('Invoice date') }}</label>
                            <input id="invoice_date" type="date" name="invoice_date" required
                                   value="{{ old('invoice_date', $invoice?->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="{{ $inputBase }}">
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-[11px] text-slate-500 self-end">
                            {{ __('Payments are recorded on the invoice after it is finalized. The paid, partial or unpaid status follows from them.') }}
                        </div>
                        <div>
                            <label for="discount_amount" class="{{ $labelClass }}">{{ __('Discount') }} ({{ $currency }})</label>
                            <input id="discount_amount" type="number" name="discount_amount" min="0" step="any"
                                   value="{{ old('discount_amount', $invoice ? (float) $invoice->discount_amount : 0) }}" class="{{ $inputBase }}">
                        </div>
                        <div>
                            <label for="delivery_fee" class="{{ $labelClass }}">{{ __('Delivery fee') }} ({{ $currency }})</label>
                            <input id="delivery_fee" type="number" name="delivery_fee" min="0" step="any"
                                   value="{{ old('delivery_fee', $invoice ? (float) $invoice->delivery_fee : 0) }}" class="{{ $inputBase }}">
                        </div>
                        <div class="md:col-span-2">
                            <label for="notes" class="{{ $labelClass }}">{{ __('Note (optional)') }}</label>
                            <textarea id="notes" name="notes" rows="3" maxlength="2000"
                                      class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900">{{ old('notes', $invoice?->notes) }}</textarea>
                        </div>
                    </div>
                </section>

                <aside class="{{ $cardClass }} h-fit">
                    <h2 class="text-sm font-bold text-slate-900 mb-4">{{ __('Totals') }}</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Subtotal') }}</dt><dd id="totalSubtotal" class="font-bold text-slate-900"></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Discount') }}</dt><dd id="totalDiscount" class="font-bold text-slate-900"></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Delivery fee') }}</dt><dd id="totalDelivery" class="font-bold text-slate-900"></dd></div>
                        <div class="flex justify-between gap-3 border-t border-slate-200 pt-3 text-base"><dt class="font-bold text-slate-900">{{ __('Grand total') }}</dt><dd id="totalGrand" class="font-bold text-slate-900"></dd></div>
                    </dl>
                    <p class="mt-3 text-[11px] text-slate-500">{{ __('A preview. The saved totals are recalculated on the server.') }}</p>
                </aside>
            </div>

            {{-- ═════════════ What saving does ═════════════ --}}
            <section class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <p class="font-bold">{{ __('Draft or finalized?') }}</p>
                <ul class="mt-1.5 space-y-1 text-xs list-disc ps-5">
                    <li>{{ __('A draft changes nothing: no stock is deducted and it is not counted as a sale. You can edit or delete it.') }}</li>
                    <li>{{ __('Finalizing deducts stock for catalogue items, records the movement under the invoice number, and locks the invoice. It happens once — pressing it again does nothing.') }}</li>
                    <li>{{ __('If a catalogue item does not have enough stock, the invoice stays a draft and nothing is deducted.') }}</li>
                </ul>
            </section>

            <div class="sticky bottom-0 z-10 -mx-4 sm:-mx-6 lg:-mx-8 border-t border-slate-200 bg-white/90 px-4 py-4 backdrop-blur dark:bg-slate-900/80">
                <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-end gap-3">
                    <a href="{{ $invoice ? route('admin.manual-invoices.show', $invoice) : route('admin.manual-invoices.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:hover:bg-slate-800">{{ __('Cancel') }}</a>
                    <button type="submit" name="action" value="draft" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-100 dark:hover:bg-slate-800">{{ __('Save as draft') }}</button>
                    <button type="submit" name="action" value="finalize" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:text-slate-900 dark:hover:bg-slate-100">{{ __('Save and finalize') }}</button>
                </div>
            </div>
        </form>

    </div>
    </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        (function () {
            const config = {
                rows: @json($rows),
                decimals: @json($decimals),
                currency: @json($currency),
                customerSearchUrl: @json(route('admin.customers.search')),
                customerStoreUrl: @json(route('admin.customers.store')),
                productSearchUrl: @json(route('admin.manual-invoices.products.search')),
                csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                text: {
                    inStock: @json(__('In stock: :count')),
                    saveFailed: @json(__('The customer could not be saved. Check the name and phone number.')),
                },
            };

            const money = (value) => new Intl.NumberFormat('en-US', {
                minimumFractionDigits: config.decimals,
                maximumFractionDigits: config.decimals,
            }).format(value) + ' ' + config.currency;
            const round = (value) => Math.round(value * 100) / 100;
            const byId = (id) => document.getElementById(id);
            const debounce = (fn, wait) => {
                let timer;
                return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), wait); };
            };
            const getJson = (url, query) => fetch(url + '?q=' + encodeURIComponent(query), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            }).then((response) => (response.ok ? response.json() : { data: [] }));

            // ── Lines ───────────────────────────────────────────────
            const rowsBody = byId('invoiceRows');
            const rowTemplate = byId('invoiceRowTemplate');
            const rowsEmpty = byId('invoiceRowsEmpty');

            const recalculate = () => {
                let subtotal = 0;
                rowsBody.querySelectorAll('tr').forEach((row, index) => {
                    // Renumbered on every change so the server always gets a
                    // gapless items[0..n] whatever was removed.
                    row.querySelectorAll('[data-field]').forEach((input) => {
                        input.name = 'items[' + index + '][' + input.dataset.field + ']';
                    });
                    const quantity = parseInt(row.querySelector('[data-field="quantity"]').value, 10) || 0;
                    const price = parseFloat(row.querySelector('[data-field="unit_price"]').value) || 0;
                    const lineTotal = round(quantity * price);
                    subtotal += lineTotal;
                    row.querySelector('[data-line-total]').textContent = money(lineTotal);
                });

                const discount = Math.max(parseFloat(byId('discount_amount').value) || 0, 0);
                const delivery = Math.max(parseFloat(byId('delivery_fee').value) || 0, 0);
                byId('totalSubtotal').textContent = money(round(subtotal));
                byId('totalDiscount').textContent = '- ' + money(discount);
                byId('totalDelivery').textContent = money(delivery);
                byId('totalGrand').textContent = money(round(subtotal - discount + delivery));
                rowsEmpty.classList.toggle('hidden', rowsBody.children.length > 0);
            };

            const addRow = (data) => {
                const row = rowTemplate.content.firstElementChild.cloneNode(true);
                const set = (field, value) => { row.querySelector('[data-field="' + field + '"]').value = value ?? ''; };
                set('product_id', data.product_id);
                set('description', data.description);
                set('sku', data.sku);
                set('quantity', data.quantity || 1);
                set('unit_price', data.unit_price ?? 0);

                if (data.stock !== undefined && data.stock !== null) {
                    const stock = row.querySelector('[data-stock]');
                    stock.textContent = config.text.inStock.replace(':count', data.stock);
                    stock.classList.remove('hidden');
                }

                row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); recalculate(); });
                row.addEventListener('input', recalculate);
                rowsBody.appendChild(row);
                recalculate();

                return row;
            };

            (Array.isArray(config.rows) ? config.rows : Object.values(config.rows || {})).forEach(addRow);
            byId('addManualRow').addEventListener('click', () => addRow({}).querySelector('[data-field="description"]').focus());
            ['discount_amount', 'delivery_fee'].forEach((id) => byId(id).addEventListener('input', recalculate));
            recalculate();

            // ── Catalogue search ───────────────────────────────────
            const productSearch = byId('productSearch');
            const productResults = byId('productResults');
            const productNoResults = byId('productNoResults');

            const resultButton = (title, detail) => {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex w-full flex-wrap items-center justify-between gap-2 px-3 py-2.5 text-start text-sm hover:bg-slate-50 dark:hover:bg-slate-800';
                const name = document.createElement('span');
                name.className = 'font-bold text-slate-900';
                name.textContent = title;
                const meta = document.createElement('span');
                meta.className = 'text-xs text-slate-500';
                meta.dir = 'auto';
                meta.textContent = detail;
                button.append(name, meta);
                item.appendChild(button);

                return { item, button };
            };

            productSearch.addEventListener('input', debounce(() => {
                const query = productSearch.value.trim();
                productResults.replaceChildren();
                productNoResults.classList.add('hidden');
                if (query.length < 2) { productResults.classList.add('hidden'); return; }

                getJson(config.productSearchUrl, query).then(({ data }) => {
                    productResults.replaceChildren();
                    data.forEach((product) => {
                        const { item, button } = resultButton(
                            product.name,
                            [product.sku, money(product.price), config.text.inStock.replace(':count', product.stock)].filter(Boolean).join(' · ')
                        );
                        button.addEventListener('click', () => {
                            addRow({ product_id: product.id, description: product.name, sku: product.sku, quantity: 1, unit_price: product.price, stock: product.stock });
                            productSearch.value = '';
                            productResults.classList.add('hidden');
                        });
                        productResults.appendChild(item);
                    });
                    productResults.classList.toggle('hidden', data.length === 0);
                    productNoResults.classList.toggle('hidden', data.length > 0);
                });
            }, 250));

            // Enter in a search box must not submit the invoice.
            [productSearch, byId('customerSearch')].forEach((input) => input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') { event.preventDefault(); }
            }));

            // ── Customer ───────────────────────────────────────────
            const customerId = byId('customerId');
            const customerPicker = byId('customerPicker');
            const customerSelected = byId('customerSelected');
            const customerResults = byId('customerResults');
            const customerNoResults = byId('customerNoResults');
            const customerNotice = byId('customerNotice');
            const customerNew = byId('customerNew');
            const customerNewToggle = byId('customerNewToggle');
            const customerNewError = byId('customerNewError');

            const selectCustomer = (customer, notice) => {
                customerId.value = customer.id;
                byId('customerSelectedName').textContent = customer.name;
                byId('customerSelectedPhone').textContent = customer.phone;
                byId('customerSelectedCity').textContent = [customer.city, customer.country_name].filter(Boolean).map((part) => ' · ' + part).join('');
                customerSelected.classList.replace('hidden', 'flex');
                customerPicker.classList.add('hidden');
                customerNotice.textContent = notice || '';
                customerNotice.classList.toggle('hidden', !notice);
            };

            byId('customerChange').addEventListener('click', () => {
                customerId.value = '';
                customerSelected.classList.replace('flex', 'hidden');
                customerPicker.classList.remove('hidden');
                customerNotice.classList.add('hidden');
                byId('customerSearch').focus();
            });

            byId('customerSearch').addEventListener('input', debounce(() => {
                const query = byId('customerSearch').value.trim();
                customerResults.replaceChildren();
                customerNoResults.classList.add('hidden');
                if (query.length < 2) { customerResults.classList.add('hidden'); return; }

                getJson(config.customerSearchUrl, query).then(({ data }) => {
                    customerResults.replaceChildren();
                    data.forEach((customer) => {
                        const { item, button } = resultButton(customer.name, [customer.phone, customer.city, customer.country_name].filter(Boolean).join(' · '));
                        button.addEventListener('click', () => selectCustomer(customer));
                        customerResults.appendChild(item);
                    });
                    customerResults.classList.toggle('hidden', data.length === 0);
                    customerNoResults.classList.toggle('hidden', data.length > 0);
                });
            }, 250));

            customerNewToggle.addEventListener('click', () => {
                const open = customerNew.classList.toggle('hidden') === false;
                customerNewToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) { byId('newCustomerName').focus(); }
            });

            byId('customerNewSave').addEventListener('click', () => {
                customerNewError.classList.add('hidden');

                fetch(config.customerStoreUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    body: JSON.stringify({
                        name: byId('newCustomerName').value,
                        phone: byId('newCustomerPhone').value,
                        phone_country: byId('newCustomerPhoneCountry').value,
                        whatsapp: byId('newCustomerWhatsapp').value,
                        whatsapp_country: byId('newCustomerWhatsappCountry').value,
                        country: byId('newCustomerAddressCountry').value,
                        city: byId('newCustomerCity').value,
                        address: byId('newCustomerAddress').value,
                    }),
                }).then((response) => response.json().then((body) => ({ ok: response.ok, body }))).then(({ ok, body }) => {
                    if (!ok) {
                        const messages = body.errors ? Object.values(body.errors).flat() : [body.message || config.text.saveFailed];
                        customerNewError.textContent = messages.join(' ');
                        customerNewError.classList.remove('hidden');
                        return;
                    }
                    // An existing number comes back as the customer who already
                    // has it, with a note saying so, rather than a duplicate.
                    selectCustomer(body.data, body.existing ? body.message : '');
                    customerNew.classList.add('hidden');
                    customerNewToggle.setAttribute('aria-expanded', 'false');
                }).catch(() => {
                    customerNewError.textContent = config.text.saveFailed;
                    customerNewError.classList.remove('hidden');
                });
            });
        })();
    </script>
</x-app-layout>
