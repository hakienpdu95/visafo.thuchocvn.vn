function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns() {
    return [
        {
            title: 'Thời gian', field: 'created_at', minWidth: 160, sorter: 'string', frozen: true,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Nguồn', field: 'source_label', width: 130, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                const cls = d.source === 'webhook' ? 'badge-info' : 'badge-ghost';
                return '<span class="badge ' + cls + ' badge-xs">' + esc(d.source_label) + '</span>';
            },
        },
        {
            title: 'Sự kiện', field: 'event_label', width: 110, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return '<span class="text-xs font-medium">' + esc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Sản phẩm / SKU', field: 'product_name', minWidth: 240, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="py-1">';
                if (d.product_name) {
                    html += d.product_url
                        ? '<a href="' + esc(d.product_url) + '" class="text-sm link link-hover">' + esc(d.product_name) + '</a>'
                        : '<p class="text-sm">' + esc(d.product_name) + '</p>';
                } else {
                    html += '<p class="text-sm text-base-content/40">—</p>';
                }
                if (d.sku) {
                    html += '<p class="text-xs text-base-content/50 font-mono">' + esc(d.sku) + '</p>';
                }
                html += '</div>';
                return html;
            },
        },
        {
            title: 'Trạng thái', field: 'status_label', width: 130, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge ' + esc(d.status_badge) + ' badge-sm">' + esc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Chi tiết', field: 'message', minWidth: 220, headerSort: false,
            formatter(cell) {
                const v = cell.getValue();
                if (!v) return '<span class="text-base-content/25 text-xs">—</span>';
                return '<span class="text-xs text-error/80">' + esc(v) + '</span>';
            },
        },
    ];
}

document.addEventListener('alpine:init', () => {

    Alpine.data('sapoProductSyncLogListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        const COLUMNS = buildColumns();

        let tableInst = null;
        let initialized = false;

        return {
            filters: { search: '', status: '' },

            init() {
                window.addEventListener('sapo-products-tab-shown', () => {
                    if (!initialized) {
                        initialized = true;
                        this._setup();
                    } else {
                        tableInst?.redraw(true);
                    }
                });
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#sapo-product-sync-log-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.status) p.status = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[sapo-product-sync-log] API error', error),

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
                        + '<p class="text-sm">Chưa có lượt đồng bộ sản phẩm nào</p></div>',
                });

                window.sapoProductSyncLogTable = tableInst;
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.refresh(); },
        };
    });
});
