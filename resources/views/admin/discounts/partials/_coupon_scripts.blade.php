@push('scripts')
<script>
    (() => {
        const couponEnabledInput = document.querySelector('input[name="coupon_enabled"]');
        const couponControls = document.querySelectorAll('[data-coupon-control]');
        const couponPanel = document.querySelector('[data-coupon-panel]');
        const couponStatusBadge = document.querySelector('[data-coupon-status-badge]');
        const couponTypeInput = document.getElementById('coupon_type');
        const couponValueInput = document.getElementById('coupon_value');
        const couponValueHelp = document.getElementById('coupon-value-help');
        const couponCodeInput = document.getElementById('coupon_code');
        const couponStartsInput = document.getElementById('coupon_starts_at');
        const couponEndsInput = document.getElementById('coupon_ends_at');
        const couponGenerateBtn = document.getElementById('coupon-generate-btn');
        const couponClearDatesBtn = document.getElementById('coupon-clear-dates');
        const couponSearch = document.getElementById('coupon-search');
        const couponFilter = document.getElementById('coupon-filter');
        const couponTableBody = document.getElementById('coupon-table-body');
        const exportCouponsBtn = document.getElementById('export-coupons-btn');
        const closeCouponBuilderBtn = document.getElementById('close-coupon-builder-btn');
        const couponBuilder = document.getElementById('coupon-builder');

        const usageDistribution = document.getElementById('usage-distribution');
        const platformDistribution = document.getElementById('platform-distribution');
        const editCouponModal = document.getElementById('edit-coupon-modal');
        const editModalClose = document.getElementById('edit-modal-close');
        const editModalCancel = document.getElementById('edit-modal-cancel');
        const editModalSave = document.getElementById('edit-modal-save');
        const editCode = document.getElementById('edit-code');
        const editDiscount = document.getElementById('edit-discount');
        const editExpiry = document.getElementById('edit-expiry');
        const editStatusToggle = document.getElementById('edit-status-toggle');
        const editPlatformWeb = document.getElementById('edit-platform-web');
        const editPlatformMobile = document.getElementById('edit-platform-mobile');
        const editPlatformDealer = document.getElementById('edit-platform-dealer');
        const editUsageLabel = document.getElementById('edit-usage-label');
        const editUsageBar = document.getElementById('edit-usage-bar');
        const editCodePreview = document.getElementById('edit-code-preview');
        const editStatusPreview = document.getElementById('edit-status-preview');
        const editExpiryPreview = document.getElementById('edit-expiry-preview');

        const randomCouponCode = () => `SAVE-${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
        const sanitizeCouponCode = (value) => value.toUpperCase().replace(/[^A-Z0-9_-]/g, '').slice(0, 40);
        let editingIndex = null;
        const editActionLabel = @json(__('Edit'));
        const pauseActionLabel = @json(__('Pause'));
        const activateActionLabel = @json(__('Activate'));
        const deleteActionLabel = @json(__('Delete'));
        const timingState = (startsAt, endsAt) => {
            const now = new Date();
            const starts = startsAt ? new Date(`${startsAt}T00:00:00`) : null;
            const ends = endsAt ? new Date(`${endsAt}T23:59:59`) : null;
            if (starts && now < starts) return { label: 'Scheduled', style: 'bg-amber-50 text-amber-700 border-amber-200' };
            if (ends && now > ends) return { label: 'Expired', style: 'bg-rose-50 text-rose-700 border-rose-200' };
            return { label: 'Active', style: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        };

        const refreshCouponControls = () => {
            const enabled = !!couponEnabledInput?.checked;
            couponControls.forEach((control) => {
                control.disabled = !enabled;
            });
            couponPanel?.classList.toggle('opacity-75', !enabled);
            if (!couponStatusBadge) return;
            if (!enabled) {
                couponStatusBadge.className = 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-600 border-slate-200';
                couponStatusBadge.textContent = 'Disabled';
                return;
            }
            const state = timingState(couponStartsInput?.value || '', couponEndsInput?.value || '');
            couponStatusBadge.className = `inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ${state.style}`;
            couponStatusBadge.textContent = state.label;
        };

        const syncCouponTypeLimits = () => {
            if (!couponTypeInput || !couponValueInput) return;
            if (couponTypeInput.value === 'percent') {
                couponValueInput.max = '100';
                if (couponValueHelp) couponValueHelp.textContent = 'Percent type supports max 100.';
                return;
            }
            couponValueInput.removeAttribute('max');
            if (couponValueHelp) couponValueHelp.textContent = 'Fixed amount has no percentage cap.';
        };

        const chartPoints = @json($trendPoints);
        const drawTrendChart = () => {
            const line = document.getElementById('trend-line');
            const area = document.getElementById('trend-area');
            if (!line || !area) return;

            const width = 560;
            const height = 170;
            const max = Math.max(...chartPoints);
            const min = Math.min(...chartPoints);
            const stepX = width / (chartPoints.length - 1);
            const points = chartPoints.map((value, idx) => {
                const x = idx * stepX;
                const y = height - (((value - min) / (max - min || 1)) * (height - 20) + 10);
                return [x, y];
            });

            const dLine = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p[0]} ${p[1]}`).join(' ');
            const dArea = `${dLine} L ${width} ${height} L 0 ${height} Z`;
            line.setAttribute('d', dLine);
            area.setAttribute('d', dArea);
        };

        const usageBuckets = @json($usageDistribution);
        const platformBuckets = @json($platformDistribution);

        const renderBars = (container, rows) => {
            if (!container) return;
            container.innerHTML = '';
            rows.forEach((row) => {
                const wrap = document.createElement('div');
                wrap.innerHTML = `
                    <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div class="mb-2 flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                            <span class="font-semibold">${row.label}</span>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 font-semibold dark:border-slate-700 dark:bg-slate-800">${row.value}%</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                            <div class="h-full rounded-full ${row.color}" style="width:${row.value}%"></div>
                        </div>
                    </div>
                `;
                container.appendChild(wrap);
            });
        };

        const statusClass = (status) => {
            if (status === 'active') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
            if (status === 'scheduled') return 'border-amber-200 bg-amber-50 text-amber-700';
            if (status === 'expired') return 'border-rose-200 bg-rose-50 text-rose-700';
            return 'border-slate-200 bg-slate-100 text-slate-700';
        };

        const coupons = @json($couponRows);

        const usageText = (row) => `${row.usageUsed} / ${row.usageLimit || '0'}`;
        const usagePercent = (row) => {
            if (!row.usageLimit || row.usageLimit <= 0) return 0;
            return Math.max(0, Math.min(100, Math.round((row.usageUsed / row.usageLimit) * 100)));
        };
        const platformsText = (row) => (row.platforms || []).join(', ');
        const setModalVisible = (modal, visible) => {
            if (!modal) return;
            modal.classList.toggle('hidden', !visible);
            modal.classList.toggle('flex', visible);
        };

        const syncEditPreview = () => {
            if (editCodePreview) {
                editCodePreview.textContent = (editCode?.value || '').trim() || 'Awaiting selection';
            }
            if (editStatusPreview) {
                editStatusPreview.textContent = editStatusToggle?.checked ? 'Active' : 'Paused';
            }
            if (editExpiryPreview) {
                editExpiryPreview.textContent = (editExpiry?.value || '').trim() || 'Open';
            }
        };

        const renderCouponRows = () => {
            if (!couponTableBody) return;
            const query = (couponSearch?.value || '').toLowerCase();
            const filter = couponFilter?.value || 'all';
            couponTableBody.innerHTML = '';

            coupons
                .filter((row) => {
                    const matchQuery = row.code.toLowerCase().includes(query) || row.platforms.toLowerCase().includes(query);
                    const matchFilter = filter === 'all' ? true : row.status === filter;
                    return matchQuery && matchFilter;
                })
                .forEach((row, idx) => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50/70 dark:hover:bg-slate-800/60';
                    tr.innerHTML = `
                        <td class="px-5 py-4 font-semibold text-slate-900 dark:text-slate-100">${row.code}</td>
                        <td class="px-5 py-4 text-slate-700 dark:text-slate-300">${row.discount}</td>
                        <td class="px-5 py-4 text-slate-700 dark:text-slate-300">${usageText(row)}</td>
                        <td class="px-5 py-4 text-slate-700 dark:text-slate-300">${row.expiry}</td>
                        <td class="px-5 py-4"><span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold capitalize ${statusClass(row.status)}">${row.status}</span></td>
                        <td class="px-5 py-4 text-slate-700 dark:text-slate-300">${platformsText(row)}</td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" data-action="edit" data-index="${idx}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">${editActionLabel}</button>
                                <button type="button" data-action="activate" data-index="${idx}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">${row.status === 'active' ? pauseActionLabel : activateActionLabel}</button>
                                <button type="button" data-action="delete" data-index="${idx}" class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">${deleteActionLabel}</button>
                            </div>
                        </td>
                    `;
                    couponTableBody.appendChild(tr);
                });
        };

        const openEditModal = (index) => {
            editingIndex = index;
            const row = coupons[index];
            if (!row) return;
            if (editCode) editCode.value = row.code || '';
            if (editDiscount) editDiscount.value = row.discount || '';
            if (editExpiry) editExpiry.value = row.expiry && row.expiry !== 'No expiry' ? row.expiry : '';
            if (editStatusToggle) editStatusToggle.checked = row.status === 'active';
            if (editPlatformWeb) editPlatformWeb.checked = row.platforms?.includes('Web');
            if (editPlatformMobile) editPlatformMobile.checked = row.platforms?.includes('Mobile');
            if (editPlatformDealer) editPlatformDealer.checked = row.platforms?.includes('Dealer Portal');
            if (editUsageLabel) editUsageLabel.textContent = usageText(row);
            if (editUsageBar) editUsageBar.style.width = `${usagePercent(row)}%`;
            syncEditPreview();
            setModalVisible(editCouponModal, true);
        };

        const saveEditModal = () => {
            if (editingIndex === null) return;
            const row = coupons[editingIndex];
            if (!row) return;
            row.code = sanitizeCouponCode(editCode?.value || row.code);
            row.discount = (editDiscount?.value || row.discount).trim() || row.discount;
            row.expiry = (editExpiry?.value || '').trim() || 'No expiry';
            row.status = editStatusToggle?.checked ? 'active' : 'paused';
            row.platforms = [
                editPlatformWeb?.checked ? 'Web' : null,
                editPlatformMobile?.checked ? 'Mobile' : null,
                editPlatformDealer?.checked ? 'Dealer Portal' : null,
            ].filter(Boolean);
            if (row.platforms.length === 0) row.platforms = ['Web'];
            syncEditPreview();
            setModalVisible(editCouponModal, false);
            renderCouponRows();
        };

        const exportCsv = () => {
            const rows = [['Coupon Code', 'Discount', 'Usage', 'Expiry', 'Status', 'Platforms']];
            coupons.forEach((c) => rows.push([c.code, c.discount, usageText(c), c.expiry, c.status, platformsText(c)]));
            const csv = rows.map((r) => r.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'coupon-dashboard-export.csv';
            link.click();
            URL.revokeObjectURL(url);
        };

        couponTypeInput?.addEventListener('change', () => {
            syncCouponTypeLimits();
            renderCouponRows();
        });
        couponValueInput?.addEventListener('input', renderCouponRows);

        couponCodeInput?.addEventListener('input', () => {
            couponCodeInput.value = sanitizeCouponCode(couponCodeInput.value || '');
            renderCouponRows();
        });

        couponEnabledInput?.addEventListener('change', () => {
            refreshCouponControls();
            renderCouponRows();
        });

        couponStartsInput?.addEventListener('change', () => {
            if (couponEndsInput && couponStartsInput.value) {
                couponEndsInput.min = couponStartsInput.value;
            } else if (couponEndsInput) {
                couponEndsInput.removeAttribute('min');
            }
            refreshCouponControls();
            renderCouponRows();
        });

        couponEndsInput?.addEventListener('change', () => {
            refreshCouponControls();
            renderCouponRows();
        });

        couponGenerateBtn?.addEventListener('click', () => {
            if (!couponCodeInput) return;
            couponCodeInput.value = randomCouponCode();
            renderCouponRows();
        });

        couponClearDatesBtn?.addEventListener('click', () => {
            if (couponStartsInput) couponStartsInput.value = '';
            if (couponEndsInput) {
                couponEndsInput.value = '';
                couponEndsInput.removeAttribute('min');
            }
            refreshCouponControls();
            renderCouponRows();
        });

        couponSearch?.addEventListener('input', renderCouponRows);
        couponFilter?.addEventListener('change', renderCouponRows);
        exportCouponsBtn?.addEventListener('click', exportCsv);
        editCode?.addEventListener('input', syncEditPreview);
        editExpiry?.addEventListener('input', syncEditPreview);
        editStatusToggle?.addEventListener('change', syncEditPreview);
        closeCouponBuilderBtn?.addEventListener('click', () => {
            couponBuilder?.classList.add('hidden');
        });
        couponTableBody?.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-action]');
            if (!button) return;
            const index = Number(button.getAttribute('data-index'));
            const action = button.getAttribute('data-action');
            if (Number.isNaN(index) || !coupons[index]) return;

            if (action === 'edit') {
                openEditModal(index);
                return;
            }
            if (action === 'activate') {
                coupons[index].status = coupons[index].status === 'active' ? 'paused' : 'active';
                renderCouponRows();
                return;
            }
            if (action === 'delete') {
                const confirmDelete = window.adminDangerConfirm({
                    title: 'Delete Coupon',
                    description: 'This action is permanent. The selected coupon campaign will be removed from the dashboard list.',
                });

                confirmDelete.then((confirmed) => {
                    if (!confirmed) return;
                    if (coupons[index]) {
                        coupons.splice(index, 1);
                        renderCouponRows();
                    }
                });
            }
        });

        [editModalClose, editModalCancel].forEach((btn) => {
            btn?.addEventListener('click', () => setModalVisible(editCouponModal, false));
        });
        editModalSave?.addEventListener('click', saveEditModal);
        [editCouponModal].forEach((modal) => {
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) setModalVisible(modal, false);
            });
        });

        drawTrendChart();
        renderBars(usageDistribution, usageBuckets);
        renderBars(platformDistribution, platformBuckets);
        syncCouponTypeLimits();
        refreshCouponControls();
        renderCouponRows();
    })();
</script>
@endpush
