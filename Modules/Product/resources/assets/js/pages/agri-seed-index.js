function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns() {
    return [
        {
            title: 'Nhóm cây trồng', field: 'crop_type', minWidth: 160, sorter: 'string',
        },
        {
            title: 'Tên giống', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
        },
        {
            title: 'Cơ quan đăng ký', field: 'author_applicant', minWidth: 240, headerSort: false,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Quyết định', field: 'decision_number', minWidth: 180, headerSort: false,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'is_banned', width: 150, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return cell.getValue()
                    ? '<span class="badge badge-error badge-sm">Đã loại bỏ</span>'
                    : '<span class="badge badge-success badge-sm">Đang lưu hành</span>';
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

    Alpine.data('agriSeedListPage', (serverData = {}) => {
        const {
            apiUrl    = '',
            cropTypes = [],
        } = serverData;

        const COLUMNS = buildColumns();

        let tableInst = null;

        return {
            filters: { search: '', crop_type: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.crop_type);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.crop_type) chips.push({ key: 'crop_type', label: f.crop_type });
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#agri-seed-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.crop_type) p.crop_type = f.crop_type;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[agri-seed] API error', error),

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
                        + '<p class="text-sm">Không có giống cây trồng nào</p></div>',
                });

                window.agriSeedTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search = p.get('q');
                if (p.has('crop')) this.filters.crop_type = p.get('crop');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.crop_type) p.set('crop', f.crop_type);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'crop_type') this.filters.crop_type = '';
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', crop_type: '' };
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
