import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EYE = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

function buildColumns(canDelete) {
    return [
        {
            title: 'Tên mẫu', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<div><p class="font-semibold text-sm">' + esc(d.name) + '</p>'
                    + (d.default_size ? '<p class="text-xs text-base-content/40 mt-0.5">' + esc(d.default_size) + '</p>' : '')
                    + '</div>';
            },
        },
        {
            title: 'View Path', field: 'view_path', minWidth: 240, sorter: 'string',
            formatter(cell) { return '<span class="font-mono text-xs">' + esc(cell.getValue()) + '</span>'; },
        },
        {
            title: 'Kích thước', field: 'default_size', width: 120, hozAlign: 'center', sorter: 'string',
            formatter(cell) { return esc(cell.getValue()) || EMPTY; },
        },
        {
            title: 'Mô tả', field: 'description', minWidth: 260, headerSort: false,
            formatter(cell) {
                const v = cell.getValue();
                return v ? '<span class="text-sm text-base-content/70" title="' + esc(v) + '">' + esc(v) + '</span>' : EMPTY;
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
        },
        {
            title: 'Thao tác', field: 'id', width: 190, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + esc(d.preview_url) + '" target="_blank" rel="noopener" title="Xem trước mẫu tem"'
                    + ' class="btn btn-xs btn-outline border-blue-500 text-blue-600 hover:bg-blue-50 hover:border-blue-500 gap-1 whitespace-nowrap">'
                    + EYE + 'Xem trước</a>';

                if (d.can_update) {
                    html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
                }

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.name) + '"'
                        + ' onclick="window.labelTemplateDeleteConfirm(this.dataset.url, this.dataset.name)">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
                }

                return html + '</div>';
            },
        },
    ];
}

let pendingDeleteUrl = null;

window.labelTemplateDeleteConfirm = function (url, name) {
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
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('deleteModal')?.close();
                window.labelTemplateTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[label-template] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled = false;
            this.textContent = 'Xóa';
            pendingDeleteUrl = null;
        }
    });
});

const LS_COLS = 'label-template-list-hidden-cols';

document.addEventListener('alpine:init', () => {
    Alpine.data('labelTemplateListPage', (serverData = {}) => {
        const { apiUrl = '', sizes = [], canDelete = false } = serverData;
        const COLUMNS = buildColumns(canDelete);

        let tableInst = null;
        let tsSize = null;
        let fpFrom = null;
        let fpTo = null;

        return {
            filters: { search: '', size: '', dateFrom: '', dateTo: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.size || f.dateFrom || f.dateTo);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.size) {
                    const v = sizes.find(x => x.value === f.size);
                    chips.push({ key: 'size', label: v ? v.text : f.size });
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
                const sizeEl = document.getElementById('ts-size');
                if (!sizeEl) return;

                tsSize = createTs(sizeEl, {
                    placeholder: 'Tất cả kích thước',
                    onChange() { sizeEl.dispatchEvent(new Event('change', { bubbles: true })); },
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

                tableInst = new window.Tabulator('#label-template-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.size) p.label_size = f.size;
                        if (f.dateFrom) p.date_from = f.dateFrom;
                        if (f.dateTo) p.date_to = f.dateTo;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[label-template] API error', error),

                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'name', dir: 'asc' }],

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
                        + '<p class="text-sm">Chưa có mẫu tem nào</p></div>',
                });

                window.labelTemplateTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q')) this.filters.search = p.get('q');
                if (p.has('sz')) this.filters.size = p.get('sz');
                if (p.has('from')) this.filters.dateFrom = p.get('from');
                if (p.has('to')) this.filters.dateTo = p.get('to');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.size) p.set('sz', f.size);
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
                if (key === 'size') { this.filters.size = ''; tsSize?.setValue('', true); }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', size: '', dateFrom: '', dateTo: '' };
                this._clearDates();
                tsSize?.setValue('', true);
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
