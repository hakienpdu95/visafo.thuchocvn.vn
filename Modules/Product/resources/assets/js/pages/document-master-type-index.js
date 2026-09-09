function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns(canDelete) {
    return [
        {
            title: 'Mã', field: 'code', width: 200, sorter: 'string', frozen: true,
            formatter(cell) {
                return '<span class="font-mono text-xs">' + esc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Tên loại giấy tờ', field: 'name', minWidth: 260, sorter: 'string',
        },
        {
            title: 'Nhóm giấy tờ', field: 'document_group_label', minWidth: 220, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                return esc(d.document_group_label);
            },
        },
        {
            title: 'Ngày cấp', field: 'is_required_issue_date', width: 130, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return cell.getValue()
                    ? '<span class="badge badge-warning badge-sm">Bắt buộc</span>'
                    : '<span class="badge badge-ghost badge-sm">Không bắt buộc</span>';
            },
        },
        {
            title: 'Ngày hết hạn', field: 'is_required_expiry_date', width: 130, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return cell.getValue()
                    ? '<span class="badge badge-warning badge-sm">Bắt buộc</span>'
                    : '<span class="badge badge-ghost badge-sm">Không bắt buộc</span>';
            },
        },
        {
            title: 'Hiệu lực mặc định', field: 'default_validity_months', width: 150, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                const v = cell.getValue();
                return v ? v + ' tháng' : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 110, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.name) + '"'
                        + ' onclick="window.documentMasterTypeDeleteConfirm(this.dataset.url, this.dataset.name)">'
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

window.documentMasterTypeDeleteConfirm = function (url, name) {
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

            if (res.ok) {
                document.getElementById('deleteModal')?.close();
                window.documentMasterTypeTable?.replaceData();
            } else {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[document-master-type] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            pendingDeleteUrl = null;
        }
    });
});

document.addEventListener('alpine:init', () => {

    Alpine.data('documentMasterTypeListPage', (serverData = {}) => {
        const {
            apiUrl         = '',
            documentGroups = [],
            canDelete      = false,
        } = serverData;

        const COLUMNS = buildColumns(canDelete);

        let tableInst = null;

        return {
            filters: { search: '', document_group: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.document_group);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.document_group) {
                    const g = documentGroups.find(x => x.value === f.document_group);
                    chips.push({ key: 'document_group', label: g ? g.text : f.document_group });
                }
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#document-master-type-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.document_group) p.document_group = f.document_group;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[document-master-type] API error', error),

                    // Danh mục tra cứu nhỏ (~15-30 dòng) — tải hết 1 trang rồi group ở client cho dễ nhìn.
                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         100,
                    paginationSizeSelector: [25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    // Không set initialSort — server mặc định sắp theo thứ tự nghiệp vụ của Nhóm giấy tờ (1→4), rồi tới tên.

                    groupBy:        'document_group_label',
                    groupStartOpen: true,
                    groupHeader(value, count) {
                        return esc(value) + ' <span class="opacity-50 text-xs font-normal">(' + count + ')</span>';
                    },

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
                        + '<p class="text-sm">Chưa có loại giấy tờ nào</p></div>',
                });

                window.documentMasterTypeTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))   this.filters.search = p.get('q');
                if (p.has('grp')) this.filters.document_group = p.get('grp');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.document_group) p.set('grp', f.document_group);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'document_group') this.filters.document_group = '';
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', document_group: '' };
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
