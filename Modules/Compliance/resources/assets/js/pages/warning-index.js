function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns(canManage, csrfToken) {
    return [
        {
            title: 'Mức độ', field: 'severity_value', width: 120, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge ' + esc(d.severity_badge) + ' badge-sm">' + esc(d.severity_label) + '</span>';
            },
        },
        {
            title: 'Loại', field: 'category_label', minWidth: 200, headerSort: false,
            formatter(cell) {
                return '<span class="text-xs">' + esc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Nội dung', field: 'title', minWidth: 280, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<div><div class="font-medium text-sm">' + esc(d.title) + '</div>'
                    + '<div class="text-xs text-base-content/50">' + esc(d.message) + '</div></div>';
            },
        },
        {
            title: 'Hạn', field: 'due_date', width: 110, hozAlign: 'center', headerSort: false,
        },
        {
            title: 'Còn lại', field: 'days_remaining', width: 130, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                const days = cell.getValue();
                return days < 0
                    ? '<span class="text-error font-medium">Quá hạn ' + Math.abs(days) + ' ngày</span>'
                    : days + ' ngày';
            },
        },
        {
            title: 'Trạng thái', field: 'status_value', width: 140, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge ' + esc(d.status_badge) + ' badge-sm">' + esc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 160, hozAlign: 'right', headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!canManage) return '';

                let html = '<div class="flex flex-col items-end gap-1">';

                if (d.can_acknowledge) {
                    html += '<form method="POST" action="' + esc(d.acknowledge_url) + '" class="w-full">'
                        + '<input type="hidden" name="_token" value="' + esc(csrfToken) + '">'
                        + '<button type="submit" class="btn btn-ghost btn-xs w-full">Ghi nhận</button>'
                        + '</form>';
                }

                if (d.can_resolve) {
                    html += '<form method="POST" action="' + esc(d.resolve_url) + '" class="w-full"'
                        + ' onsubmit="return confirm(\'Đánh dấu cảnh báo này đã xử lý xong?\');">'
                        + '<input type="hidden" name="_token" value="' + esc(csrfToken) + '">'
                        + '<button type="submit" class="btn btn-ghost btn-xs text-success w-full">Xử lý xong</button>'
                        + '</form>';
                }

                html += '</div>';
                return html;
            },
        },
    ];
}

document.addEventListener('alpine:init', () => {

    Alpine.data('complianceWarningListPage', (serverData = {}) => {
        const {
            apiUrl     = '',
            statuses   = [],
            categories = [],
            severities = [],
            canManage  = false,
            csrfToken  = '',
        } = serverData;

        const COLUMNS = buildColumns(canManage, csrfToken);

        let tableInst = null;

        return {
            filters: { status: '', category: '', severity: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.status || f.category || f.severity);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.status) {
                    const s = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: s ? s.text : f.status });
                }
                if (f.category) {
                    const c = categories.find(x => x.value === f.category);
                    chips.push({ key: 'category', label: c ? c.text : f.category });
                }
                if (f.severity) {
                    const s = severities.find(x => x.value === f.severity);
                    chips.push({ key: 'severity', label: s ? s.text : f.severity });
                }
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#compliance-warning-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.status)   p.status   = f.status;
                        if (f.category) p.category = f.category;
                        if (f.severity) p.severity = f.severity;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[compliance-warning] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>'
                        + '<p class="text-sm">Không có cảnh báo nào đang mở</p></div>',
                });

                window.complianceWarningTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('st'))  this.filters.status   = p.get('st');
                if (p.has('cat')) this.filters.category = p.get('cat');
                if (p.has('sev')) this.filters.severity = p.get('sev');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.status)   p.set('st',  f.status);
                if (f.category) p.set('cat', f.category);
                if (f.severity) p.set('sev', f.severity);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'status')   this.filters.status = '';
                if (key === 'category') this.filters.category = '';
                if (key === 'severity') this.filters.severity = '';
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { status: '', category: '', severity: '' };
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
