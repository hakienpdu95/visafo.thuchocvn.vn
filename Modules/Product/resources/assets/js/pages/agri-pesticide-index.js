function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns() {
    return [
        {
            title: 'Nhóm thuốc', field: 'category', minWidth: 160, sorter: 'string',
        },
        {
            title: 'Tên thương phẩm', field: 'trade_name', minWidth: 200, sorter: 'string', frozen: true,
        },
        {
            title: 'Hoạt chất', field: 'active_ingredients', minWidth: 260, headerSort: false,
        },
        {
            title: 'Ngày cách ly', field: 'quarantine_days', width: 130, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                const v = cell.getValue();
                return v || v === 0 ? v + ' ngày' : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'is_banned', width: 150, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return cell.getValue()
                    ? '<span class="badge badge-error badge-sm">Đã cấm lưu hành</span>'
                    : '<span class="badge badge-success badge-sm">Được phép dùng</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 90, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';
            },
        },
    ];
}

document.addEventListener('alpine:init', () => {

    Alpine.data('agriPesticideListPage', (serverData = {}) => {
        const {
            apiUrl     = '',
            categories = [],
        } = serverData;

        const COLUMNS = buildColumns();

        let tableInst = null;

        return {
            filters: { search: '', category: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.category);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.category) chips.push({ key: 'category', label: f.category });
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#agri-pesticide-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.category) p.category = f.category;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[agri-pesticide] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         50,
                    paginationSizeSelector: [25, 50, 100, 200],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'trade_name', dir: 'asc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        + '<p class="text-sm">Không có thuốc BVTV nào</p></div>',
                });

                window.agriPesticideTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))   this.filters.search = p.get('q');
                if (p.has('cat')) this.filters.category = p.get('cat');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.category) p.set('cat', f.category);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'category') this.filters.category = '';
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', category: '' };
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
