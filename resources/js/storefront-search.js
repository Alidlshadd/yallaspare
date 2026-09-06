// The header search suggestion panel.
//
// Not a list of names any more. A shopper who knows their car but not the part
// number gets four groups back — parts, cars, marques, categories — each row
// saying why it is there and, for a part, which car it is being offered for.
// The server decides all of that; nothing below invents a result, a count or a
// link. Every URL in the panel was built by a named route on the server.
//
// Built with createElement and textContent throughout: no innerHTML, no inline
// handlers, nothing that would need the CSP loosened.
//
// The file is laid out as the collaborators one panel is made of, in the order
// they are needed: translations, DOM helpers, geometry, the option list and its
// keyboard, the announcer, the request runner, the row builders, the renderer,
// and finally the wiring that puts one of each behind one search box.

const MIN_QUERY = 2;
const DEBOUNCE_MS = 300;
const PANEL_MIN_HEIGHT = 220;
const PANEL_MAX_HEIGHT = 520;

let panelCount = 0;

// --------------------------------------------------------------- translations

const readLabels = () => {
    const i18n = window.YallaI18n || {};
    const t = (key, fallback) => (typeof i18n[key] === 'string' && i18n[key] !== '' ? i18n[key] : fallback);

    return {
        products: t('products', 'Products'),
        vehicles: t('vehicles', 'Vehicles'),
        brands: t('brands', 'Brands'),
        categories: t('categories', 'Categories'),
        sku: t('sku', 'SKU:'),
        oem: t('oem', 'OEM:'),
        fits: t('fits', 'Fits'),
        fitsCount: t('fitsCount', 'Fits :count vehicles'),
        viewCompatibleParts: t('viewCompatibleParts', 'View compatible parts'),
        browseBrandParts: t('browseBrandParts', 'Browse brand parts'),
        browseCategoryProducts: t('browseCategoryProducts', 'Browse category products'),
        browseVehicleParts: t('browseVehicleParts', 'Browse :vehicle parts'),
        viewAllResults: t('viewAllResults', 'View all results'),
        didYouMean: t('didYouMean', 'Did you mean'),
        trySearching: t('trySearching', 'Try searching'),
        typeOneMore: t('typeOneMore', 'Type one more character'),
        noExactMatches: t('noExactMatches', 'No exact matches for ":query"'),
        searchWithoutYear: t('searchWithoutYear', 'Search without the year'),
        clearSearch: t('clearSearch', 'Clear search'),
        retry: t('retry', 'Retry'),
        loading: t('loading', 'Loading'),
        errorMessage: t('searchError', 'We could not load suggestions.'),
        suggestions: t('searchSuggestions', 'Search suggestions'),
        vehicle: t('vehicle', 'Vehicle'),
        year: t('year', 'Year'),
        brand: t('brand', 'Brand'),
        engine: t('engine', 'Engine'),
        fuel: t('fuel', 'Fuel'),
    };
};

const readExamples = () => {
    const examples = (window.YallaI18n || {}).searchExamples;

    return Array.isArray(examples)
        ? examples.filter((value) => typeof value === 'string' && value !== '').slice(0, 3)
        : [];
};

// ---------------------------------------------------------------- DOM helpers

const makeNode = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) {
        node.className = className;
    }
    if (text !== undefined && text !== null) {
        node.textContent = String(text);
    }
    return node;
};

// ":count results" and friends, filled the way Laravel would have filled them
// server-side. Only the placeholders the panel actually passes are replaced.
const fillPlaceholders = (template, replacements) => {
    let output = String(template);

    Object.keys(replacements).forEach((key) => {
        output = output.split(`:${key}`).join(String(replacements[key]));
    });

    return output;
};

// SKU and OEM numbers read left to right in every language.
const technicalText = (text) => {
    const node = makeNode('span', 'truncate', text);
    node.dir = 'ltr';
    return node;
};

const accentBar = () => {
    const bar = makeNode('span', 'pointer-events-none absolute inset-y-1.5 start-0 w-[3px] rounded-full bg-accent opacity-0 transition-opacity duration-150');
    bar.setAttribute('aria-hidden', 'true');
    return bar;
};

// ------------------------------------------------------------------- geometry

// The panel is bounded, not the list inside it: the chip bar and the progress
// line are part of what has to fit, and sizing only the list left the panel
// taller than the space it was measured against.
const createGeometry = (input, panel, isOpen) => {
    let frame = null;

    const size = () => {
        const rect = input.getBoundingClientRect();
        const room = window.innerHeight - rect.bottom - 24;

        panel.style.maxHeight = `${Math.max(PANEL_MIN_HEIGHT, Math.min(PANEL_MAX_HEIGHT, room))}px`;
    };

    const queue = () => {
        if (frame !== null) {
            return;
        }

        frame = window.requestAnimationFrame(() => {
            frame = null;
            if (isOpen()) {
                size();
            }
        });
    };

    window.addEventListener('resize', queue, { passive: true });
    window.addEventListener('scroll', queue, { passive: true });

    return { size };
};

// ------------------------------------------------------- options and keyboard

// The listbox half of the combobox: what can be picked, which one is picked,
// and what the arrow keys do about it.
const createOptionList = (input, idPrefix) => {
    let options = [];
    let activeIndex = -1;

    const clear = () => {
        options = [];
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');
    };

    const setActive = (index, { scroll = true } = {}) => {
        if (options.length === 0) {
            activeIndex = -1;
            input.removeAttribute('aria-activedescendant');
            return;
        }

        options.forEach((option, position) => {
            const active = position === index;
            option.element.setAttribute('aria-selected', active ? 'true' : 'false');
            option.element.classList.toggle('bg-surface-1', active);
            option.accent?.classList.toggle('opacity-0', !active);
        });

        activeIndex = index;

        if (index < 0) {
            input.removeAttribute('aria-activedescendant');
            return;
        }

        input.setAttribute('aria-activedescendant', options[index].element.id);

        if (scroll) {
            options[index].element.scrollIntoView({ block: 'nearest' });
        }
    };

    return {
        clear,
        setActive,
        get length() {
            return options.length;
        },
        get activeIndex() {
            return activeIndex;
        },
        register(element, accent, run) {
            const index = options.length;
            element.id = `${idPrefix}-${index}`;
            element.setAttribute('role', 'option');
            element.setAttribute('aria-selected', 'false');
            element.addEventListener('mousemove', () => setActive(index, { scroll: false }));
            options.push({ element, accent, run });
            return element;
        },
        move(delta) {
            if (options.length === 0) {
                return;
            }

            const next = activeIndex < 0
                ? (delta > 0 ? 0 : options.length - 1)
                : (activeIndex + delta + options.length) % options.length;

            setActive(next);
        },
        activate() {
            const option = options[activeIndex];

            if (!option) {
                return false;
            }

            option.run();
            return true;
        },
    };
};

// ------------------------------------------------------------------ announcer

const createAnnouncer = (form) => {
    const live = makeNode('span', 'sr-only');
    live.setAttribute('aria-live', 'polite');
    live.setAttribute('aria-atomic', 'true');
    form.appendChild(live);

    return (message) => {
        live.textContent = message || '';
    };
};

// ------------------------------------------------------------- request runner

// One request at a time, and never an old answer over a newer one. The abort
// stops the network; the ticket stops a response that was already in flight
// when the shopper typed the next letter.
const createRequestRunner = (endpoint) => {
    let controller = null;
    let sequence = 0;
    let timer = null;

    return {
        cancel() {
            window.clearTimeout(timer);
            timer = null;
            controller?.abort();
            controller = null;
        },
        debounce(run) {
            window.clearTimeout(timer);
            timer = window.setTimeout(run, DEBOUNCE_MS);
        },
        send(query, { onSuccess, onFailure }) {
            controller?.abort();
            controller = new AbortController();

            sequence += 1;
            const ticket = sequence;
            const isCurrent = () => ticket === sequence;

            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('q', query);

            fetch(url, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    return response.json();
                })
                .then((json) => {
                    if (!isCurrent()) {
                        return;
                    }

                    if (!json?.data) {
                        throw new Error('empty payload');
                    }

                    onSuccess(json.data);
                })
                .catch((error) => {
                    if (error?.name === 'AbortError' || !isCurrent()) {
                        return;
                    }

                    onFailure();
                });
        },
    };
};

// ---------------------------------------------------------------- row builders

const createRows = (labels, optionList) => {
    const rowShell = (tag, extra) => {
        const element = document.createElement(tag);
        element.className = `relative flex min-h-[44px] w-full items-center gap-3 px-3 text-start transition-colors duration-150 hover:bg-surface-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/60 ${extra}`;

        if (tag === 'button') {
            element.type = 'button';
        }

        // Reachable by arrow key and by mouse, but not by Tab: a combobox popup
        // that puts a dozen stops between the search box and the next control
        // is a worse keyboard experience, not a better one.
        element.tabIndex = -1;

        return element;
    };

    const linkRow = (url, extra) => {
        const row = rowShell('a', extra);
        row.href = url;
        return row;
    };

    const goTo = (url) => () => {
        window.location.assign(url);
    };

    const groupHeading = (text) => {
        const heading = makeNode('div', 'px-3 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-muted', text);
        heading.setAttribute('role', 'presentation');
        return heading;
    };

    // A dot beside the word, so the state does not rest on colour alone. The
    // response carries a state and a translated label and nothing else — the
    // exact stock level is not a shopper's business and not on the wire.
    const stockBadge = (stock) => {
        const state = stock?.state || 'in_stock';
        const tone = state === 'out_of_stock'
            ? 'bg-rose-500/10 text-rose-600 dark:text-rose-300'
            : state === 'low_stock'
                ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300'
                : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';

        const badge = makeNode('span', `inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-semibold ${tone}`);
        const dot = makeNode('span', 'inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-current');
        dot.setAttribute('aria-hidden', 'true');
        badge.append(dot, makeNode('span', '', stock?.label || ''));

        return badge;
    };

    const productThumb = (item) => {
        const media = makeNode('span', 'flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-app bg-surface-1');

        const placeholder = () => {
            media.replaceChildren();
            const mark = makeNode('span', 'text-sm font-semibold text-muted', (item.name || '?').slice(0, 1).toUpperCase());
            mark.setAttribute('aria-hidden', 'true');
            media.appendChild(mark);
        };

        if (!item.image) {
            placeholder();
            return media;
        }

        const image = document.createElement('img');
        image.className = 'h-full w-full object-contain';
        image.loading = 'lazy';
        image.decoding = 'async';
        image.alt = item.name || '';
        // A missing file must read as a product with no picture, never as the
        // browser's broken-image glyph.
        image.addEventListener('error', placeholder, { once: true });
        image.src = item.image;
        media.appendChild(image);

        return media;
    };

    // Why this row is here. When the caption would only repeat the "fits" line
    // below it, it is left out: "Compatible vehicle match" over
    // "Fits: Camry · 2022–2026" says the same thing twice.
    const matchLine = (item) => {
        const line = makeNode('span', 'flex flex-wrap items-center gap-x-2 gap-y-0.5 pt-0.5 text-[11px] leading-snug');
        const fitsLabel = item.fits?.label;
        const fitsCount = item.fits?.vehicle_count;
        const type = item.match?.type;
        const isExact = ['sku_exact', 'oem_exact', 'part_number_exact'].includes(type);

        if (fitsLabel) {
            line.append(makeNode('span', 'font-medium text-app', `${labels.fits}: ${fitsLabel}`));
        } else if ((type === 'fitment' || type === 'vehicle_part_match') && fitsCount > 1) {
            line.append(makeNode('span', 'font-medium text-app', fillPlaceholders(labels.fitsCount, { count: fitsCount })));
        }

        const redundant = type === 'fitment' && Boolean(fitsLabel);

        if (item.match?.label && !redundant) {
            line.append(makeNode('span', isExact ? 'font-semibold text-accent' : 'text-muted', item.match.label));
        }

        return line;
    };

    return {
        groupHeading,

        product(item) {
            const row = linkRow(item.url, 'items-start py-2.5');
            const accent = accentBar();
            const body = makeNode('span', 'flex min-w-0 flex-1 flex-col gap-0.5');

            const titleLine = makeNode('span', 'flex items-start justify-between gap-2');
            titleLine.append(
                makeNode('span', 'line-clamp-2 text-sm font-semibold leading-snug text-app', item.name),
                stockBadge(item.stock),
            );

            const meta = makeNode('span', 'flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted');
            if (item.sku) {
                meta.append(technicalText(`${labels.sku} ${item.sku}`));
            }
            const identifier = item.oem || item.part_number;
            if (identifier && identifier !== item.sku) {
                meta.append(technicalText(`${labels.oem} ${identifier}`));
            }
            if (item.price_formatted) {
                meta.append(makeNode('span', 'font-semibold text-app', item.price_formatted));
            }

            body.append(titleLine, meta);

            const reason = matchLine(item);
            if (reason.childElementCount > 0) {
                body.appendChild(reason);
            }

            row.append(accent, productThumb(item), body);

            return optionList.register(row, accent, goTo(item.url));
        },

        simple(item, detail, action) {
            const row = linkRow(item.url, 'py-2');
            const accent = accentBar();

            const body = makeNode('span', 'flex min-w-0 flex-1 flex-col');
            body.appendChild(makeNode('span', 'truncate text-sm font-semibold text-app', item.name));
            if (detail) {
                body.appendChild(makeNode('span', 'truncate text-xs text-muted', detail));
            }

            row.append(accent, body, makeNode('span', 'shrink-0 text-[11px] font-semibold text-accent', action));

            return optionList.register(row, accent, goTo(item.url));
        },

        link(text, url) {
            const row = linkRow(url, 'py-2');
            const accent = accentBar();
            row.append(accent, makeNode('span', 'flex-1 text-sm font-semibold text-app', text));

            return optionList.register(row, accent, goTo(url));
        },

        action(text, run, tone) {
            const row = rowShell('button', 'py-2');
            const accent = accentBar();

            row.append(accent, makeNode('span', `flex-1 text-sm font-semibold ${tone || 'text-app'}`, text));
            row.addEventListener('click', (event) => {
                event.preventDefault();
                run();
            });

            return optionList.register(row, accent, run);
        },

        correction(correction, apply) {
            const row = rowShell('button', 'py-2.5');
            const accent = accentBar();

            const body = makeNode('span', 'flex min-w-0 flex-1 flex-wrap items-baseline gap-x-1.5');
            body.append(
                makeNode('span', 'text-xs text-muted', labels.didYouMean),
                makeNode('span', 'truncate text-sm font-semibold text-accent', `“${correction.to}”`),
            );

            row.append(accent, body);
            row.addEventListener('click', (event) => {
                event.preventDefault();
                apply();
            });

            return optionList.register(row, accent, apply);
        },

        // Decorative on purpose: the vehicle and brand rows below carry the
        // real links, and a chip that looks like a button but is not one is
        // worse than a chip that looks like a label.
        chip(name, value) {
            const chip = makeNode('span', 'inline-flex max-w-full items-center gap-1 rounded-full border border-app bg-surface-1 px-2.5 py-1 text-[11px] leading-none');
            chip.append(
                makeNode('span', 'text-muted', `${name}:`),
                makeNode('span', 'truncate font-semibold text-app', String(value)),
            );
            return chip;
        },

        skeleton() {
            const row = makeNode('div', 'flex min-h-[44px] items-center gap-3 px-3 py-2.5');
            row.setAttribute('aria-hidden', 'true');
            row.appendChild(makeNode('span', 'h-12 w-12 shrink-0 animate-pulse rounded-xl bg-surface-1'));

            const lines = makeNode('span', 'flex min-w-0 flex-1 flex-col gap-1.5');
            lines.append(
                makeNode('span', 'h-3 w-3/5 animate-pulse rounded-full bg-surface-1'),
                makeNode('span', 'h-2.5 w-2/5 animate-pulse rounded-full bg-surface-1'),
            );

            row.appendChild(lines);
            return row;
        },
    };
};

// --------------------------------------------------------------------- panel

const createSearchPanel = (form, labels, examples) => {
    const input = form.querySelector('[data-search-autocomplete-input]');
    const panel = form.querySelector('[data-search-autocomplete-panel]');
    const endpoint = form.dataset.searchAutocompleteUrl;

    if (!input || !panel || !endpoint) {
        return;
    }

    panelCount += 1;
    const panelId = `search-panel-${panelCount}`;

    // ---------------------------------------------------------------- markup

    panel.replaceChildren();
    panel.id = panelId;
    panel.classList.add('flex', 'flex-col');

    const chipBar = makeNode('div', 'sticky top-0 z-10 hidden flex-wrap items-center gap-1.5 border-b border-app bg-surface-2 px-3 py-2');
    const list = makeNode('div', 'search-panel-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain py-1');
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', labels.suggestions);

    const progress = makeNode('div', 'search-panel-progress hidden');
    progress.setAttribute('aria-hidden', 'true');

    panel.append(progress, chipBar, list);

    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', panelId);
    input.setAttribute('autocomplete', 'off');

    // ------------------------------------------------------------ collaborators

    let isOpen = false;
    let isLoading = false;
    let renderedQuery = null;
    let lastPayload = null;

    const options = createOptionList(input, `${panelId}-option`);
    const rows = createRows(labels, options);
    const geometry = createGeometry(input, panel, () => isOpen);
    const announce = createAnnouncer(form);
    const request = createRequestRunner(endpoint);

    // --------------------------------------------------------------- lifecycle

    const reset = () => {
        options.clear();
        list.replaceChildren();
    };

    const open = () => {
        if (list.childElementCount === 0 && chipBar.childElementCount === 0) {
            close();
            return;
        }

        panel.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
        isOpen = true;
        geometry.size();
    };

    function close() {
        panel.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        options.setActive(-1);
        isOpen = false;
    }

    // Stale rows stay on screen so the panel does not jump, but they stop being
    // selectable: nobody should land on the answer to the query before last.
    const setLoading = (loading) => {
        isLoading = loading;
        progress.classList.toggle('hidden', !loading);
        list.setAttribute('aria-busy', loading ? 'true' : 'false');
        list.classList.toggle('pointer-events-none', loading);
        list.classList.toggle('opacity-50', loading);

        if (loading) {
            options.setActive(-1);
            announce(labels.loading);
        }
    };

    const abort = () => {
        request.cancel();
        setLoading(false);
    };

    // ----------------------------------------------------------------- renders

    const renderChips = (interpreted) => {
        chipBar.replaceChildren();

        if (interpreted) {
            [
                [labels.vehicle, interpreted.vehicle?.label],
                [labels.year, interpreted.year],
                [labels.brand, interpreted.brand?.label],
                [labels.engine, interpreted.engine],
                [labels.fuel, interpreted.fuel],
            ]
                .filter(([, value]) => value !== null && value !== undefined && value !== '')
                .slice(0, 3)
                .forEach(([name, value]) => chipBar.appendChild(rows.chip(name, value)));
        }

        chipBar.classList.toggle('hidden', chipBar.childElementCount === 0);
    };

    const renderGroup = (heading, items, renderItem) => {
        if (!items || items.length === 0) {
            return;
        }

        list.appendChild(rows.groupHeading(heading));
        items.forEach((item) => list.appendChild(renderItem(item)));
    };

    // The server pluralises: English has two forms, Arabic six, and a
    // template in the bundle cannot know which one a number wants.
    const countDetail = (item) => (item?.product_count ? item.product_count_label || '' : '');

    const renderEmpty = (payload) => {
        const query = payload.query || input.value.trim();

        list.appendChild(makeNode(
            'p',
            'px-3 py-3 text-sm font-medium text-app',
            fillPlaceholders(labels.noExactMatches, { query }),
        ));

        // Only what can actually be done from here. An offer that leads nowhere
        // is worse than no offer.
        const vehicle = payload.interpreted?.vehicle;
        if (vehicle?.url && vehicle.label) {
            list.appendChild(rows.link(
                fillPlaceholders(labels.browseVehicleParts, { vehicle: vehicle.label }),
                vehicle.url,
            ));
        }

        if (payload.meta?.without_year_url) {
            list.appendChild(rows.link(labels.searchWithoutYear, payload.meta.without_year_url));
        }

        list.appendChild(rows.action(labels.clearSearch, () => {
            input.value = '';
            input.focus();
            abort();
            renderedQuery = null;
            renderHint();
        }, 'text-muted'));

        announce(fillPlaceholders(labels.noExactMatches, { query }));
    };

    const renderResults = (payload) => {
        const groups = payload.groups || {};
        const products = groups.products || [];
        // Null means the server did not count: it fetched one row past the page
        // and found more, rather than running the whole search again to say how
        // many. The footer then leads there without quoting a number.
        const totalLabel = payload.meta?.total_products_label ?? null;

        renderChips(payload.interpreted);

        if (products.length === 0 && payload.correction) {
            list.appendChild(rows.correction(payload.correction, () => {
                input.value = payload.correction.suggested_query;
                input.focus();
                renderedQuery = null;
                search();
            }));
        }

        if (products.length === 0
            && (groups.vehicles || []).length === 0
            && (groups.brands || []).length === 0
            && (groups.categories || []).length === 0) {
            renderEmpty(payload);
            return;
        }

        renderGroup(labels.products, products, (item) => rows.product(item));

        renderGroup(labels.vehicles, groups.vehicles || [], (item) => rows.simple(
            item,
            [item.detail, countDetail(item)].filter(Boolean).join(' · '),
            labels.viewCompatibleParts,
        ));

        renderGroup(labels.brands, groups.brands || [], (item) => rows.simple(
            item,
            countDetail(item),
            labels.browseBrandParts,
        ));

        renderGroup(labels.categories, groups.categories || [], (item) => rows.simple(
            item,
            countDetail(item),
            labels.browseCategoryProducts,
        ));

        if (payload.meta?.view_all_url && products.length > 0) {
            const footer = document.createElement('a');
            footer.href = payload.meta.view_all_url;
            footer.className = 'relative mt-1 flex min-h-[44px] w-full items-center justify-between gap-3 border-t border-app px-3 py-2.5 text-start transition-colors duration-150 hover:bg-surface-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/60';
            footer.tabIndex = -1;

            const accent = accentBar();
            footer.append(accent, makeNode('span', 'text-sm font-semibold text-app', labels.viewAllResults));

            if (totalLabel) {
                footer.appendChild(makeNode('span', 'shrink-0 text-xs font-semibold text-accent', totalLabel));
            }

            list.appendChild(options.register(footer, accent, () => window.location.assign(payload.meta.view_all_url)));
        }

        announce(totalLabel || labels.viewAllResults);
    };

    function renderHint() {
        reset();

        if (examples.length === 0) {
            close();
            return;
        }

        list.appendChild(rows.groupHeading(labels.trySearching));

        examples.forEach((example) => {
            list.appendChild(rows.action(example, () => {
                input.value = example;
                input.focus();
                renderedQuery = null;
                search();
            }, 'text-muted'));
        });

        renderChips(null);
        open();
    }

    const renderTypeMore = () => {
        reset();
        renderChips(null);
        list.appendChild(makeNode('p', 'px-3 py-3 text-sm text-muted', labels.typeOneMore));
        open();
    };

    const renderError = () => {
        reset();
        renderChips(null);

        list.appendChild(makeNode('p', 'px-3 pt-3 text-sm font-medium text-app', labels.errorMessage));
        list.appendChild(rows.action(labels.retry, () => {
            renderedQuery = null;
            search();
        }, 'text-accent'));

        announce(labels.errorMessage);
        open();
    };

    const renderSkeleton = () => {
        reset();

        for (let index = 0; index < 3; index += 1) {
            list.appendChild(rows.skeleton());
        }
    };

    // ------------------------------------------------------------------ search

    function search() {
        const query = input.value.trim();

        if (query.length === 0) {
            abort();
            renderedQuery = null;
            renderHint();
            return;
        }

        if (query.length < MIN_QUERY) {
            abort();
            renderedQuery = null;
            renderTypeMore();
            return;
        }

        // Nothing changed since the panel was drawn: re-asking the server for
        // the same answer wastes a request and flickers the list for nothing.
        if (query === renderedQuery && lastPayload) {
            open();
            return;
        }

        if (options.length === 0) {
            renderSkeleton();
        }

        setLoading(true);
        open();

        request.send(query, {
            onSuccess: (payload) => {
                setLoading(false);
                reset();
                lastPayload = payload;
                renderedQuery = query;
                renderResults(payload);
                open();
            },
            onFailure: () => {
                setLoading(false);
                renderedQuery = null;
                lastPayload = null;
                renderError();
            },
        });
    }

    // ------------------------------------------------------------------ events

    input.addEventListener('input', () => {
        if (input.value.trim() === '') {
            abort();
            renderedQuery = null;
            renderHint();
            return;
        }

        request.debounce(search);
    });

    input.addEventListener('focus', () => {
        if (input.value.trim() === '') {
            renderHint();
            return;
        }

        search();
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (!isOpen) {
                return;
            }

            // Escape closes the panel and leaves the query alone. Without this,
            // Chrome's own handling of a search field wins: it empties the box,
            // which fires an input event, which re-opens the panel on the hint
            // state — the field cleared and the panel still there, which is the
            // opposite of what was asked for.
            event.preventDefault();
            event.stopPropagation();
            abort();
            close();
            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (!isOpen) {
                search();
                return;
            }

            if (isLoading) {
                return;
            }

            event.preventDefault();
            options.move(event.key === 'ArrowDown' ? 1 : -1);
            return;
        }

        if (event.key === 'Home' && isOpen && options.activeIndex >= 0) {
            event.preventDefault();
            options.setActive(0);
            return;
        }

        if (event.key === 'End' && isOpen && options.activeIndex >= 0) {
            event.preventDefault();
            options.setActive(options.length - 1);
            return;
        }

        if (event.key === 'Enter') {
            // With a row picked, Enter takes that row. With nothing picked it
            // is an ordinary search, and the form submits as it always did.
            if (isOpen && options.activeIndex >= 0 && !isLoading && options.activate()) {
                event.preventDefault();
            }
            return;
        }

        if (event.key === 'Tab') {
            close();
        }
    });

    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) {
            close();
        }
    });
};

// ----------------------------------------------------------------- entry point

export const initSearchAutocomplete = () => {
    const forms = Array.from(document.querySelectorAll('[data-search-autocomplete]'));

    if (forms.length === 0) {
        return;
    }

    const labels = readLabels();
    const examples = readExamples();

    forms.forEach((form) => createSearchPanel(form, labels, examples));
};
