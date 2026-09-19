import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Số phiếu', field: 'misa_ref_id', minWidth: 160, sorter: 'string', frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<a href="' + esc(d.show_url) + '" class="font-mono font-semibold text-sm hover:text-primary transition-colors">' + esc(d.misa_ref_id) + '</a>';
        },
    },
    {
        title: 'Khách hàng', field: 'customer_name', minWidth: 220, sorter: 'string',
        formatter(cell) { return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>'; },
    },
    {
        title: 'Địa chỉ giao hàng', field: 'delivery_address', minWidth: 240, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v ? '<span class="text-sm" title="' + esc(v) + '">' + esc(v) + '</span>' : '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Số dòng hàng', field: 'items_count', width: 120, hozAlign: 'center', headerSort: false,
    },
    {
        title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            return '<span class="badge badge-sm badge-soft badge-warning">' + esc(cell.getRow().getData().status_label) + '</span>';
        },
    },
    {
        title: 'File nguồn', field: 'source_file_name', minWidth: 200, headerSort: false,
        formatter(cell) { return '<span class="text-xs text-base-content/60">' + esc(cell.getValue()) + '</span>'; },
    },
    {
        title: 'Nhập lúc', field: 'created_at', width: 140, hozAlign: 'center', sorter: 'string',
    },
    {
        title: 'Thao tác', field: 'id', width: 90, hozAlign: 'center', headerSort: false, frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<a href="' + esc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                + '</a>';
        },
    },
];

const LS_COLS = 'sales-order-list-hidden-cols';

document.addEventListener('alpine:init', () => {
    Alpine.data('salesOrderListPage', (serverData = {}) => {
        const { apiUrl = '', statuses = [] } = serverData;

        let tableInst = null;
        let tsStatus = null;
        let fpFrom = null;
        let fpTo = null;

        return {
            filters: { search: '', status: '', dateFrom: '', dateTo: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'misa_ref_id')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.status || f.dateFrom || f.dateTo);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status) {
                    const v = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: v ? v.text : f.status });
                }
                if (f.dateFrom || f.dateTo) {
                    chips.push({ key: 'date', label: 'Ngày: ' + (f.dateFrom || '...') + ' → ' + (f.dateTo || '...') });
                }
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => { this._setup(); this._initTomSelects(); this._initDatePickers(); });
            },

            _initTomSelects() {
                const statusEl = document.getElementById('ts-status');
                if (!statusEl) return;

                tsStatus = createTs(statusEl, {
                    placeholder: 'Tất cả trạng thái',
                    onChange() { statusEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });
            },

            _initDatePickers() {
                const fromEl = document.getElementById('fp-date-from');
                const toEl = document.getElementById('fp-date-to');
                if (!fromEl || !toEl || !window.initDatePicker) return;

                const opts = (key, other) => ({
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: true,
                    disableMobile: true,
                    onChange: (_sel, dateStr) => {
                        this.filters[key] = dateStr;
                        other()?.set(key === 'dateFrom' ? 'minDate' : 'maxDate', dateStr || null);
                        this.onFilterChange();
                    },
                });

                fpFrom = window.initDatePicker(fromEl, { ...opts('dateFrom', () => fpTo), defaultDate: this.filters.dateFrom || null });
                fpTo = window.initDatePicker(toEl, { ...opts('dateTo', () => fpFrom), defaultDate: this.filters.dateTo || null });
                if (this.filters.dateFrom) fpTo.set('minDate', this.filters.dateFrom);
                if (this.filters.dateTo) fpFrom.set('maxDate', this.filters.dateTo);
            },

            _clearDates() {
                this.filters.dateFrom = '';
                this.filters.dateTo = '';
                fpFrom?.clear(false);
                fpTo?.clear(false);
                fpFrom?.set('maxDate', null);
                fpTo?.set('minDate', null);
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#sales-order-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.status) p.status = f.status;
                        if (f.dateFrom) p.date_from = f.dateFrom;
                        if (f.dateTo) p.date_to = f.dateTo;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[sales-order] API error', error),

                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'created_at', dir: 'desc' }],

                    layout: 'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns: true,
                    height: '68vh',

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Dòng/trang', page_title: 'Trang',
                                first: '«', last: '»', prev: '‹', next: '›',
                                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                            },
                        },
                    },

                    columns: COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<p class="text-sm">Chưa có đơn xuất hàng nào</p></div>',
                });

                window.salesOrderTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q')) this.filters.search = p.get('q');
                if (p.has('st')) this.filters.status = p.get('st');
                if (p.has('from')) this.filters.dateFrom = p.get('from');
                if (p.has('to')) this.filters.dateTo = p.get('to');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.status) p.set('st', f.status);
                if (f.dateFrom) p.set('from', f.dateFrom);
                if (f.dateTo) p.set('to', f.dateTo);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh() { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch() { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'date') this._clearDates();
                if (key === 'status') { this.filters.status = ''; tsStatus?.setValue('', true); }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', status: '', dateFrom: '', dateTo: '' };
                this._clearDates();
                tsStatus?.setValue('', true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },

            toggleCol(field) {
                if (this.hiddenCols.includes(field)) {
                    this.hiddenCols = this.hiddenCols.filter(f => f !== field);
                    tableInst?.showColumn(field);
                } else {
                    this.hiddenCols.push(field);
                    tableInst?.hideColumn(field);
                }
                try { localStorage.setItem(LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
