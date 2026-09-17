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
                const clipIcon = '<svg class="w-3 h-3 inline-block -mt-0.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>';
                const mediaBadge = '<span class="text-xs text-gray-500">' + clipIcon
                    + (d.media_count > 0 ? d.media_count + ' tệp đính kèm' : 'Chưa có tệp đính kèm') + '</span>';
                return '<div>'
                    + '<p class="font-semibold text-sm">' + esc(d.document_type_name) + '</p>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + esc(d.document_number || '—') + '</p>'
                    + '<p class="mt-0.5">' + mediaBadge + '</p>'
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
        {
            title: 'Thao tác', field: 'actions', width: 130, hozAlign: 'center', headerSort: false, headerHozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">'
                    + '<button type="button" class="btn btn-ghost btn-xs btn-square" title="Xem tệp" onclick="window.openViewDocFilesModal(\'' + d.id + '\')">' + ICON_EYE + '</button>';

                if (d.is_shared) {
                    html += '<button type="button" class="btn btn-ghost btn-xs btn-square text-warning" title="Sửa" onclick="window.openEditSharedDocModal(\'' + d.id + '\')">' + ICON_EDIT + '</button>'
                        + '<button type="button" class="btn btn-ghost btn-xs btn-square text-error" title="Xóa" onclick="window.documentDeleteConfirm(\'' + d.id + '\')">' + ICON_TRASH + '</button>';
                }

                html += '</div>';
                return html;
            },
        },
    ];
}

const ICON_EYE = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
const ICON_EDIT = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
const ICON_TRASH = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
const ICON_IMAGE = '<svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
const ICON_DOC = '<svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
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

    Alpine.data('sharedDocumentUploadForm', () => ({
        files: [],
        existingMedia: [],
        removingMediaId: null,

        onFilesChange(event) {
            this.files = Array.from(event.target.files ?? []);
        },

        removeFile(index) {
            this.files.splice(index, 1);

            // FileList is immutable — rebuild it via DataTransfer so the
            // <input> only submits the files still left in `files`.
            const dt = new DataTransfer();
            this.files.forEach((file) => dt.items.add(file));
            this.$refs.sharedFilesInput.files = dt.files;
        },

        async removeExistingMedia(media) {
            if (this.removingMediaId || !confirm(`Xóa file "${media.name}"?`)) return;

            this.removingMediaId = media.id;
            const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';

            try {
                const res = await fetch(media.delete_url, {
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
                    this.existingMedia = this.existingMedia.filter((m) => m.id !== media.id);
                    window.documentTable?.replaceData();
                } else {
                    const data = await res.json().catch(() => ({}));
                    window.Toast?.error?.(data.message || 'Xóa file thất bại. Vui lòng thử lại.', { duration: 4000 });
                }
            } catch (e) {
                window.Toast?.error?.('Lỗi kết nối. Vui lòng thử lại.', { duration: 4000 });
            } finally {
                this.removingMediaId = null;
            }
        },

        formatFileSize,
    }));

    Alpine.data('documentFilesViewer', () => ({
        docName: '',
        media: [],
        isImage(m) { return m.is_image; },
        formatFileSize,
    }));
});

// ── Modal: Tải/Sửa tài liệu nội bộ dùng chung ────────────────────────────

window.openCreateSharedDocModal = function () {
    const modal = document.getElementById('sharedUploadModal');
    if (!modal) return;

    const form = modal.querySelector('form[data-shared-upload-form]');
    const data = window.Alpine?.$data(form);

    form.action = form.dataset.createUrl;
    form.querySelector('#sharedFormMethod').value = '';
    form.querySelector('[name="custom_name"]').value = '';
    form.querySelector('[name="custom_category"]').value = '';
    form.querySelector('[name="notes"]').value = '';
    form.querySelector('[name="files[]"]').value = '';
    if (data) { data.files = []; data.existingMedia = []; }

    document.getElementById('sharedUploadModalTitle').textContent = 'Tải tài liệu nội bộ dùng chung';
    document.getElementById('sharedUploadModalSubmit').textContent = 'Tải lên';

    modal.showModal();
};

window.openEditSharedDocModal = async function (id) {
    const row = window.documentTable?.getRow(id);
    if (!row) return;
    const editUrl = row.getData().edit_url;
    if (!editUrl) return;

    const res = await fetch(editUrl, { headers: { Accept: 'application/json' } });
    if (!res.ok) {
        window.Toast?.error?.('Không tải được dữ liệu tài liệu.', { duration: 4000 });
        return;
    }
    const doc = await res.json();

    const modal = document.getElementById('sharedUploadModal');
    if (!modal) return;

    const form = modal.querySelector('form[data-shared-upload-form]');
    const data = window.Alpine?.$data(form);

    form.action = form.dataset.updateUrlTemplate.replace('__ID__', doc.id);
    form.querySelector('#sharedFormMethod').value = 'PUT';
    form.querySelector('[name="custom_name"]').value = doc.custom_name ?? '';
    form.querySelector('[name="custom_category"]').value = doc.custom_category ?? '';
    form.querySelector('[name="notes"]').value = doc.notes ?? '';
    form.querySelector('[name="files[]"]').value = '';
    if (data) { data.files = []; data.existingMedia = doc.media ?? []; }

    document.getElementById('sharedUploadModalTitle').textContent = 'Sửa tài liệu nội bộ dùng chung';
    document.getElementById('sharedUploadModalSubmit').textContent = 'Lưu thay đổi';

    modal.showModal();
};

// ── Modal: Xem danh sách tệp đính kèm (read-only) ────────────────────────

window.openViewDocFilesModal = function (id) {
    const row = window.documentTable?.getRow(id);
    if (!row) return;
    const d = row.getData();

    const content = document.getElementById('documentFilesModalContent');
    const data = window.Alpine?.$data(content);
    if (!data) return;

    data.docName = d.document_type_name;
    data.media = d.media ?? [];
    document.getElementById('documentFilesModal')?.showModal();
};

// ── Modal: Xác nhận xóa tài liệu nội bộ dùng chung ───────────────────────

let pendingDocumentDeleteUrl = null;

window.documentDeleteConfirm = function (id) {
    const row = window.documentTable?.getRow(id);
    if (!row) return;
    const d = row.getData();
    if (!d.delete_url) return;

    pendingDocumentDeleteUrl = d.delete_url;
    const nameEl = document.getElementById('documentDeleteItemName');
    if (nameEl) nameEl.textContent = d.document_type_name;
    document.getElementById('documentDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('confirmDocumentDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDocumentDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(pendingDocumentDeleteUrl, {
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
                document.getElementById('documentDeleteModal')?.close();
                window.documentTable?.replaceData();
                window.Toast?.success?.('Đã xóa tài liệu.', { duration: 4000 });
            } else {
                const data = await res.json().catch(() => ({}));
                window.Toast?.error?.(data.message || 'Xóa thất bại. Vui lòng thử lại.', { duration: 4000 });
            }
        } catch (e) {
            window.Toast?.error?.('Lỗi kết nối. Vui lòng thử lại.', { duration: 4000 });
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    window.initFormValidation?.('[data-shared-upload-form]');
});
