import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

const COLUMNS = [
    {
        title: 'Ngày kiểm tra', field: 'inspection_date', width: 130, sorter: 'string', frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<a href="' + esc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + esc(d.inspection_date) + '</a>';
        },
    },
    { title: 'Cơ sở', field: 'customer_name', minWidth: 220, sorter: 'string', formatter: (cell) => esc(cell.getValue()) || EMPTY },
    { title: 'Địa điểm', field: 'location_name', minWidth: 200, headerSort: false, formatter: (cell) => esc(cell.getValue()) || EMPTY },
    {
        title: 'Số món', field: 'details_count', width: 100, hozAlign: 'right', headerSort: false,
        formatter: (cell) => '<span class="font-mono">' + esc(cell.getValue()) + '</span>',
    },
    {
        title: 'Kết quả', field: 'failed_items_count', width: 170, hozAlign: 'center', sorter: 'number',
        formatter(cell) {
            const n = Number(cell.getValue());
            return n > 0
                ? '<span class="badge badge-sm badge-soft badge-error">' + n + ' món không đạt</span>'
                : '<span class="badge badge-sm badge-soft badge-success">Đạt toàn bộ</span>';
        },
    },
    { title: 'Người kiểm tra', field: 'inspector_name', minWidth: 160, headerSort: false, formatter: (cell) => esc(cell.getValue()) || EMPTY },
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

const LS_COLS = 'step3-list-hidden-cols';
const EMPTY_FILTERS = { search: '', customer: '', dateFrom: '', dateTo: '', result: '' };

document.addEventListener('alpine:init', () => {

    Alpine.data('step3ListPage', (serverData = {}) => {
        const {
            apiUrl     = '',
            customers = [],
            results   = [],
        } = serverData;

        let tableInst   = null;
        let tsCustomer = null;
        let tsResult   = null;

        const label = (list, value) => list.find((o) => o.value === value)?.text ?? value;

        return {
            filters: { ...EMPTY_FILTERS },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter((c) => c.field !== 'id' && c.field !== 'inspection_date')
                    .map((c) => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                return Object.values(this.filters).some(Boolean);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.customer) chips.push({ key: 'customer', label: label(customers, f.customer) });
                if (f.dateFrom) chips.push({ key: 'dateFrom', label: 'Từ: ' + f.dateFrom });
                if (f.dateTo) chips.push({ key: 'dateTo', label: 'Đến: ' + f.dateTo });
                if (f.result) chips.push({ key: 'result', label: label(results, f.result) });
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const customerEl = document.getElementById('ts-customer');
                const resultEl   = document.getElementById('ts-result');
                if (!customerEl || !resultEl) return;

                tsCustomer = createTs(customerEl, {
                    placeholder: 'Tất cả cơ sở',
                    maxOptions: null,
                    onChange() { customerEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });
                tsResult = createTs(resultEl, {
                    placeholder: 'Tất cả kết quả',
                    onChange() { resultEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsCustomer.setValue(this.filters.customer, true);
                tsResult.setValue(this.filters.result, true);
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#step3-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)    p.search       = f.search;
                        if (f.customer)  p.customer_id  = f.customer;
                        if (f.dateFrom)  p.date_from    = f.dateFrom;
                        if (f.dateTo)    p.date_to      = f.dateTo;
                        if (f.result)    p.result       = f.result;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[food-inspection] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'inspection_date', dir: 'desc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,
                    height:           '68vh',

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Dòng/trang', page_title: 'Trang',
                                first: '«', last: '»', prev: '‹', next: '›',
                                first_title: 'Trang đầu', last_title: 'Trang cuối',
                                prev_title: 'Trang trước', next_title: 'Trang sau',
                                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                            },
                        },
                    },

                    columns: COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18v4H3V3zm2 4h14v14H5V7zm3 4h8m-8 4h5"/></svg>'
                        + '<p class="text-sm">Chưa có sổ kiểm thực Bước 3 nào</p></div>',
                });

                window.step3Table = tableInst;
                self.hiddenCols.forEach((field) => tableInst.hideColumn(field));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search    = p.get('q');
                if (p.has('cus'))  this.filters.customer  = p.get('cus');
                if (p.has('from')) this.filters.dateFrom  = p.get('from');
                if (p.has('to'))   this.filters.dateTo    = p.get('to');
                if (p.has('rs'))   this.filters.result    = p.get('rs');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)    p.set('q', f.search);
                if (f.customer)  p.set('cus', f.customer);
                if (f.dateFrom)  p.set('from', f.dateFrom);
                if (f.dateTo)    p.set('to', f.dateTo);
                if (f.result)    p.set('rs', f.result);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                this.filters[key] = '';
                if (key === 'customer')  tsCustomer?.setValue('', true);
                if (key === 'result')    tsResult?.setValue('', true);
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { ...EMPTY_FILTERS };
                tsCustomer?.setValue('', true);
                tsResult?.setValue('', true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },

            toggleCol(field) {
                if (this.hiddenCols.includes(field)) {
                    this.hiddenCols = this.hiddenCols.filter((f) => f !== field);
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
