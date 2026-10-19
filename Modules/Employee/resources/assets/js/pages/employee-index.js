import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns(canDelete) {
    return [
        {
            formatter: 'responsiveCollapse', width: 34, minWidth: 34, hozAlign: 'center', headerSort: false, resizable: false, responsive: 0,
        },
        {
            title: 'Nhân viên', field: 'full_name', minWidth: 220, sorter: 'string', responsive: 0,
            formatter(cell) {
                const d = cell.getRow().getData();
                const avatar = d.avatar_url
                    ? '<img src="' + esc(d.avatar_url) + '" class="w-7 h-7 rounded-full object-cover shrink-0">'
                    : '<div class="w-7 h-7 rounded-full bg-base-200 flex items-center justify-center text-[10px] text-base-content/40 shrink-0">' + esc((d.full_name || '?').charAt(0)) + '</div>';
                return '<div class="flex items-center gap-2">' + avatar
                    + '<div class="min-w-0"><div class="font-medium truncate">' + esc(d.full_name) + '</div>'
                    + '<div class="text-xs text-base-content/40 truncate">' + esc(d.email || '') + '</div></div></div>';
            },
        },
        {
            title: 'Vai trò công việc', field: 'job_title', minWidth: 140, sorter: 'string', responsive: 1,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Giới tính · Ngày sinh', field: 'dob', width: 175, sorter: 'string', responsive: 2,
            formatter(cell) {
                const d = cell.getRow().getData();
                const parts = [d.gender_label, d.dob].filter(Boolean).map(esc);
                return parts.length ? parts.join(' · ') : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Ngày vào làm', field: 'start_date', width: 135, sorter: 'string', responsive: 3,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Phòng ban', field: 'department_names', minWidth: 150, headerSort: false, responsive: 1,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Điện thoại', field: 'phone', width: 120, headerSort: false, responsive: 4,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Địa chỉ', field: 'address', minWidth: 180, headerSort: false, responsive: 5,
            formatter(cell) {
                const v = cell.getValue();
                return v ? '<span class="text-xs" title="' + esc(v) + '">' + esc(v) + '</span>' : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'CCCD', field: 'id_card_masked', width: 140, headerSort: false, responsive: 0,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.id_card_masked && !d.id_card_images) return '<span class="text-base-content/25 text-xs">—</span>';
                const images = d.id_card_images === 2
                    ? '<span class="badge badge-success badge-xs">Đủ ảnh 2 mặt</span>'
                    : '<span class="badge badge-warning badge-xs">' + (d.id_card_images ? 'Thiếu 1 mặt' : 'Chưa có ảnh') + '</span>';
                return '<div class="leading-tight"><div class="font-mono text-xs">' + (esc(d.id_card_masked) || '—') + '</div>' + images + '</div>';
            },
        },
        {
            title: 'Khám SK', field: 'health_status_label', width: 120, hozAlign: 'center', headerSort: false, responsive: 0,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge ' + esc(d.health_status_class) + ' badge-sm">' + esc(d.health_status_label) + '</span>';
            },
        },
        {
            title: 'ATTP', field: 'attp_status_label', width: 120, hozAlign: 'center', headerSort: false, responsive: 0,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge ' + esc(d.attp_status_class) + ' badge-sm">' + esc(d.attp_status_label) + '</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, responsive: 0,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.full_name) + '"'
                        + ' onclick="window.employeeDeleteConfirm(this.dataset.url, this.dataset.name)">'
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

window.employeeDeleteConfirm = function (url, name) {
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
                window.employeeTable?.replaceData();
            } else {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[employee] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            pendingDeleteUrl = null;
        }
    });
});

document.addEventListener('alpine:init', () => {

    Alpine.data('employeeListPage', (serverData = {}) => {
        const {
            apiUrl      = '',
            departments = [],
            canDelete   = false,
        } = serverData;

        const COLUMNS = buildColumns(canDelete);

        let tableInst    = null;
        let tsDepartment = null;

        return {
            filters: { search: '', department_id: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.department_id);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.department_id) {
                    const d = departments.find(x => x.id === f.department_id);
                    chips.push({ key: 'department_id', label: d ? d.name : f.department_id });
                }
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const departmentEl = document.getElementById('ts-department');
                if (!departmentEl) return;

                tsDepartment = createTs(departmentEl, {
                    placeholder: 'Tất cả phòng ban',
                    onChange() { departmentEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#employee-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.department_id) p.department_id = f.department_id;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[employee] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'full_name', dir: 'asc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    responsiveLayoutCollapseStartOpen: false,
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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4"/></svg>'
                        + '<p class="text-sm">Chưa có nhân viên nào</p></div>',
                });

                window.employeeTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))   this.filters.search = p.get('q');
                if (p.has('dep')) this.filters.department_id = p.get('dep');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.department_id) p.set('dep', f.department_id);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'department_id') { this.filters.department_id = ''; tsDepartment?.setValue('', true); }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', department_id: '' };
                tsDepartment?.setValue('', true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
