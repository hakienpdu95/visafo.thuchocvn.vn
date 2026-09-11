import { createTs } from '@shared/tom-select-factory.js';

const MODAL_ID = 'farmingSourceModal';

function _pinFixed(panel, anchor) {
    const rect = anchor.getBoundingClientRect();
    Object.assign(panel.style, {
        position: 'fixed',
        top:      (rect.bottom + 4) + 'px',
        left:     rect.left + 'px',
        right:    'auto',
        width:    rect.width + 'px',
        margin:   0,
    });
}

function _initVendorSelect(modal) {
    const el = modal.querySelector('#ts-farming-source-vendor');
    if (!el || el.tomselect) return;

    const ts = createTs(el, {
        dropdownParent: '#' + MODAL_ID,
        placeholder:    '— Chọn nông hộ —',
    });
    if (!ts) return;

    ts.on('dropdown_open', () => _pinFixed(ts.dropdown, ts.control));
}

function _setModalMode(modal, source) {
    const form = modal.querySelector('form');
    const methodEl = form.querySelector('input[name="_method"]');
    const titleEl = modal.querySelector('#farmingSourceModalTitle');
    const submitEl = modal.querySelector('#farmingSourceModalSubmit');

    if (source) {
        form.action = form.dataset.updateUrlTemplate.replace('__ID__', source.id);
        methodEl.value = 'PUT';
        titleEl.textContent = 'Sửa vùng trồng';
        submitEl.textContent = 'Lưu thay đổi';
    } else {
        form.action = form.dataset.createUrl;
        methodEl.value = '';
        titleEl.textContent = 'Thêm vùng trồng';
        submitEl.textContent = 'Lưu vùng trồng';
    }
}

function _fillForm(modal, source) {
    modal.querySelector('[name="name"]').value = source.name ?? '';
    modal.querySelector('[name="area_hectare"]').value = source.area_hectare ?? '';
    modal.querySelector('[name="water_source"]').value = source.water_source ?? '';
    modal.querySelector('[name="address"]').value = source.address ?? '';
    modal.querySelector('[name="notes"]').value = source.notes ?? '';

    const select = modal.querySelector('#ts-farming-source-vendor');
    if (select?.tomselect) select.tomselect.setValue(source.vendor_id, false);
}

function _resetForm(modal) {
    modal.querySelector('form').reset();
    modal.querySelector('#ts-farming-source-vendor')?.tomselect?.clear(false);
}

window.openFarmingSourceModal = function (source) {
    const modal = document.getElementById(MODAL_ID);
    if (!modal) return;
    _setModalMode(modal, source || null);
    modal.showModal();
    document.activeElement?.blur();
    requestAnimationFrame(() => {
        _initVendorSelect(modal);
        if (source) _fillForm(modal, source);
        else _resetForm(modal);
    });
};

function _confirmPreSeason(source, table) {
    if (!confirm('Xác nhận đã kiểm tra vùng trồng "' + source.name + '" — cho phép mở vụ?')) return;

    const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
    fetch('/dashboard/farming-sources/' + source.id + '/confirm-pre-season', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN':     csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept':           'application/json',
        },
    }).then((res) => {
        if (res.ok) table.replaceData();
        else alert('Xác nhận thất bại. Vui lòng thử lại.');
    }).catch(() => alert('Lỗi kết nối. Vui lòng thử lại.'));
}

function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const STATUS_BADGE = {
    pending: '<span class="badge badge-warning badge-sm">Chờ kiểm tra</span>',
    passed:  '<span class="badge badge-success badge-sm">Đã kiểm tra — Đạt</span>',
    failed:  '<span class="badge badge-error badge-sm">Đã kiểm tra — Không đạt</span>',
};

document.addEventListener('alpine:init', () => {
    Alpine.data('farmingSourceListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;
        let tableInst = null;

        return {
            filters: { search: '', status: '' },

            get hasFilters() {
                return !!(this.filters.search || this.filters.status);
            },

            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;
                tableInst = new window.Tabulator('#farming-source-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.status) p.status = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,

                    pagination: true, paginationMode: 'remote', paginationSize: 25,
                    paginationSizeSelector: [25, 50, 100], paginationCounter: 'rows',
                    sortMode: 'remote', initialSort: [{ column: 'created_at', dir: 'desc' }],

                    layout: 'fitColumns', responsiveLayout: 'collapse', movableColumns: true, height: '68vh',
                    locale: 'vi-VN',
                    langs: { 'vi-VN': { pagination: {
                        page_size: 'Dòng/trang', page_title: 'Trang', first: '«', last: '»', prev: '‹', next: '›',
                        first_title: 'Trang đầu', last_title: 'Trang cuối', prev_title: 'Trang trước', next_title: 'Trang sau',
                        counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                    } } },

                    columns: [
                        { title: 'Nông hộ', field: 'vendor_name', minWidth: 180, sorter: 'string' },
                        { title: 'Mã vùng', field: 'source_code', width: 120, formatter: (c) => '<span class="font-mono text-xs">' + esc(c.getValue()) + '</span>' },
                        { title: 'Tên vùng trồng', field: 'name', minWidth: 200, sorter: 'string' },
                        { title: 'Diện tích (ha)', field: 'area_hectare', width: 120, hozAlign: 'center', formatter: (c) => c.getValue() ? esc(c.getValue()) : '<span class="text-base-content/25 text-xs">—</span>' },
                        { title: 'Nguồn nước', field: 'water_source', minWidth: 150, headerSort: false, formatter: (c) => esc(c.getValue()) || '<span class="text-base-content/25 text-xs">—</span>' },
                        {
                            title: 'Trạng thái phê duyệt', field: 'status', width: 170, hozAlign: 'center', headerSort: false,
                            formatter: (c) => STATUS_BADGE[c.getValue()] ?? esc(c.getValue()),
                        },
                        {
                            title: '', field: 'id', width: 180, hozAlign: 'center', headerSort: false,
                            formatter: (cell) => {
                                const d = cell.getRow().getData();
                                let html = '<div class="flex items-center justify-center gap-1">'
                                    + '<button type="button" class="btn btn-ghost btn-xs" data-action="edit">Sửa</button>';
                                if (d.status === 'pending') {
                                    html += '<button type="button" class="btn btn-outline btn-success btn-xs" data-action="confirm">Xác nhận kiểm tra</button>';
                                }
                                return html + '</div>';
                            },
                            cellClick: (e, cell) => {
                                const action = e.target.closest('[data-action]')?.dataset.action;
                                const data = cell.getRow().getData();
                                if (action === 'edit') window.openFarmingSourceModal(data);
                                if (action === 'confirm') _confirmPreSeason(data, cell.getTable());
                            },
                        },
                    ],
                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Chưa có vùng trồng nào</p></div>',
                });
                window.farmingSourceTable = tableInst;
            },

            loadState() {},
            saveState() {},
            refresh() { tableInst?.replaceData(); },
            onFilterChange() { this.refresh(); },
            clearSearch() { this.filters.search = ''; this.refresh(); },
            reset() { this.filters = { search: '', status: '' }; this.refresh(); },
        };
    });
});
