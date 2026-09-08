function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns(canRecall, csrfToken) {
    return [
        {
            title: 'Lô & Sản phẩm', field: 'internal_batch_code', minWidth: 280, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex flex-row gap-3 py-1">';
                html += '<div class="avatar placeholder shrink-0"><div class="bg-neutral text-neutral-content rounded-lg w-12 h-12"><span class="text-lg">'
                    + esc(d.product_name ? d.product_name.charAt(0).toUpperCase() : '?') + '</span></div></div>';
                html += '<div class="min-w-0">';
                html += '<p class="text-sm font-semibold truncate">' + esc(d.product_name || '—') + '</p>';
                html += '<p class="text-xs text-base-content/50 font-mono">' + esc(d.product_sku || '—') + '</p>';
                html += '<a href="' + esc(d.show_url) + '" class="block mt-1.5 font-mono text-base font-bold text-primary hover:underline">' + esc(d.internal_batch_code) + '</a>';
                html += '<p class="text-xs text-base-content/60 mt-1"><span class="text-base-content/40">Nhà cung cấp:</span> ' + esc(d.vendor_name || '—') + '</p>';
                html += '<p class="text-xs text-base-content/60"><span class="text-base-content/40">NSX:</span> ' + esc(d.mfg_date || '—')
                    + '<span class="text-base-content/40 ml-1">HSD:</span> ' + esc(d.exp_date || '—');
                if (d.is_expired) {
                    html += '<span class="badge badge-error badge-xs ml-1">Hết hạn</span>';
                } else if (d.is_expiring) {
                    html += '<span class="badge badge-warning badge-xs ml-1">Sắp hết hạn</span>';
                }
                html += '</p>';
                html += '<span class="badge ' + esc(d.status_badge) + ' badge-sm mt-2">' + esc(d.status_label) + '</span>';
                html += '</div></div>';
                return html;
            },
        },
        {
            title: 'Hồ sơ & Chứng từ', field: 'compliance_document_number', minWidth: 260, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex flex-col gap-2.5 py-1">';

                html += '<div>';
                html += '<p class="text-[10px] uppercase tracking-wide text-base-content/40 mb-1">Truy vết nội bộ</p>';
                html += '<a href="' + esc(d.inbound_receipt_url) + '" class="flex items-center gap-1.5 text-xs link link-hover">'
                    + '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>'
                    + 'Xem phiếu nhập kho gốc</a>';
                html += '</div>';

                html += '<div title="Tài liệu này sẽ hiển thị trực tiếp cho người tiêu dùng khi quét mã QR">';
                html += '<p class="text-[10px] uppercase tracking-wide text-base-content/40 mb-1">Hồ sơ công khai</p>';
                if (d.compliance_document_number) {
                    html += '<a href="' + esc(d.compliance_file_url || '#') + '"'
                        + (d.compliance_file_url ? ' target="_blank" rel="noopener"' : '')
                        + ' class="flex items-center gap-1.5 text-xs text-blue-600 hover:underline font-medium">'
                        + '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75l1.5 1.5 4.5-4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                        + 'Bản công bố / COA (' + esc(d.compliance_document_number) + ')</a>';
                } else {
                    html += '<div class="flex items-center gap-1.5 flex-wrap">';
                    html += '<p class="flex items-center gap-1.5 text-xs text-base-content/40">'
                        + '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0-13.5v4.5m0 3.75h.008v.008H12v-.008z"/></svg>'
                        + 'Chưa cấu hình Bản công bố</p>';
                    if (d.compliance_create_url) {
                        html += '<a href="' + esc(d.compliance_create_url) + '" class="text-xs link link-primary font-medium">Cập nhật ngay</a>';
                    }
                    html += '</div>';
                }
                html += '</div>';

                if (d.adverse_event_reports_count > 0) {
                    html += '<a href="' + esc(d.incidents_url) + '" class="flex items-center gap-1.5 text-xs link link-hover text-error font-medium">'
                        + '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>'
                        + 'Báo cáo sự cố 18-MP (' + esc(d.adverse_event_reports_count) + ')</a>';
                } else {
                    html += '<p class="flex items-center gap-1.5 text-xs text-base-content/40">'
                        + '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>'
                        + 'Không có khiếu nại</p>';
                }

                html += '</div>';
                return html;
            },
        },
        {
            title: 'Tem & Tồn kho', field: 'current_qty', minWidth: 220, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="py-1">';
                html += '<p class="text-xs mb-2">Tồn thực tế: <span class="font-semibold">' + esc(d.current_qty) + '</span> | '
                    + 'Đã gán: <span class="font-semibold">' + esc(d.tags_count) + '</span> | '
                    + 'Sapo: <span class="font-semibold">' + esc(d.tags_exported_count) + '</span></p>';
                html += '<div class="flex flex-col gap-2">';
                html += '<a href="' + esc(d.show_url) + '" class="flex items-center gap-1.5 text-xs link link-hover">'
                    + '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5.586a1 1 0 01.707.293l6.414 6.414a1 1 0 010 1.414l-6.586 6.586a1 1 0 01-1.414 0L5.293 11.293A1 1 0 015 10.586V5a2 2 0 012-2z"/></svg>'
                    + 'Gán dải tem / Quản lý tem</a>';
                html += '<a href="' + esc(d.sapo_sync_log_url) + '" class="flex items-center gap-1.5 text-xs link link-hover">'
                    + '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v4.5M16 15l3 3M16 15l-3 3M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>'
                    + 'Nhật ký đồng bộ Sapo POS</a>';
                html += '</div></div>';
                return html;
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 170, hozAlign: 'right', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex flex-col items-end gap-2 py-1">';
                html += '<a href="' + esc(d.show_url) + '" class="btn btn-primary btn-xs w-full">Xem chi tiết lô</a>';
                html += '<a href="' + esc(d.audit_trail_url) + '" class="btn btn-ghost btn-xs w-full">Lịch sử thao tác</a>';

                if (canRecall && !d.is_recalled) {
                    html += '<form method="POST" action="' + esc(d.recall_url) + '" class="w-full"'
                        + ' onsubmit="return confirm(\'Đánh dấu lô \\\'' + esc(d.internal_batch_code) + '\\\' là thu hồi? Hành động này sẽ khóa toàn bộ hàng của lô trên kệ.\');">'
                        + '<input type="hidden" name="_token" value="' + esc(csrfToken) + '">'
                        + '<button type="submit" class="btn btn-xs bg-red-600 hover:bg-red-700 border-red-600 text-white w-full">Thu hồi lô</button>'
                        + '</form>';
                }

                html += '</div>';
                return html;
            },
        },
    ];
}

document.addEventListener('alpine:init', () => {

    Alpine.data('batchListPage', (serverData = {}) => {
        const {
            apiUrl    = '',
            statuses  = [],
            canRecall = false,
            csrfToken = '',
        } = serverData;

        const COLUMNS = buildColumns(canRecall, csrfToken);

        let tableInst = null;

        return {
            filters: { search: '', status: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'internal_batch_code')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.status);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status) {
                    const st = statuses.find(s => s.value === f.status);
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem('batch-list-hidden-cols') || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#batch-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.status) p.status = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[batch] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         10,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'internal_batch_code', dir: 'asc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,
                    height:           '72vh',

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 8l-9-5-9 5m18 0l-9 5m9-5v10l-9 5m0-10L3 8m9 5v10M3 8v10l9 5"/></svg>'
                        + '<p class="text-sm">Chưa có lô hàng nào</p></div>',
                });

                window.batchTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search = p.get('q');
                if (p.has('st')) this.filters.status = p.get('st');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.status) p.set('st', f.status);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'status') this.filters.status = '';
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', status: '' };
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
                try { localStorage.setItem('batch-list-hidden-cols', JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
