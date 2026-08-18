<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-variant-form]');
        if (!form) return;

        /* ── Model family list follows the chosen brand (create page only) ── */
        const brandSelect = form.querySelector('[data-family-brand]');
        const familySelect = form.querySelector('[data-family-select]');
        if (brandSelect && familySelect) {
            const familyMap = JSON.parse(form.dataset.familyMap || '{}');
            const placeholder = familySelect.options[0]?.textContent || '';
            const selectedFamily = @json(old('vehicle_model_family_id'));

            const renderFamilies = () => {
                const families = familyMap[brandSelect.value] || [];
                familySelect.innerHTML = '';
                const blank = document.createElement('option');
                blank.value = '';
                blank.textContent = placeholder;
                familySelect.appendChild(blank);
                families.forEach((family) => {
                    const option = document.createElement('option');
                    option.value = family.id;
                    option.textContent = family.name;
                    if (String(family.id) === String(selectedFamily)) option.selected = true;
                    familySelect.appendChild(option);
                });
                familySelect.disabled = families.length === 0;
            };

            brandSelect.addEventListener('change', renderFamilies);
            renderFamilies();
        }

        /* ── Engine repeater ── */
        const rowsHost = form.querySelector('[data-engine-rows]');
        const template = form.querySelector('[data-engine-template]');
        const addButton = form.querySelector('[data-engine-add]');
        const emptyNote = form.querySelector('[data-engine-empty]');
        const maxRows = 20;

        const rows = () => Array.from(rowsHost.querySelectorAll('[data-engine-row]'));

        // Names carry the row position, so they are rewritten whenever a row is
        // added or removed. Otherwise a removed row would leave a gap and the
        // server would see engines[0] and engines[2].
        const reindex = () => {
            rows().forEach((row, index) => {
                row.querySelectorAll('[name^="engines["]').forEach((field) => {
                    field.name = field.name.replace(/^engines\[[^\]]*\]/, 'engines[' + index + ']');
                });
            });
            if (emptyNote) emptyNote.hidden = rows().length > 0;
            if (addButton) addButton.disabled = rows().length >= maxRows;
        };

        const applyFuelType = (row) => {
            const fuel = row.querySelector('[data-engine-fuel]');
            const displacement = row.querySelector('[data-engine-displacement]');
            const aspiration = row.querySelector('[data-engine-aspiration]');
            const note = row.querySelector('[data-engine-electric-note]');
            if (!fuel) return;

            const option = fuel.options[fuel.selectedIndex];
            const hasDisplacement = !option || option.dataset.hasDisplacement !== '0';

            if (displacement) {
                displacement.hidden = !hasDisplacement;
                // Disabled fields are not submitted, so a stale size can never
                // reach the server as a fake displacement for an EV.
                displacement.querySelectorAll('input').forEach((input) => {
                    input.disabled = !hasDisplacement;
                    if (!hasDisplacement) input.value = '';
                });
            }
            if (aspiration) {
                aspiration.hidden = !hasDisplacement;
                aspiration.querySelectorAll('select').forEach((select) => {
                    select.disabled = !hasDisplacement;
                    if (!hasDisplacement) select.value = '';
                });
            }
            if (note) note.hidden = hasDisplacement;
        };

        rowsHost.addEventListener('change', (event) => {
            if (event.target.matches('[data-engine-fuel]')) {
                applyFuelType(event.target.closest('[data-engine-row]'));
            }
        });

        rowsHost.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-engine-remove]');
            if (!remove) return;
            remove.closest('[data-engine-row]').remove();
            reindex();
        });

        if (addButton && template) {
            addButton.addEventListener('click', () => {
                if (rows().length >= maxRows) return;
                // Cloned as nodes rather than markup; reindex() then gives the
                // new row its field names, so __INDEX__ never has to be parsed.
                const row = template.content.firstElementChild.cloneNode(true);
                rowsHost.appendChild(row);
                applyFuelType(row);
                reindex();
                row.querySelector('[data-engine-fuel]')?.focus();
            });
        }

        rows().forEach(applyFuelType);
        reindex();

        /* ── Image picker ── */
        const picker = form.querySelector('[data-image-picker]');
        if (picker) {
            const input = picker.querySelector('[data-image-input]');
            const preview = picker.querySelector('[data-image-preview]');
            const placeholder = picker.querySelector('[data-image-placeholder]');
            const filename = picker.querySelector('[data-image-filename]');
            const buttonLabel = picker.querySelector('[data-image-button-label]');

            input?.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) return;
                if (preview) {
                    // Released once loaded so the page does not hold the blob.
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    preview.hidden = false;
                    preview.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
                }
                if (placeholder) placeholder.hidden = true;
                if (filename) filename.textContent = file.name;
                if (buttonLabel) buttonLabel.textContent = @json(__('Replace Image'));
            });
        }
    });
</script>
