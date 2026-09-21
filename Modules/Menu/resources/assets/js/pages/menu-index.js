import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

function buildColumns() {
    return [
        {
            title: 'Ngày', field: 'menu_date', width: 120, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const label = '<span class="font-semibold text-sm">' + esc(d.menu_date) + '</span>';
                return d.can_update ? '<a href="' + esc(d.edit_url) + '" class="hover:text-primary transition-colors">' + label + '</a>' : label;
            },
        },
        {
            title: 'Bữa ăn', field: 'meal_time', width: 170, sorter: 'string',
            formatter: (cell) => '<span class="badge badge-sm badge-soft badge-info">' + esc(cell.getRow().getData().meal_label) + '</span>',
        },
        { title: 'Cơ sở', field: 'customer_name', minWidth: 240, headerSort: false, formatter: (cell) => esc(cell.getValue()) || EMPTY },
        {
            title: 'Số món', field: 'dishes_count', width: 100, hozAlign: 'right', headerSort: false,
            formatter: (cell) => '<span class="font-mono">' + esc(cell.getValue()) + '</span>',
        },
        {
            title: 'Tổng số suất', field: 'total_servings', width: 130, hozAlign: 'right', headerSort: false,
            formatter: (cell) => '<span class="font-mono">' + Number(cell.getValue()).toLocaleString('vi-VN') + '</span>',
        },
        { title: 'Ghi chú', field: 'note', minWidth: 180, headerSort: false, formatter: (cell) => esc(cell.getValue()) || EMPTY },
        {
            title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                if (d.can_update) {
                    html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                        + '</a>';
                }
                if (d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.menu_date + ' — ' + d.meal_label) + '"'
                        + ' onclick="window.menuDeleteConfirm(this.dataset.url, this.dataset.name)">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        + '</button>';
                }

                return html + '</div>';
            },
        },
    ];
}

let pendingDeleteUrl = null;

window.menuDeleteConfirm = function (url, name) {
    pendingDeleteUrl = url;
    const nameEl = document.getElementById('deleteItemName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('deleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('deleteModal')?.close();
                window.menuTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[menu] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled = false;
            this.textContent = 'Xóa';
            pendingDeleteUrl = null;
        }
    });
});

const LS_COLS = 'menu-list-hidden-cols';
const EMPTY_FILTERS = { search: '', customer: '', meal: '', dateFrom: '', dateTo: '' };

document.addEventListener('alpine:init', () => {

    Alpine.data('menuListPage', (serverData = {}) => {
        const { apiUrl = '', customers = [], meals = [] } = serverData;
        const COLUMNS = buildColumns();

        let tableInst  = null;
        let tsCustomer = null;
        let tsMeal     = null;

        const label = (list, value) => list.find((o) => o.value === value)?.text ?? value;

        return {
            filters: { ...EMPTY_FILTERS },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS.filter((c) => c.field !== 'id' && c.field !== 'menu_date').map((c) => ({ field: c.field, title: c.title }));
            },

            get hasFilters() { return Object.values(this.filters).some(Boolean); },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search)   chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.customer) chips.push({ key: 'customer', label: label(customers, f.customer) });
                if (f.meal)     chips.push({ key: 'meal', label: label(meals, f.meal) });
                if (f.dateFrom) chips.push({ key: 'dateFrom', label: 'Từ: ' + f.dateFrom });
                if (f.dateTo)   chips.push({ key: 'dateTo', label: 'Đến: ' + f.dateTo });
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const customerEl = document.getElementById('ts-customer');
                const mealEl     = document.getElementById('ts-meal');
                if (!customerEl || !mealEl) return;

                tsCustomer = createTs(customerEl, {
                    placeholder: 'Tất cả cơ sở',
                    maxOptions: null,
                    onChange() { customerEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });
                tsMeal = createTs(mealEl, {
                    placeholder: 'Tất cả bữa ăn',
                    onChange() { mealEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsCustomer.setValue(this.filters.customer, true);
                tsMeal.setValue(this.filters.meal, true);
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#menu-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)   p.search      = f.search;
                        if (f.customer) p.customer_id = f.customer;
                        if (f.meal)     p.meal_time   = f.meal;
                        if (f.dateFrom) p.date_from   = f.dateFrom;
                        if (f.dateTo)   p.date_to     = f.dateTo;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[menu] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'menu_date', dir: 'desc' }],

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
                        + '<p class="text-sm">Chưa có thực đơn nào</p></div>',
                });

                window.menuTable = tableInst;
                self.hiddenCols.forEach((field) => tableInst.hideColumn(field));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search   = p.get('q');
                if (p.has('cus'))  this.filters.customer = p.get('cus');
                if (p.has('meal')) this.filters.meal     = p.get('meal');
                if (p.has('from')) this.filters.dateFrom = p.get('from');
                if (p.has('to'))   this.filters.dateTo   = p.get('to');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)   p.set('q', f.search);
                if (f.customer) p.set('cus', f.customer);
                if (f.meal)     p.set('meal', f.meal);
                if (f.dateFrom) p.set('from', f.dateFrom);
                if (f.dateTo)   p.set('to', f.dateTo);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                this.filters[key] = '';
                if (key === 'customer') tsCustomer?.setValue('', true);
                if (key === 'meal')     tsMeal?.setValue('', true);
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { ...EMPTY_FILTERS };
                tsCustomer?.setValue('', true);
                tsMeal?.setValue('', true);
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
