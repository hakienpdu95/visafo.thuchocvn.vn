function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns(canDelete) {
    return [
        {
            title: 'Sản phẩm', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<div>'
                    + '<a href="' + esc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + esc(d.name) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + esc(d.sku) + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Mã vạch', field: 'barcode', width: 140, headerSort: false,
            formatter(cell) {
                const v = cell.getValue();
                return v ? '<span class="font-mono text-xs">' + esc(v) + '</span>'
                         : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Ngành hàng', field: 'category_name', minWidth: 160, headerSort: false,
        },
        {
            title: 'Loại sản phẩm', field: 'product_type_label', minWidth: 140, headerSort: false,
        },
        {
            title: 'Hồ sơ pháp lý gần nhất', field: 'compliance_document_name', minWidth: 220, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.compliance_document_name) {
                    return '<span class="text-base-content/25 text-xs">Chưa có</span>';
                }
                let html = '<span class="text-sm">' + esc(d.compliance_document_name) + '</span>';
                if (d.compliance_expired) {
                    html += '<span class="badge badge-error badge-xs ml-1">Hết hạn</span>';
                } else if (d.compliance_expiring) {
                    html += '<span class="badge badge-warning badge-xs ml-1">Sắp hết hạn</span>';
                }
                return html;
            },
        },
        {
            title: 'Trạng thái', field: 'status_value', width: 140, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + esc(d.status_badge) + '">'
                    + esc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
        },
        {
            title: 'Thao tác', field: 'id', width: 110, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + esc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.name) + '"'
                        + ' onclick="window.productDeleteConfirm(this.dataset.url, this.dataset.name)">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        + '</button>';
                }

                html += '</div>';
                return html;
            },
        },
    ];
}

let pendingDeleteUrl = null;

window.productDeleteConfirm = function (url, name) {
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
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                    'Content-Type':     'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('deleteModal')?.close();
                window.productTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[product] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            pendingDeleteUrl = null;
        }
    });
});

const LS_COLS = 'product-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('productListPage', (serverData = {}) => {
        const {
            apiUrl     = '',
            categories = [],
            statuses   = [],
            canDelete  = false,
        } = serverData;

        const COLUMNS = buildColumns(canDelete);

        let tableInst = null;

        return {
            filters: { search: '', category_id: '', status: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.category_id || f.status);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.category_id) {
                    const c = categories.find(x => x.value === f.category_id);
                    chips.push({ key: 'category_id', label: c ? c.text : f.category_id });
                }
                if (f.status) {
                    const st = statuses.find(s => s.value === f.status);
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#product-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)      p.search      = f.search;
                        if (f.category_id) p.category_id = f.category_id;
                        if (f.status)      p.status      = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[product] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'created_at', dir: 'desc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>'
                        + '<p class="text-sm">Không có sản phẩm nào</p></div>',
                });

                window.productTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))   this.filters.search      = p.get('q');
                if (p.has('cat')) this.filters.category_id = p.get('cat');
                if (p.has('st'))  this.filters.status      = p.get('st');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)      p.set('q',   f.search);
                if (f.category_id) p.set('cat', f.category_id);
                if (f.status)      p.set('st',  f.status);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search')      this.filters.search = '';
                if (key === 'category_id') this.filters.category_id = '';
                if (key === 'status')      this.filters.status = '';
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', category_id: '', status: '' };
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
