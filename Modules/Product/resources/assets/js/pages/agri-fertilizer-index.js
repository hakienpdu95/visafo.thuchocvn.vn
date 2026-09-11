function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns() {
    return [
        {
            title: 'Nhóm phân bón', field: 'category', minWidth: 160, sorter: 'string',
        },
        {
            title: 'Tên phân bón', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
        },
        {
            title: 'Thành phần chi tiết', field: 'ingredients', minWidth: 320, headerSort: false,
            formatter(cell) {
                const v = cell.getValue();
                if (!v) return '<span class="text-base-content/25 text-xs">—</span>';
                return '<span class="line-clamp-2" title="' + esc(v) + '">' + esc(v) + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'is_banned', width: 160, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return cell.getValue()
                    ? '<span class="badge badge-error badge-sm">Đã cấm/loại bỏ</span>'
                    : '<span class="badge badge-success badge-sm">Được phép dùng</span>';
            },
        },
    ];
}

document.addEventListener('alpine:init', () => {

    Alpine.data('agriFertilizerListPage', (serverData = {}) => {
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

                tableInst = new window.Tabulator('#agri-fertilizer-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.category) p.category = f.category;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[agri-fertilizer] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         50,
                    paginationSizeSelector: [25, 50, 100, 200],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'name', dir: 'asc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3c-4 3-6 6-6 10a6 6 0 0012 0c0-4-2-7-6-10z"/></svg>'
                        + '<p class="text-sm">Không có phân bón nào</p></div>',
                });

                window.agriFertilizerTable = tableInst;
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
