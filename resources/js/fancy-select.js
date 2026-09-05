// A listbox that looks like the rest of the page.
//
// The vehicle finder used bare <select> elements, so opening one handed the
// visitor the operating system's own popup — a white OS menu, positioned and
// coloured by the browser, drawn outside the panel it belongs to. No stylesheet
// reaches inside that popup, which is why it could never be made to match.
//
// So the native control stays in the DOM and stays the thing that submits, and
// a button plus a listbox is drawn in its place. Every choice is written back to
// the <select> and a `change` event is dispatched, which means the dependent
// dropdown logic underneath never learns that any of this happened.
//
// Enhancement only: without JavaScript the native control is still there and
// still works.

const SEARCH_THRESHOLD = 8;
const PANEL_MIN_HEIGHT = 180;
const PANEL_MAX_HEIGHT = 320;

// Markup never comes from a data attribute. A field names an icon and the
// drawing lives here, so nothing built from page data is ever parsed as HTML.
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

const FIELD_ICONS = {
    car: () => drawSvg('M3.5 12.5h13m-13 0-.5-3 2-4.5h9l2 4.5.5 3m-13 0v2.5h2v-2.5m9 0v2.5h2v-2.5', 1.5),
    vehicle: () => drawSvg('M4 6.5h12v7H4v-7Zm2.5 7v2h2v-2m5 0v2h2v-2', 1.5),
    engine: () => drawSvg('M4 8.5h2l2-2h4l2 2h2v5h-2l-2 2H8l-2-2H4v-5Z', 1.5),
    calendar: () => drawSvg('M4.5 5.5h11v11h-11v-11Zm0 3.5h11M7.5 3.5v3m5-3v3', 1.5),
};

const drawChevron = () => drawSvg('m5.5 7.5 4.5 4.5 4.5-4.5', 1.8);
const drawCheck = () => drawSvg('m4.5 10.5 3.5 3.5 7.5-8', 2.1);

export const initFancySelects = () => {
    const selects = Array.from(document.querySelectorAll('select[data-fancy-select]'));
    if (selects.length === 0) {
        return;
    }

    const openInstances = new Set();
    const instances = [];
    let idCounter = 0;

    const visibleItems = (instance) => instance.items.filter((item) => !item.hidden);

    const closeInstance = (instance, { focusTrigger = false } = {}) => {
        if (!instance.isOpen) {
            return;
        }

        instance.isOpen = false;
        openInstances.delete(instance);
        instance.panel.hidden = true;
        instance.trigger.setAttribute('aria-expanded', 'false');
        instance.trigger.removeAttribute('aria-activedescendant');
        instance.wrapper.classList.remove('is-open', 'is-above');
        instance.items.forEach((item) => item.classList.remove('is-active'));

        if (focusTrigger) {
            instance.trigger.focus({ preventScroll: true });
        }
    };

    const closeAll = (except = null) => {
        Array.from(openInstances).forEach((instance) => {
            if (instance !== except) {
                closeInstance(instance);
            }
        });
    };

    const renderOptions = (instance) => {
        const { select, list } = instance;
        list.innerHTML = '';
        instance.items = [];

        Array.from(select.options).forEach((option, index) => {
            const item = document.createElement('li');
            item.className = 'ys-select-option';
            item.id = `${instance.id}-option-${index}`;
            item.setAttribute('role', 'option');
            item.dataset.value = option.value;
            item.dataset.search = (option.dataset.search || option.textContent || '').toLowerCase();

            // A placeholder is the way back to "no choice", not a vehicle.
            if (option.value === '') {
                item.classList.add('is-placeholder');
            }

            const primary = document.createElement('span');
            primary.className = 'ys-select-option-primary';
            primary.textContent = option.dataset.primary || option.textContent.trim();
            item.appendChild(primary);

            const secondary = (option.dataset.secondary || '').trim();
            if (secondary !== '') {
                const secondaryNode = document.createElement('span');
                secondaryNode.className = 'ys-select-option-secondary';
                secondaryNode.textContent = secondary;
                item.appendChild(secondaryNode);
            }

            const checkHost = document.createElement('span');
            checkHost.className = 'ys-select-option-check';
            checkHost.setAttribute('aria-hidden', 'true');
            checkHost.appendChild(drawCheck());
            item.appendChild(checkHost);

            const isSelected = option.value === select.value;
            item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            item.classList.toggle('is-selected', isSelected);

            list.appendChild(item);
            instance.items.push(item);
        });

        // A search box earns its place only once scanning the list stops being
        // instant. Below that it is one more thing between a tap and an answer.
        instance.searchWrap.hidden = instance.items.length <= instance.searchThreshold;
    };

    const syncTrigger = (instance) => {
        const { select, trigger } = instance;
        const option = select.selectedOptions[0] || null;
        const chosen = Boolean(option && option.value !== '');

        instance.value.textContent = option ? option.textContent.trim() : '';
        trigger.classList.toggle('is-empty', !chosen);
        trigger.disabled = select.disabled;
        instance.wrapper.classList.toggle('is-disabled', select.disabled);

        if (select.disabled) {
            closeInstance(instance);
        }
    };

    const setActive = (instance, index) => {
        const items = visibleItems(instance);
        if (items.length === 0) {
            instance.activeIndex = -1;
            instance.trigger.removeAttribute('aria-activedescendant');
            return;
        }

        const bounded = Math.max(0, Math.min(index, items.length - 1));
        instance.items.forEach((item) => item.classList.remove('is-active'));

        const active = items[bounded];
        active.classList.add('is-active');
        instance.activeIndex = bounded;
        instance.trigger.setAttribute('aria-activedescendant', active.id);
        active.scrollIntoView({ block: 'nearest' });
    };

    const applyFilter = (instance) => {
        const needle = instance.search.value.trim().toLowerCase();

        instance.items.forEach((item) => {
            if (item.classList.contains('is-placeholder')) {
                // The placeholder is navigation, not a result. Hiding it while
                // filtering is what keeps "no matches" honest.
                item.hidden = needle !== '';
                return;
            }

            item.hidden = needle !== '' && !item.dataset.search.includes(needle);
        });

        instance.empty.hidden = visibleItems(instance).length > 0;
        setActive(instance, 0);
    };

    const placePanel = (instance) => {
        // Below by default; above when there is not enough room under the
        // field, so the list never runs off the bottom of a phone.
        const rect = instance.trigger.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;
        const wantsAbove = spaceBelow < 240 && spaceAbove > spaceBelow;

        instance.wrapper.classList.toggle('is-above', wantsAbove);
        instance.panel.style.maxHeight = `${Math.max(
            PANEL_MIN_HEIGHT,
            Math.min(PANEL_MAX_HEIGHT, (wantsAbove ? spaceAbove : spaceBelow) - 16)
        )}px`;
    };

    const openInstance = (instance) => {
        if (instance.isOpen || instance.select.disabled) {
            return;
        }

        closeAll(instance);
        instance.isOpen = true;
        openInstances.add(instance);
        instance.panel.hidden = false;
        instance.trigger.setAttribute('aria-expanded', 'true');
        instance.wrapper.classList.add('is-open');
        instance.search.value = '';
        applyFilter(instance);
        placePanel(instance);

        const selectedIndex = visibleItems(instance)
            .findIndex((item) => item.dataset.value === instance.select.value);
        setActive(instance, selectedIndex >= 0 ? selectedIndex : 0);

        if (!instance.searchWrap.hidden) {
            instance.search.focus({ preventScroll: true });
        }
    };

    const choose = (instance, item) => {
        if (!item) {
            return;
        }

        const previous = instance.select.value;
        instance.select.value = item.dataset.value;
        closeInstance(instance, { focusTrigger: true });

        if (instance.select.value !== previous) {
            // The dependent dropdowns listen for this, exactly as they did when
            // a person was operating the native control.
            instance.select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        renderOptions(instance);
        syncTrigger(instance);
    };

    const build = (select) => {
        const id = `ys-select-${(idCounter += 1)}`;

        const wrapper = document.createElement('div');
        wrapper.className = 'ys-select';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ys-select-trigger';
        trigger.id = `${id}-trigger`;
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-controls', `${id}-listbox`);

        // The <select> already carries this field's accessible name, and it is
        // the name a screen reader should read for the control replacing it.
        const accessibleName = select.getAttribute('aria-label');
        if (accessibleName) {
            trigger.setAttribute('aria-label', accessibleName);
        }

        const iconName = select.dataset.fancyIcon || '';
        if (Object.prototype.hasOwnProperty.call(FIELD_ICONS, iconName)) {
            const iconHost = document.createElement('span');
            iconHost.className = 'ys-select-icon';
            iconHost.setAttribute('aria-hidden', 'true');
            iconHost.appendChild(FIELD_ICONS[iconName]());
            trigger.appendChild(iconHost);
        }

        const value = document.createElement('span');
        value.className = 'ys-select-value';
        trigger.appendChild(value);

        const chevronHost = document.createElement('span');
        chevronHost.className = 'ys-select-chevron';
        chevronHost.setAttribute('aria-hidden', 'true');
        chevronHost.appendChild(drawChevron());
        trigger.appendChild(chevronHost);

        const panel = document.createElement('div');
        panel.className = 'ys-select-panel';
        panel.hidden = true;

        const searchWrap = document.createElement('div');
        searchWrap.className = 'ys-select-search';
        const search = document.createElement('input');
        search.type = 'text';
        search.className = 'ys-select-search-input';
        search.setAttribute('autocomplete', 'off');
        search.setAttribute('aria-label', select.dataset.fancySearchLabel || 'Search');
        search.placeholder = select.dataset.fancySearchLabel || 'Search';
        searchWrap.appendChild(search);
        panel.appendChild(searchWrap);

        const list = document.createElement('ul');
        list.className = 'ys-select-list';
        list.id = `${id}-listbox`;
        list.setAttribute('role', 'listbox');
        if (accessibleName) {
            list.setAttribute('aria-label', accessibleName);
        }
        panel.appendChild(list);

        const empty = document.createElement('p');
        empty.className = 'ys-select-empty';
        empty.hidden = true;
        empty.textContent = select.dataset.fancyEmptyLabel || 'No matches found';
        panel.appendChild(empty);

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(trigger);
        wrapper.appendChild(panel);
        wrapper.appendChild(select);

        // The native control keeps its name and value so the form submits
        // exactly what it always did, but it is no longer reachable: leaving it
        // focusable would give the field two tab stops and two announcements.
        select.classList.add('ys-select-native');
        select.setAttribute('tabindex', '-1');
        select.setAttribute('aria-hidden', 'true');

        return {
            id,
            select,
            wrapper,
            trigger,
            value,
            panel,
            list,
            search,
            searchWrap,
            empty,
            isOpen: false,
            activeIndex: -1,
            items: [],
            searchThreshold: Number(select.dataset.fancySearchThreshold || SEARCH_THRESHOLD),
        };
    };

    selects.forEach((select) => {
        const instance = build(select);
        instances.push(instance);

        renderOptions(instance);
        syncTrigger(instance);

        // The finder rebuilds these options whenever the brand or the variant
        // changes. Watching the element means that code did not have to learn
        // anything about this one.
        const observer = new MutationObserver(() => {
            renderOptions(instance);
            syncTrigger(instance);
            if (instance.isOpen) {
                applyFilter(instance);
            }
        });
        observer.observe(select, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['disabled'],
        });

        select.addEventListener('change', () => {
            renderOptions(instance);
            syncTrigger(instance);
        });

        instance.trigger.addEventListener('click', () => {
            if (instance.isOpen) {
                closeInstance(instance, { focusTrigger: true });
            } else {
                openInstance(instance);
            }
        });

        instance.trigger.addEventListener('keydown', (event) => {
            const isConfirm = event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar';

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || isConfirm) {
                event.preventDefault();

                if (!instance.isOpen) {
                    openInstance(instance);
                    return;
                }

                if (isConfirm) {
                    choose(instance, visibleItems(instance)[instance.activeIndex]);
                    return;
                }

                setActive(instance, instance.activeIndex + (event.key === 'ArrowDown' ? 1 : -1));
                return;
            }

            if (!instance.isOpen) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeInstance(instance, { focusTrigger: true });
            } else if (event.key === 'Home') {
                event.preventDefault();
                setActive(instance, 0);
            } else if (event.key === 'End') {
                event.preventDefault();
                setActive(instance, visibleItems(instance).length - 1);
            }
        });

        instance.search.addEventListener('input', () => applyFilter(instance));
        instance.search.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setActive(instance, instance.activeIndex + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(instance, instance.activeIndex - 1);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                choose(instance, visibleItems(instance)[instance.activeIndex]);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                closeInstance(instance, { focusTrigger: true });
            }
        });

        instance.list.addEventListener('click', (event) => {
            choose(instance, event.target.closest('.ys-select-option'));
        });

        instance.list.addEventListener('mousemove', (event) => {
            const item = event.target.closest('.ys-select-option');
            if (item && !item.hidden) {
                setActive(instance, visibleItems(instance).indexOf(item));
            }
        });
    });

    document.addEventListener('click', (event) => {
        Array.from(openInstances).forEach((instance) => {
            if (!instance.wrapper.contains(event.target)) {
                closeInstance(instance);
            }
        });
    });

    const reposition = () => Array.from(openInstances).forEach(placePanel);
    window.addEventListener('resize', reposition, { passive: true });
    window.addEventListener('scroll', reposition, { passive: true });
};
