export default function welcomeOfferEditor() {
    return {
        enabled: false, type: 'percent', value: 20, minimum: 0, maximum: 0, days: 30,
        exampleSubtotal: 50000, exampleShipping: 5000, currency: 'IQD', labels: {}, saved: '',
        init() {
            const config = JSON.parse(this.$el.dataset.config || '{}');
            Object.assign(this, config.fields || {});
            this.currency = config.currency || 'IQD';
            this.labels = config.labels || {};
            this.saved = JSON.stringify(config.saved || this.state);
        },
        get state() {
            return { enabled: Boolean(this.enabled), type: this.type, value: this.isShipping ? 0 : this.number(this.value), minimum: this.number(this.minimum), maximum: this.number(this.maximum), days: this.number(this.days) };
        },
        number(value) { return Math.max(0, Number(value) || 0); },
        money(value) { return new Intl.NumberFormat(document.documentElement.lang || 'en', { maximumFractionDigits: 2 }).format(value) + ' ' + this.currency; },
        get dirty() { return JSON.stringify(this.state) !== this.saved; },
        get saveState() { return this.dirty ? this.labels.unsaved : this.labels.saved; },
        get isShipping() { return this.type === 'free_shipping'; },
        get showDiscount() { return !this.isShipping; },
        get isPercent() { return this.type === 'percent'; },
        get valueUnit() { return this.isPercent ? '%' : this.currency; },
        get valueMax() { return this.isPercent ? 100 : 999999999; },
        changeType() {
            if (this.isPercent && (this.number(this.value) > 100 || this.number(this.value) === 0)) this.value = 20;
            if (this.type === 'fixed' && this.number(this.value) === 0) this.value = 5000;
        },
        usePreset(event) { this.value = Number(event.currentTarget.dataset.value); },
        get offerHeadline() {
            const template = this.labels[this.type] || '';
            return template.replace(':value', String(this.number(this.value))).replace(':amount', this.money(this.number(this.value)));
        },
        get durationLabel() { return this.number(this.days) > 0 ? this.labels.days.replace(':days', String(this.number(this.days))) : this.labels.noExpiry; },
        get minimumLabel() { return this.number(this.minimum) > 0 ? this.labels.minimum.replace(':amount', this.money(this.number(this.minimum))) : this.labels.noMinimum; },
        get statusLabel() { return this.enabled ? this.labels.active : this.labels.paused; },
        get meetsMinimum() { return this.number(this.exampleSubtotal) >= this.number(this.minimum); },
        get discount() {
            if (!this.enabled || !this.meetsMinimum || this.number(this.exampleSubtotal) <= 0) return 0;
            if (this.isShipping) return this.number(this.exampleShipping);
            let amount = this.isPercent ? this.number(this.exampleSubtotal) * Math.min(100, this.number(this.value)) / 100 : this.number(this.value);
            if (this.number(this.maximum) > 0) amount = Math.min(amount, this.number(this.maximum));
            return Math.round(Math.min(this.number(this.exampleSubtotal), amount) * 100) / 100;
        },
        get subtotalLabel() { return this.money(this.number(this.exampleSubtotal)); },
        get shippingLabel() { return this.money(this.number(this.exampleShipping)); },
        get discountLabel() { return '−' + this.money(this.discount); },
        get totalLabel() { return this.money(Math.max(0, this.number(this.exampleSubtotal) + this.number(this.exampleShipping) - this.discount)); },
        get previewMessage() { return !this.enabled ? this.labels.pauseNote : (!this.meetsMinimum ? this.labels.belowMinimum : this.labels.automatic); },
    };
}
