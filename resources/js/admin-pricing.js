import '../css/admin-pricing.css';
import { plainNumber, scaled, rateUnits, toIqd, usdText, pricePlan } from './pricing-numbers';

const root = document.querySelector('[data-pricing-workspace]');
if (root) initPricing(root);

function initPricing(root) {
    const messages = JSON.parse(root.dataset.messages);
    const t = (key, replacements = {}) => Object.entries(replacements).reduce((text, [name, value]) => text.replaceAll(`:${name}`, value), messages[key] || key);
    const number = value => Number(value).toLocaleString('en-US', { maximumFractionDigits: 4 });
    const money = value => `${number(value)} IQD`;
    const select = selector => root.querySelector(selector);
    const rows = [...root.querySelectorAll('[data-price-row]')];
    const rateForm = select('[data-rate-form]');
    const rateInput = select('[data-usd-rate-input]');
    const currencyInput = select('#default_price_currency');
    const pageMessage = select('[data-page-message]');
    let rate = rateUnits(root.dataset.rate);

    let savedCurrency = root.dataset.defaultCurrency;
    let rateDirty = false;
    let rateBusy = false;
    let savingAll = false;
    let operationBusy = false;
    let navigating = false;

    const notify = (text, error = false) => {
        pageMessage.hidden = false;
        pageMessage.textContent = text;
        pageMessage.classList.toggle('is-error', error);
        pageMessage.setAttribute('role', error ? 'alert' : 'status');
    };
    const dirtyRows = () => rows.filter(row => row.dataset.dirty === 'true');
    const busyRows = () => rows.some(row => row.dataset.busy === 'true');
    const isDirty = () => rateDirty || dirtyRows().length > 0;

    function refreshDirtyBar() {
        const count = dirtyRows().length;
        const bar = select('[data-dirty-bar]');
        if (!bar) return;
        bar.hidden = count === 0 && !savingAll;
        select('[data-dirty-count]').textContent = savingAll ? t('pending') : t('unsaved', { count });
        select('[data-save-all]').disabled = savingAll || busyRows() || rateBusy;
        select('[data-reset-all]').disabled = savingAll || busyRows();
    }

    function renderRate() {
        const typed = rateUnits(rateInput.value);
        const displayed = rateInput.value.trim() === '' ? rate : typed;
        select('[data-rate-hundred]').textContent = displayed ? number(Number(displayed) / 100) : '—';
        select('[data-rate-dollar]').textContent = displayed ? number(Number(displayed) / 10000) : '—';
        rateDirty = (rateInput.value.trim() !== '' && typed?.toString() !== rate?.toString()) || currencyInput.value !== savedCurrency;
        const state = select('[data-rate-state]');
        state.textContent = rateDirty ? t('rate_dirty') : t('saved_rate');
        state.classList.toggle('is-dirty', rateDirty);
        rateInput.setAttribute('aria-invalid', String(rateInput.value.trim() !== '' && !typed));
    }
    rateInput.addEventListener('input', renderRate);
    currencyInput.addEventListener('change', renderRate);
    renderRate();

    function updateSavedRate(value) {
        const next = rateUnits(value);
        const changed = next?.toString() !== rate?.toString();
        rate = next;
        root.dataset.rate = value || '';

        select('[data-saved-rate]').textContent = next ? number(Number(next) / 100) : '—';
        for (const row of rows) {
            const savedUsd = scaled(row.dataset.savedUsd, 4, 7);
            if (row.dataset.currency === 'USD' && next && savedUsd !== null) {
                row.dataset.savedIqd = toIqd(savedUsd, next).toString();
                row.querySelector('[data-current-iqd]').textContent = money(row.dataset.savedIqd);
                row.querySelector('[data-iqd-input]').placeholder = number(row.dataset.savedIqd);
            }
            renderRow(row);
        }
        if (!rateDirty) rateInput.value = next ? plainNumber(value).replace(/\.00$/, '') : '';
        renderRate();
        return changed;
    }

    async function send(form, submitter) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 30000);
        const body = new FormData(form);
        if (submitter?.name) body.set(submitter.name, submitter.value);
        try {
            const response = await fetch(form.action, {
                method: 'POST', body, signal: controller.signal, credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.status === 419 || response.status === 401 || response.redirected) throw new Error(t('session_error'));
            const payload = await response.json();
            if (!response.ok) {
                const error = new Error(response.status === 422
                    ? Object.values(payload.errors || {}).flat().join(' ') || payload.message
                    : t('network_error'));
                error.fields = payload.errors || {};
                throw error;
            }
            return payload;
        } catch (error) {
            if (error.name === 'AbortError' || error instanceof TypeError || error instanceof SyntaxError) throw new Error(t('network_error'));
            throw error;
        } finally { clearTimeout(timeout); }
    }

    rateForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (rateBusy || busyRows() || savingAll || operationBusy) return notify(t('rate_busy'), true);
        const message = rateForm.querySelector('[data-form-message]');
        if (rateInput.value.trim() && !rateUnits(rateInput.value)) {
            message.textContent = t('rate_invalid'); message.classList.add('is-error'); rateInput.focus(); return;
        }
        rateBusy = true;
        const button = rateForm.querySelector('button[type=submit]');
        const label = button.querySelector('[data-button-label]');
        const original = label.textContent;
        label.textContent = t('saving'); button.disabled = true;
        rateForm.setAttribute('aria-busy', 'true');
        // Capture FormData before disabling fields (disabled controls are omitted).
        const request = send(rateForm, event.submitter);
        rateInput.disabled = currencyInput.disabled = true;
        message.textContent = '';
        refreshDirtyBar();
        try {
            const data = await request;
            savedCurrency = data.default_currency;
            rateDirty = false;
            updateSavedRate(data.rate);
            currencyInput.value = savedCurrency;
            select('[data-rate-date]').textContent = data.updated_at ? data.updated_at.replace('T', ' ').slice(0, 16) : '—';
            select('[data-rate-author]').textContent = data.updated_by || '—';
            message.classList.remove('is-error'); message.textContent = data.message;
            notify(data.message);
        } catch (error) {
            message.classList.add('is-error'); message.textContent = error.message;
        } finally {
            rateBusy = false; button.disabled = false; label.textContent = original;
            rateInput.disabled = currencyInput.disabled = false;
            rateForm.removeAttribute('aria-busy'); renderRate(); rows.forEach(renderRow); refreshDirtyBar();
        }
    });

    function renderRow(row) {
        const usdInput = row.querySelector('[data-usd-input]');
        const iqdInput = row.querySelector('[data-iqd-input]');
        const basis = row.querySelector('[data-basis]').value;
        const currentUsd = usdInput ? scaled(usdInput.value, 4, 7) : null;
        const savedUsd = usdInput ? scaled(row.dataset.savedUsd, 4, 7) : null;
        const usdChanged = usdInput && (currentUsd === null || currentUsd !== savedUsd);
        const targetChanged = iqdInput.value.trim() !== '';
        const dirty = Boolean(usdChanged || targetChanged);
        row.dataset.dirty = String(dirty);
        row.classList.toggle('is-dirty', dirty);
        const plan = dirty ? pricePlan(row.dataset.currency, basis || 'usd', usdInput?.value, iqdInput.value, rate) : null;
        const state = row.querySelector('[data-row-state]');
        if (row.dataset.busy !== 'true') state.textContent = dirty ? t('unsaved_row') : row.dataset.saved === 'true' ? t('saved') : t('ready');
        row.querySelector('[data-row-reset]').hidden = !dirty;
        row.querySelector('.pm-row-save').disabled = !dirty || !plan || row.dataset.busy === 'true' || rateBusy;
        row.querySelector('[data-change-row]').hidden = !dirty && !row.querySelector('[data-row-message]').textContent;
        row.querySelector('.pm-row-review').hidden = !dirty;
        const usdHint = row.querySelector('[data-usd-hint]');
        const iqdHint = row.querySelector('[data-iqd-hint]');
        if (usdHint) usdHint.textContent = plan?.usd !== null && plan?.usd !== undefined && basis === 'iqd' ? `= $${usdText(plan.usd)}` : '';
        iqdHint.textContent = plan && basis !== 'iqd' ? `= ${money(plan.iqd)}` : '';
        const oldValue = BigInt(Math.round(Number(row.dataset.savedIqd)));
        row.querySelector('[data-old-value]').textContent = money(oldValue);
        row.querySelector('[data-new-value]').textContent = plan ? money(plan.iqd) : '—';
        const difference = plan ? plan.iqd - oldValue : null;
        const direction = difference === null ? '' : difference > 0n ? 'up' : difference < 0n ? 'down' : 'same';
        const delta = row.querySelector('[data-difference]');
        delta.textContent = difference !== null ? `${difference > 0n ? '+' : ''}${money(difference)}` : '—';
        delta.className = `pm-delta pm-delta-${direction}`;
        const percent = row.querySelector('[data-percent]');
        percent.textContent = difference !== null && oldValue > 0n ? `${difference > 0n ? '+' : ''}${(Number(difference) / Number(oldValue) * 100).toFixed(2)}%` : '—';
        percent.className = delta.className;
        row.querySelector('[data-direction]').textContent = !plan && dirty ? (row.dataset.currency === 'USD' && !rate ? t('no_rate') : t('invalid')) : direction === 'up' ? `↑ ${t('increase')}` : direction === 'down' ? `↓ ${t('decrease')}` : t('unchanged');
        const activeInput = basis === 'iqd' || !usdInput ? iqdInput : usdInput;
        for (const input of [usdInput, iqdInput].filter(Boolean)) input.removeAttribute('aria-invalid');
        if (dirty && !plan) activeInput.setAttribute('aria-invalid', 'true');
        for (const field of JSON.parse(row.dataset.invalidFields || '[]')) {
            row.querySelector(`[name="${field}"]`)?.setAttribute('aria-invalid', 'true');
        }
        refreshDirtyBar();
    }

    function resetRow(row) {
        if (row.dataset.busy === 'true') return;
        const usdInput = row.querySelector('[data-usd-input]');
        if (usdInput) usdInput.value = row.dataset.savedUsd;
        row.querySelector('[data-iqd-input]').value = '';
        row.querySelector('[data-basis]').value = '';
        row.querySelector('[data-row-message]').textContent = '';
        delete row.dataset.invalidFields;
        row.classList.remove('has-error');
        renderRow(row);
    }

    async function saveRow(row, fromQueue = false) {
        if (row.dataset.busy === 'true' || rateBusy || operationBusy || (savingAll && !fromQueue)) return false;
        const form = row.querySelector('[data-row-form]');
        const button = form.querySelector('.pm-row-save');
        if (button.disabled) return false;
        const message = row.querySelector('[data-row-message]');
        const inputs = [...row.querySelectorAll('[data-usd-input], [data-iqd-input]')];
        row.dataset.busy = 'true'; row.setAttribute('aria-busy', 'true');
        row.querySelector('[data-row-state]').textContent = t('saving');
        button.disabled = true; button.querySelector('[data-button-label]').textContent = t('saving');
        row.querySelector('[data-row-reset]').disabled = true;
        message.textContent = ''; row.classList.remove('has-error');
        const request = send(form);
        inputs.forEach(input => { input.disabled = true; });
        refreshDirtyBar();
        let success = false;
        try {
            const data = await request;
            const rateChanged = updateSavedRate(data.product.rate);
            row.dataset.savedUsd = data.product.usd ? usdText(scaled(data.product.usd, 4)) : '';
            row.dataset.savedIqd = data.product.iqd;
            row.dataset.saved = 'true';
            row.querySelector('[data-current-iqd]').textContent = money(data.product.iqd);
            row.querySelector('[data-iqd-input]').placeholder = number(data.product.iqd);
            row.dataset.busy = 'false'; resetRow(row);
            message.textContent = data.message; message.classList.remove('is-error');
            if (rateChanged) notify(t('rate_changed'));
            const history = select('[data-history-notice]'); if (history) history.hidden = false;
            success = true;
        } catch (error) {
            row.classList.add('has-error'); message.classList.add('is-error'); message.textContent = error.message;
            row.dataset.invalidFields = JSON.stringify(inputs.filter(input => error.fields?.[input.name]).map(input => input.name));
        } finally {
            row.dataset.busy = 'false'; row.removeAttribute('aria-busy'); inputs.forEach(input => { input.disabled = false; });
            row.querySelector('[data-row-reset]').disabled = false;
            button.querySelector('[data-button-label]').textContent = t('save');
            renderRow(row);
        }
        return success;
    }

    rows.forEach(row => {
        row.querySelector('[data-row-form]').addEventListener('submit', event => { event.preventDefault(); saveRow(row); });
        row.querySelector('[data-row-reset]').addEventListener('click', () => resetRow(row));
        row.querySelectorAll('[data-usd-input], [data-iqd-input]').forEach(input => {
            input.addEventListener('input', () => {
                const usd = input.hasAttribute('data-usd-input');
                row.querySelector('[data-basis]').value = usd || (input.value.trim() === '' && row.querySelector('[data-usd-input]')) ? 'usd' : 'iqd';
                if (usd) row.querySelector('[data-iqd-input]').value = '';
                row.querySelector('[data-row-message]').textContent = ''; row.classList.remove('has-error');
                delete row.dataset.invalidFields;
                renderRow(row);
            });
            input.addEventListener('keydown', event => {
                if (event.isComposing) return;
                if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) { event.preventDefault(); saveRow(row); return; }
                if (event.key !== 'Enter' && !['ArrowUp', 'ArrowDown'].includes(event.key)) return;
                event.preventDefault();
                const selector = input.hasAttribute('data-usd-input') ? '[data-usd-input]' : '[data-iqd-input]';
                const cells = [...root.querySelectorAll(selector)].filter(cell => !cell.disabled);
                const step = event.key === 'ArrowUp' || (event.key === 'Enter' && event.shiftKey) ? -1 : 1;
                const next = cells[cells.indexOf(input) + step];
                if (next) { next.focus(); next.select(); }
            });
        });
        renderRow(row);
    });
    select('[data-reset-all]')?.addEventListener('click', () => dirtyRows().forEach(resetRow));
    select('[data-save-all]')?.addEventListener('click', async () => {
        if (savingAll || busyRows() || rateBusy || operationBusy) return;
        savingAll = true; refreshDirtyBar();
        let failed = false;
        for (const row of dirtyRows()) if (!await saveRow(row, true)) failed = true;
        savingAll = false; refreshDirtyBar();
        notify(t(failed ? 'partial_save' : 'saved_all'), failed);
    });

    function updateSelection(group) {
        const boxes = [...root.querySelectorAll(`[data-check="${group}"]`)];
        const count = boxes.filter(box => box.checked).length;
        root.querySelectorAll(`[data-check-all="${group}"]`).forEach(all => {
            all.checked = boxes.length > 0 && count === boxes.length;
            all.indeterminate = count > 0 && count < boxes.length;
        });
        if (group !== 'bulk') return;
        select('[data-selection-count]').textContent = t('selected', { count });
        select('[data-clear-selection]').hidden = count === 0;
        select('[data-selection-hint]').hidden = count > 0;
        select('.pm-selection-bar').classList.toggle('has-selection', count > 0);
        const scope = select('input[name=bulk_scope]:checked')?.value;
        select('[data-bulk-preview]').disabled = scope === 'selected' && count === 0;
        boxes.forEach(box => box.closest('[data-price-row]')?.classList.toggle('is-selected', box.checked));
    }
    for (const group of ['bulk', 'convert']) {
        root.querySelectorAll(`[data-check="${group}"]`).forEach(box => box.addEventListener('change', () => updateSelection(group)));
        root.querySelectorAll(`[data-check-all="${group}"]`).forEach(all => all.addEventListener('change', event => {
            root.querySelectorAll(`[data-check="${group}"]`).forEach(box => { box.checked = event.target.checked; }); updateSelection(group);
        }));
        if (select(`[data-check-all="${group}"]`)) updateSelection(group);
    }
    select('[data-clear-selection]')?.addEventListener('click', () => { root.querySelectorAll('[data-check="bulk"]').forEach(box => { box.checked = false; }); updateSelection('bulk'); });
    root.querySelectorAll('input[name=bulk_scope]').forEach(input => input.addEventListener('change', () => updateSelection('bulk')));
    root.querySelectorAll('[data-operation-form]').forEach(form => form.addEventListener('submit', event => {
        if (isDirty() || busyRows() || rateBusy || savingAll || operationBusy) { event.preventDefault(); event.stopImmediatePropagation(); notify(t('save_first'), true); pageMessage.scrollIntoView({ block: 'center', behavior: 'auto' }); }
        else if (!form.hasAttribute('data-async-operation')) navigating = true;
    }, true));
    root.querySelectorAll('[data-async-operation]').forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault();
        operationBusy = true;
        const message = form.querySelector('[data-operation-message]');
        message.classList.remove('is-error');
        message.textContent = t('pending');
        form.setAttribute('aria-busy', 'true');
        // Keep the form and selection in place if validation or the connection fails.
        const request = send(form, event.submitter);
        const controls = [...root.querySelectorAll('input, select, button')].filter(control => !control.disabled);
        controls.forEach(control => { control.disabled = true; });
        try {
            const data = await request;
            if (!data.redirect) throw new Error(t('network_error'));
            navigating = true;
            window.location.assign(data.redirect);
        } catch (error) {
            message.classList.add('is-error');
            message.textContent = error.message;
            message.focus();
        } finally {
            operationBusy = false;
            form.removeAttribute('aria-busy');
            if (!navigating) controls.forEach(control => { control.disabled = false; });
        }
    }));
    window.addEventListener('beforeunload', event => {
        if (!navigating && (isDirty() || busyRows() || rateBusy || operationBusy)) { event.preventDefault(); event.returnValue = ''; }
    });
    const openAnchor = () => {
        const target = ['#convert', '#history'].includes(location.hash) ? select(location.hash) : null;
        if (target) target.open = true;
    };
    window.addEventListener('hashchange', openAnchor); openAnchor();
    if (select('#bulk-preview') && !location.hash) select('#bulk-preview').scrollIntoView({ block: 'start' });
}
