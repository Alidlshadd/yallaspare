// Picking one part out of hundreds that share a name.
//
// The fitment form used a filter box beside a native <select>. The select drew
// the operating system's own popup — a flat list of text — and several products
// are called "SsangYong Engine Air Filter", so the only thing separating them
// was a SKU buried in the same line. Choosing the wrong one attaches a fitment
// to the wrong part, and nothing on the page says so afterwards.
//
// This replaces both controls with one combobox whose results carry the photo,
// the numbers, the price and the stock. The native <select> stays in the DOM
// and stays what submits, so the form still posts a real product_id.

const DEBOUNCE_MS = 300;
const MIN_QUERY_LENGTH = 2;

const drawSvg = (path, width) => {
    const node = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    node.setAttribute('viewBox', '0 0 20 20');
    node.setAttribute('fill', 'none');
    node.setAttribute('stroke', 'currentColor');
    node.setAttribute('stroke-width', String(width));
    node.setAttribute('stroke-linecap', 'round');
    node.setAttribute('stroke-linejoin', 'round');

    const shape = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    shape.setAttribute('d', path);
    node.appendChild(shape);

    return node;
};

const drawSearch = () => drawSvg('M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11Zm3.9 9.4 3.6 3.6', 1.7);
const drawChevron = () => drawSvg('m5.5 7.5 4.5 4.5 4.5-4.5', 1.8);
const drawCheck = () => drawSvg('m4.5 10.5 3.5 3.5 7.5-8', 2.1);
const drawClose = () => drawSvg('m5.5 5.5 9 9m0-9-9 9', 1.8);
const drawBox = () => drawSvg('M4 6.5 10 3.5l6 3v7l-6 3-6-3v-7Zm0 0 6 3m0 0 6-3m-6 3v7', 1.4);

export const initAdminProductPicker = () => {
    const roots = Array.from(document.querySelectorAll('[data-product-picker]'));
    if (roots.length === 0) {
        return;
    }

    roots.forEach((root) => setup(root));
};

const setup = (root) => {
    const select = root.querySelector('select[data-product-picker-select]');
    const endpoint = root.dataset.searchUrl || '';

    if (!select || endpoint === '') {
        return;
    }

    const text = {
        placeholder: root.dataset.placeholderLabel || 'Search and select a product',
        searching: root.dataset.searchingLabel || 'Searching products…',
        empty: root.dataset.emptyLabel || 'No matching products found',
        emptyHint: root.dataset.emptyHintLabel || 'Try a product name, SKU or OEM number.',
        initial: root.dataset.initialLabel || 'Start typing to find a product',
        error: root.dataset.errorLabel || 'Products could not be loaded. Try again.',
        search: root.dataset.searchLabel || 'Search products',
        change: root.dataset.changeLabel || 'Change',
        clear: root.dataset.clearLabel || 'Clear',
        sku: root.dataset.skuLabel || 'SKU',
        oem: root.dataset.oemLabel || 'OEM',
        units: root.dataset.unitsLabel || 'units',
        selected: root.dataset.selectedLabel || 'Selected',
    };

    const id = 'product-picker-' + Math.random().toString(36).slice(2, 9);

    // ------------------------------------------------------------ the control
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'ys-picker-trigger';
    trigger.id = id + '-trigger';
    trigger.setAttribute('role', 'combobox');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', id + '-listbox');
    trigger.setAttribute('aria-label', text.placeholder);

    const triggerIcon = document.createElement('span');
    triggerIcon.className = 'ys-picker-trigger-icon';
    triggerIcon.setAttribute('aria-hidden', 'true');
    triggerIcon.appendChild(drawSearch());

    const triggerText = document.createElement('span');
    triggerText.className = 'ys-picker-trigger-text';
    triggerText.textContent = text.placeholder;

    const triggerChevron = document.createElement('span');
    triggerChevron.className = 'ys-picker-chevron';
    triggerChevron.setAttribute('aria-hidden', 'true');
    triggerChevron.appendChild(drawChevron());

    trigger.append(triggerIcon, triggerText, triggerChevron);

    // The chosen product, kept on screen so the operator can see the part they
    // are attaching vehicles to rather than trusting a line of text.
    const card = document.createElement('div');
    card.className = 'ys-picker-card';
    card.hidden = true;

    const panel = document.createElement('div');
    panel.className = 'ys-picker-panel';
    panel.hidden = true;

    const searchWrap = document.createElement('div');
    searchWrap.className = 'ys-picker-search';

    const searchIcon = document.createElement('span');
    searchIcon.className = 'ys-picker-search-icon';
    searchIcon.setAttribute('aria-hidden', 'true');
    searchIcon.appendChild(drawSearch());

    const search = document.createElement('input');
    search.type = 'text';
    search.className = 'ys-picker-search-input';
    search.setAttribute('autocomplete', 'off');
    search.setAttribute('aria-label', text.search);
    search.placeholder = text.search;

    searchWrap.append(searchIcon, search);

    const list = document.createElement('ul');
    list.className = 'ys-picker-list';
    list.id = id + '-listbox';
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', text.search);

    const status = document.createElement('p');
    status.className = 'ys-picker-status';
    status.setAttribute('role', 'status');

    panel.append(searchWrap, list, status);

    const shell = document.createElement('div');
    shell.className = 'ys-picker';
    shell.append(trigger, card, panel);

    select.parentNode.insertBefore(shell, select);
    shell.appendChild(select);

    select.classList.add('ys-picker-native');
    select.setAttribute('tabindex', '-1');
    select.setAttribute('aria-hidden', 'true');

    // ------------------------------------------------------------------ state
    let items = [];
    let activeIndex = -1;
    let isOpen = false;
    let debounceTimer = null;
    let inFlight = null;
    let lastQuery = null;
    let cachedRows = new Map();

    const setStatus = (message, tone = 'muted') => {
        status.textContent = message;
        status.dataset.tone = tone;
        status.hidden = message === '';
    };

    // ------------------------------------------------------------ result rows
    const thumbnail = (row) => {
        const host = document.createElement('span');
        host.className = 'ys-picker-thumb';

        if (row.image_url) {
            const img = document.createElement('img');
            img.src = row.image_url;
            img.alt = '';
            img.loading = 'lazy';
            img.decoding = 'async';
            // A dead URL must not leave a broken-image glyph in the list.
            img.addEventListener('error', () => {
                img.remove();
                host.appendChild(placeholderGlyph());
            }, { once: true });
            host.appendChild(img);
        } else {
            host.appendChild(placeholderGlyph());
        }

        return host;
    };

    const placeholderGlyph = () => {
        const glyph = document.createElement('span');
        glyph.className = 'ys-picker-thumb-placeholder';
        glyph.setAttribute('aria-hidden', 'true');
        glyph.appendChild(drawBox());

        return glyph;
    };

    const stockBadge = (row) => {
        const badge = document.createElement('span');
        badge.className = 'ys-picker-badge is-' + (row.stock_state || 'in_stock');

        const label = row.stock_state === 'in_stock' && Number.isFinite(row.stock_quantity)
            ? `${row.stock_label} · ${row.stock_quantity} ${text.units}`
            : row.stock_label;

        badge.textContent = label;

        return badge;
    };

    const metaLine = (row) => {
        const parts = [];
        if (row.sku) parts.push(`${text.sku}: ${row.sku}`);
        if (row.oem) parts.push(`${text.oem}: ${row.oem}`);

        return parts.join(' · ');
    };

    const renderRows = (rows) => {
        list.innerHTML = '';
        items = [];
        activeIndex = -1;

        rows.forEach((row, index) => {
            cachedRows.set(String(row.id), row);

            const item = document.createElement('li');
            item.className = 'ys-picker-option';
            item.id = `${id}-option-${index}`;
            item.setAttribute('role', 'option');
            item.dataset.value = String(row.id);

            const selected = String(row.id) === String(select.value);
            item.setAttribute('aria-selected', selected ? 'true' : 'false');
            item.classList.toggle('is-selected', selected);

            if (row.selectable === false) {
                item.classList.add('is-unselectable');
                item.setAttribute('aria-disabled', 'true');
            }

            const body = document.createElement('span');
            body.className = 'ys-picker-option-body';

            const name = document.createElement('span');
            name.className = 'ys-picker-option-name';
            name.textContent = row.name;
            body.appendChild(name);

            const meta = metaLine(row);
            if (meta !== '') {
                const metaNode = document.createElement('span');
                metaNode.className = 'ys-picker-option-meta';
                metaNode.textContent = meta;
                body.appendChild(metaNode);
            }

            const tail = document.createElement('span');
            tail.className = 'ys-picker-option-tail';
            if (row.price_formatted) {
                const price = document.createElement('span');
                price.className = 'ys-picker-option-price';
                price.textContent = row.price_formatted;
                tail.appendChild(price);
            }
            if (row.brand) {
                const brand = document.createElement('span');
                brand.className = 'ys-picker-option-brand';
                brand.textContent = row.brand;
                tail.appendChild(brand);
            }
            body.appendChild(tail);

            const side = document.createElement('span');
            side.className = 'ys-picker-option-side';
            side.appendChild(stockBadge(row));

            const check = document.createElement('span');
            check.className = 'ys-picker-option-check';
            check.setAttribute('aria-hidden', 'true');
            check.appendChild(drawCheck());
            side.appendChild(check);

            item.append(thumbnail(row), body, side);
            list.appendChild(item);
            items.push(item);
        });

        const selectedIndex = items.findIndex((item) => item.dataset.value === String(select.value));
        setActive(selectedIndex >= 0 ? selectedIndex : 0);
    };

    const setActive = (index) => {
        if (items.length === 0) {
            activeIndex = -1;
            trigger.removeAttribute('aria-activedescendant');
            search.removeAttribute('aria-activedescendant');

            return;
        }

        const bounded = Math.max(0, Math.min(index, items.length - 1));
        items.forEach((item) => item.classList.remove('is-active'));

        const active = items[bounded];
        active.classList.add('is-active');
        activeIndex = bounded;
        search.setAttribute('aria-activedescendant', active.id);
        active.scrollIntoView({ block: 'nearest' });
    };

    // ------------------------------------------------------------- the fetch
    const fetchRows = (query, { ids = null } = {}) => {
        // The same query twice in a row is the same answer.
        const key = ids ? `ids:${ids}` : `q:${query}`;
        if (key === lastQuery) {
            return Promise.resolve(null);
        }
        lastQuery = key;

        if (inFlight) {
            inFlight.abort();
        }

        const controller = new AbortController();
        inFlight = controller;

        const url = new URL(endpoint, window.location.origin);
        if (ids) {
            url.searchParams.set('ids', ids);
        } else {
            url.searchParams.set('q', query);
            url.searchParams.set('per_page', '20');
        }

        setStatus(text.searching, 'busy');

        return fetch(url, {
            signal: controller.signal,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then((payload) => {
                // A slower earlier request must never overwrite a newer answer.
                if (controller !== inFlight) {
                    return null;
                }

                inFlight = null;

                return Array.isArray(payload.results) ? payload.results : [];
            })
            .catch((error) => {
                if (error.name === 'AbortError') {
                    return null;
                }

                inFlight = null;
                setStatus(text.error, 'error');

                return null;
            });
    };

    const runSearch = (query) => {
        fetchRows(query).then((rows) => {
            if (rows === null) {
                return;
            }

            renderRows(rows);

            if (rows.length === 0) {
                setStatus(query === '' ? text.initial : `${text.empty} — ${text.emptyHint}`);
            } else {
                setStatus('');
            }
        });
    };

    const scheduleSearch = () => {
        const query = search.value.trim();

        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }

        if (query !== '' && query.length < MIN_QUERY_LENGTH) {
            setStatus(text.initial);

            return;
        }

        debounceTimer = window.setTimeout(() => runSearch(query), DEBOUNCE_MS);
    };

    // ------------------------------------------------------- chosen product
    const renderCard = (row) => {
        card.innerHTML = '';

        if (!row) {
            card.hidden = true;
            trigger.hidden = false;
            triggerText.textContent = text.placeholder;
            trigger.classList.add('is-empty');

            return;
        }

        card.hidden = false;
        trigger.hidden = true;

        const body = document.createElement('div');
        body.className = 'ys-picker-card-body';

        const name = document.createElement('span');
        name.className = 'ys-picker-card-name';
        name.textContent = row.name;
        body.appendChild(name);

        const meta = metaLine(row);
        if (meta !== '') {
            const metaNode = document.createElement('span');
            metaNode.className = 'ys-picker-card-meta';
            metaNode.textContent = meta;
            body.appendChild(metaNode);
        }

        const tail = document.createElement('span');
        tail.className = 'ys-picker-card-meta';
        tail.textContent = [row.price_formatted, row.stock_label].filter(Boolean).join(' · ');
        body.appendChild(tail);

        const actions = document.createElement('div');
        actions.className = 'ys-picker-card-actions';

        const change = document.createElement('button');
        change.type = 'button';
        change.className = 'ys-picker-card-change';
        change.textContent = text.change;
        change.addEventListener('click', () => {
            card.hidden = true;
            trigger.hidden = false;
            open();
        });

        const clear = document.createElement('button');
        clear.type = 'button';
        clear.className = 'ys-picker-card-clear';
        clear.setAttribute('aria-label', text.clear);
        clear.appendChild(drawClose());
        clear.addEventListener('click', () => choose(null));

        actions.append(change, clear);
        card.append(thumbnail(row), body, actions);
    };

    // The preview plate beside the form shows the same product, so the picture
    // an operator is working from is never only inside a closed dropdown.
    const syncPreview = (row) => {
        const host = document.querySelector('[data-admin-preview-product-media]');
        const label = document.querySelector('[data-admin-preview-product]');

        if (label) {
            label.textContent = row ? row.name : (label.dataset.emptyLabel || label.textContent);
        }

        if (!host) {
            return;
        }

        host.innerHTML = '';

        if (!row) {
            host.hidden = true;

            return;
        }

        host.hidden = false;
        const meta = document.createElement('span');
        meta.className = 'ys-picker-preview-meta';
        meta.textContent = metaLine(row);
        host.append(thumbnail(row), meta);
    };

    const choose = (row) => {
        if (row && row.selectable === false) {
            return;
        }

        // Setting .value on a <select> that has no such <option> silently
        // leaves it empty, and the form would post no product at all. Results
        // arrive from the endpoint, so the option usually does not exist yet.
        if (row) {
            const value = String(row.id);

            if (!Array.from(select.options).some((option) => option.value === value)) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = row.name;
                select.appendChild(option);
            }

            select.value = value;
        } else {
            select.value = '';
        }

        select.dispatchEvent(new Event('change', { bubbles: true }));
        renderCard(row);
        syncPreview(row);
        close({ focusTrigger: true });
    };

    // ---------------------------------------------------------------- opening
    const place = () => {
        const rect = trigger.hidden
            ? card.getBoundingClientRect()
            : trigger.getBoundingClientRect();
        const below = window.innerHeight - rect.bottom - 16;
        const above = rect.top - 16;
        const wantsAbove = below < 220 && above > below;

        shell.classList.toggle('is-above', wantsAbove);
        panel.style.maxHeight = `${Math.max(200, Math.min(380, wantsAbove ? above : below))}px`;
    };

    const open = () => {
        if (isOpen) {
            return;
        }

        isOpen = true;
        panel.hidden = false;
        shell.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        place();
        search.focus({ preventScroll: true });

        if (items.length === 0) {
            runSearch(search.value.trim());
        }
    };

    const close = ({ focusTrigger = false } = {}) => {
        if (!isOpen) {
            return;
        }

        isOpen = false;
        panel.hidden = true;
        shell.classList.remove('is-open', 'is-above');
        trigger.setAttribute('aria-expanded', 'false');

        if (focusTrigger && !trigger.hidden) {
            trigger.focus({ preventScroll: true });
        }
    };

    // ----------------------------------------------------------------- events
    trigger.addEventListener('click', () => (isOpen ? close({ focusTrigger: true }) : open()));

    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            open();
        }
    });

    search.addEventListener('input', scheduleSearch);

    search.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive(activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(activeIndex - 1);
        } else if (event.key === 'Enter') {
            // Enter here picks a product. Without this it would submit the
            // whole fitment form from inside the search box.
            event.preventDefault();
            event.stopPropagation();
            const item = items[activeIndex];
            if (item) {
                choose(cachedRows.get(item.dataset.value) || null);
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            close({ focusTrigger: true });
        }
    });

    list.addEventListener('click', (event) => {
        const item = event.target.closest('.ys-picker-option');
        if (item) {
            choose(cachedRows.get(item.dataset.value) || null);
        }
    });

    list.addEventListener('mousemove', (event) => {
        const item = event.target.closest('.ys-picker-option');
        if (item) {
            setActive(items.indexOf(item));
        }
    });

    document.addEventListener('click', (event) => {
        if (isOpen && !shell.contains(event.target)) {
            close();
        }
    });

    window.addEventListener('resize', () => isOpen && place(), { passive: true });
    window.addEventListener('scroll', () => isOpen && place(), { passive: true });

    // ------------------------------------------------- restore any selection
    setStatus(text.initial);
    trigger.classList.add('is-empty');

    if (select.value !== '') {
        // Coming back from a failed validation: redraw the chosen product,
        // photo and all, from its id alone.
        fetchRows('', { ids: select.value }).then((rows) => {
            if (rows && rows.length > 0) {
                cachedRows.set(String(rows[0].id), rows[0]);
                renderCard(rows[0]);
                syncPreview(rows[0]);
            }

            lastQuery = null;
            setStatus(text.initial);
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminProductPicker, { once: true });
} else {
    initAdminProductPicker();
}
