function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns() {
    return [
        {
            title: 'Thời điểm trừ kho', field: 'sold_at', minWidth: 180, sorter: 'string', frozen: true,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Serial tem', field: 'serial', minWidth: 140, headerSort: false,
            formatter(cell) {
                return '<span class="font-mono text-xs">' + esc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Sản phẩm', field: 'product_name', minWidth: 200, headerSort: false,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Lô hàng', field: 'batch_code', minWidth: 160, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.batch_code) return '<span class="text-base-content/25 text-xs">—</span>';
                return '<a href="' + esc(d.batch_url) + '" class="font-mono text-xs link link-hover">' + esc(d.batch_code) + '</a>';
            },
        },
        {
            title: 'Mã đơn Sapo', field: 'external_order_code', minWidth: 160, headerSort: false,
            formatter(cell) {
                const v = cell.getValue();
                return v ? '<span class="font-mono text-xs">' + esc(v) + '</span>' : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Trạng thái đơn', field: 'external_order_status', width: 150, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Trạng thái tem', field: 'status_value', width: 150, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge ' + esc(d.status_badge) + ' badge-xs">' + esc(d.status_label) + '</span>';
            },
        },
    ];
}

document.addEventListener('alpine:init', () => {

    Alpine.data('sapoSyncLogListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        const COLUMNS = buildColumns();

        let tableInst = null;

        return {
            filters: { search: '' },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#sapo-sync-log-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[sapo-sync-log] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'sold_at', dir: 'desc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v4.5M16 15l3 3M16 15l-3 3M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>'
                        + '<p class="text-sm">Chưa có tem nào được xuất bán qua Sapo</p></div>',
                });

                window.sapoSyncLogTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q')) this.filters.search = p.get('q');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },
        };
    });
});
