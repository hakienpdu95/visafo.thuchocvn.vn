function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns() {
    return [
        {
            title: 'Tên giấy tờ', field: 'document_type_name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<div>'
                    + '<p class="font-semibold text-sm">' + esc(d.document_type_name) + '</p>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + esc(d.document_number || '—') + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Thuộc về', field: 'documentable_name', minWidth: 220, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                const badge = '<span class="badge badge-ghost badge-xs">' + esc(d.documentable_label) + '</span>';
                const name = d.documentable_url
                    ? '<a href="' + esc(d.documentable_url) + '" class="hover:text-primary transition-colors">' + esc(d.documentable_name || '—') + '</a>'
                    : esc(d.documentable_name || '—');
                return badge + ' <span class="ml-1 text-sm">' + name + '</span>';
            },
        },
        {
            title: 'Ngày cấp', field: 'issue_date', width: 110, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Hết hạn', field: 'expiration_date', width: 150, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = esc(d.expiration_date) || '<span class="text-base-content/25 text-xs">—</span>';
                if (d.is_expired) {
                    html += ' <span class="badge badge-error badge-xs ml-1">Hết hạn</span>';
                } else if (d.is_expiring) {
                    html += ' <span class="badge badge-warning badge-xs ml-1">Sắp hết hạn</span>';
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
    ];
}

const LS_COLS = 'document-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('documentListPage', (serverData = {}) => {
        const {
            apiUrl            = '',
            documentableTypes = [],
        } = serverData;

        const COLUMNS = buildColumns();

        let tableInst = null;

        return {
            filters: { search: '', documentableType: '', expiring: false, expired: false },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'document_type_name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.documentableType || f.expiring || f.expired);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.documentableType) {
                    const t = documentableTypes.find(x => x.value === f.documentableType);
                    chips.push({ key: 'documentableType', label: t ? t.text : f.documentableType });
                }
                if (f.expiring) chips.push({ key: 'expiring', label: 'Sắp hết hạn' });
                if (f.expired) chips.push({ key: 'expired', label: 'Đã hết hạn' });
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#document-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.documentableType) p.documentable_type = f.documentableType;
                        if (f.expiring) p.expiring = 1;
                        if (f.expired) p.expired = 1;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[document] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'expiration_date', dir: 'desc' }],

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
                        + '<p class="text-sm">Không có hồ sơ nào khớp bộ lọc</p></div>',
                });

                window.documentTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search = p.get('q');
                if (p.has('dt')) this.filters.documentableType = p.get('dt');
                if (p.has('exg')) this.filters.expiring = p.get('exg') === '1';
                if (p.has('exd')) this.filters.expired = p.get('exd') === '1';
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.documentableType) p.set('dt', f.documentableType);
                if (f.expiring) p.set('exg', '1');
                if (f.expired) p.set('exd', '1');
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            onExpiringChange() {
                if (this.filters.expiring) this.filters.expired = false;
                this.onFilterChange();
            },
            onExpiredChange() {
                if (this.filters.expired) this.filters.expiring = false;
                this.onFilterChange();
            },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'documentableType') this.filters.documentableType = '';
                if (key === 'expiring') this.filters.expiring = false;
                if (key === 'expired') this.filters.expired = false;
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', documentableType: '', expiring: false, expired: false };
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
