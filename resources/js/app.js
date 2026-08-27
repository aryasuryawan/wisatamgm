import '@tabler/core/dist/css/tabler.css';
import 'tom-select/dist/css/tom-select.default.css';
import '@tabler/icons-webfont/dist/tabler-icons.min.css';
import './app.css';
import '../css/sip-garden-theme.css';

import '@tabler/core/dist/js/tabler.min.js';

import TomSelect from 'tom-select';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select[data-searchable]').forEach((el) => {
        new TomSelect(el, {
            create: false,
            maxOptions: 1000,
            placeholder: el.dataset.placeholder || '',
            allowEmptyOption: true,
        });
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.data('moneyField', (initial = null) => ({
        display: '',
        init() {
            this.$nextTick(() => {
                const v = this.$refs.hidden?.value ?? (initial !== null ? String(initial) : '');
                this.display = this.fmt(v);
            });
        },
        onInput() {
            const digits = this.digits(this.display);
            this.$refs.hidden.value = digits;
            this.$dispatch('money-input', digits);
            this.display = this.fmt(digits);
        },
        fmt(v) {
            const d = this.digits(String(v));
            return d === '' ? '' : new Intl.NumberFormat('id-ID').format(Number(d));
        },
        digits(s) {
            return String(s).replace(/[^\d]/g, '');
        },
    }));

    const modules = import.meta.glob('./alpine/*.js', { eager: true });

    Object.values(modules).forEach((module) => {
        if (typeof module.default === 'function') {
            module.default(Alpine);
        }
    });
});

Alpine.start();
