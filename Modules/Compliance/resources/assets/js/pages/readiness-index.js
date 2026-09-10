function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

let readinessRows = [];

function buildColumns() {
    return [
        {
            title: 'Nhà cung cấp', field: 'vendor_name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                return '<span class="font-semibold text-sm">' + esc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'status_value', width: 170, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + esc(d.status_badge) + '">'
                    + esc(d.status_emoji) + ' ' + esc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Số mặt hàng', field: 'partner_product_count', width: 130, hozAlign: 'center', sorter: 'number',
        },
        {
            title: '', field: 'id', width: 130, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.missing || !d.missing.length) {
                    return '<span class="text-xs text-base-content/25">—</span>';
                }
                return '<button type="button" class="btn btn-ghost btn-xs" data-vendor-id="' + esc(d.vendor_id) + '">Xem chi tiết</button>';
            },
            cellClick(e, cell) {
                const btn = e.target.closest('button[data-vendor-id]');
                if (!btn) return;
                const d = cell.getRow().getData();
                window.showReadinessDetail(d);
            },
        },
    ];
}

window.showReadinessDetail = function (row) {
    document.getElementById('readinessDetailVendorName').textContent = row.vendor_name;

    const body = document.getElementById('readinessDetailBody');
    body.innerHTML = (row.missing || []).map(entry => {
        const items = (entry.requirements || []).map(r => '<li>Thiếu ' + esc(r) + '</li>').join('');
        return '<div class="text-sm">'
            + '<p class="font-medium">' + esc(entry.product_name) + '</p>'
            + '<ul class="list-disc list-inside text-xs text-error ml-2">' + items + '</ul>'
            + '</div>';
    }).join('');

    document.getElementById('readinessDetailModal')?.showModal();
};

document.addEventListener('alpine:init', () => {

    Alpine.data('readinessListPage', (serverData = {}) => {
        const {
            apiUrl   = '',
            statuses = [],
        } = serverData;

        const COLUMNS = buildColumns();

        let tableInst = null;

        return {
            filters: { search: '', status: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'vendor_name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.status);
            },

            init() {
                this.$nextTick(() => this._setup());
            },

            async _setup() {
                let payload = { data: [] };
                try {
                    const res = await fetch(apiUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    payload = await res.json();
                } catch (e) {
                    console.error('[readiness] API error', e);
                }

                readinessRows = payload.data || [];

                tableInst = new window.Tabulator('#readiness-table', {
                    data: readinessRows,

                    pagination:             true,
                    paginationMode:         'local',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,
                    height:           '68vh',

                    initialSort: [{ column: 'status_value', dir: 'asc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        + '<p class="text-sm">Chưa có nhà cung cấp nào gắn hàng hóa để đánh giá</p></div>',
                });

                window.readinessTable = tableInst;
                this.hiddenCols.forEach(field => tableInst.hideColumn(field));
            },

            applyFilters() {
                if (!tableInst) return;
                const f = this.filters;
                const search = f.search.trim().toLowerCase();

                tableInst.setFilter((row) => {
                    if (search && !String(row.vendor_name || '').toLowerCase().includes(search)) return false;
                    if (f.status && row.status_value !== f.status) return false;
                    return true;
                });
            },

            clearSearch() { this.filters.search = ''; this.applyFilters(); },

            reset() {
                this.filters = { search: '', status: '' };
                this.applyFilters();
            },

            toggleCol(field) {
                if (this.hiddenCols.includes(field)) {
                    this.hiddenCols = this.hiddenCols.filter(f => f !== field);
                    tableInst?.showColumn(field);
                } else {
                    this.hiddenCols.push(field);
                    tableInst?.hideColumn(field);
                }
            },
        };
    });
});
