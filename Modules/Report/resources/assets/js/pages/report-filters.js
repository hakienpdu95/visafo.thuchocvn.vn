import { createTs } from '@shared/tom-select-factory.js';

export function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

export const fmtQty = (n) => Number(n ?? 0).toLocaleString('vi-VN', { maximumFractionDigits: 3 });

export function reportBase({ apiUrl, defaults = {}, filterKeys = [] }) {
    const selects = { customer: 'rp-customer', unit: 'rp-unit' };
    let controller = null;

    return {
        loading: false,
        error: '',
        filters: Object.fromEntries(filterKeys.map((k) => [k, ''])),

        initBase() {
            const params = new URLSearchParams(location.search);
            filterKeys.forEach((k) => { this.filters[k] = params.get(k) ?? defaults[k] ?? ''; });

            this.$nextTick(() => {
                Object.entries(selects).forEach(([key, id]) => {
                    const el = document.getElementById(id);
                    if (!el || !filterKeys.includes(key)) return;
                    const ts = createTs(el, {
                        placeholder: el.dataset.tsPlaceholder,
                        maxOptions: null,
                        onChange: (value) => { this.filters[key] = value || ''; this.reload(); },
                    });
                    ts.setValue(this.filters[key], true);
                    this['_ts_' + key] = ts;
                });

                const dateOpts = { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: true, disableMobile: true };
                [['date_from', 'rp-date-from'], ['date_to', 'rp-date-to'], ['date', 'rp-date']].forEach(([key, id]) => {
                    const el = document.getElementById(id);
                    if (!el || !filterKeys.includes(key) || !window.initDatePicker) return;
                    window.initDatePicker(el, {
                        ...dateOpts,
                        defaultDate: this.filters[key] || null,
                        onChange: (_s, dateStr) => { this.filters[key] = dateStr; this.reload(); },
                    });
                });

                this.setupView?.();
                this.reload();
            });
        },

        saveState() {
            const p = new URLSearchParams();
            Object.entries(this.filters).forEach(([k, v]) => { if (v) p.set(k, v); });
            history.replaceState(null, '', p.toString() ? '?' + p.toString() : location.pathname);
        },

        async reload() {
            this.saveState();
            controller?.abort();
            controller = new AbortController();
            this.loading = true;
            this.error = '';
            try {
                const p = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => { if (v) p.set(k, v); });
                const res = await fetch(apiUrl + '?' + p.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.render(await res.json());
            } catch (e) {
                if (e.name === 'AbortError') return;
                console.error('[report] load failed', e);
                this.error = 'Không tải được dữ liệu báo cáo.';
            } finally {
                this.loading = false;
            }
        },
    };
}
