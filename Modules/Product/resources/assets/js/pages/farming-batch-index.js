import { createTs } from '@shared/tom-select-factory.js';

const MODAL_ID    = 'farmingBatchModal';
const SOURCE_ID   = 'ts-batch-source';
const SEED_ID     = 'ts-batch-seed';
const PRODUCT_ID  = 'ts-batch-product';

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

function _rebuildProductOptions(modal, vendorId) {
    const el = modal.querySelector('#' + PRODUCT_ID);
    if (!el) return;

    if (!el.tomselect) {
        const ts = createTs(el, {
            dropdownParent: '#' + MODAL_ID,
            placeholder:    'Chọn vùng trồng trước',
        });
        if (!ts) return;
        ts.on('dropdown_open', () => _pinFixed(ts.dropdown, ts.control));
    }

    const ts = el.tomselect;
    const allProducts = JSON.parse(el.dataset.products || '[]');
    const filtered = vendorId ? allProducts.filter((p) => p.vendor_id === vendorId) : [];

    ts.clear(true);
    ts.clearOptions();
    filtered.forEach((p) => ts.addOption({ value: p.id, text: p.name }));

    const placeholder = !vendorId
        ? 'Chọn vùng trồng trước'
        : (filtered.length ? '— Chọn mặt hàng —' : 'Nông hộ này chưa có mặt hàng nào');
    ts.settings.placeholder = placeholder;
    if (ts.control_input) ts.control_input.placeholder = placeholder;

    if (vendorId) ts.enable(); else ts.disable();
}

function _initModalSelects(modal) {
    for (const id of [SOURCE_ID, SEED_ID]) {
        const el = modal.querySelector('#' + id);
        if (!el || el.tomselect) continue;

        const ts = createTs(el, {
            dropdownParent: '#' + MODAL_ID,
            placeholder:    el.dataset.tsPlaceholder,
        });
        if (!ts) continue;

        ts.on('dropdown_open', () => _pinFixed(ts.dropdown, ts.control));
    }

    _rebuildProductOptions(modal, null);

    const sourceEl = modal.querySelector('#' + SOURCE_ID);
    if (sourceEl && !sourceEl._vendorCascadeBound) {
        sourceEl._vendorCascadeBound = true;
        sourceEl.addEventListener('change', () => {
            const option = sourceEl.querySelector('option[value="' + sourceEl.value + '"]');
            _rebuildProductOptions(modal, option?.dataset.vendorId || null);
        });
    }
}

window.openFarmingBatchModal = function () {
    const modal = document.getElementById(MODAL_ID);
    if (!modal) return;
    modal.showModal();
    document.activeElement?.blur();
    requestAnimationFrame(() => _initModalSelects(modal));
};

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById(MODAL_ID);
    if (!modal) return;
    if (modal.dataset.autoopen === '1') {
        modal.showModal();
        document.activeElement?.blur();
    }
    if (modal.open) requestAnimationFrame(() => _initModalSelects(modal));
});

function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const STATUS_BADGE = {
    active:    '<span class="badge badge-info badge-sm">Đang canh tác</span>',
    harvested: '<span class="badge badge-success badge-sm">Đã thu hoạch</span>',
    cancelled: '<span class="badge badge-ghost badge-sm">Đã hủy</span>',
};

const PRE_HARVEST_BADGE = {
    pending: '<span class="badge badge-warning badge-xs">Chưa kiểm tra</span>',
    passed:  '<span class="badge badge-success badge-xs">Đạt Readiness</span>',
    blocked: '<span class="badge badge-error badge-xs">Bị chặn</span>',
};

document.addEventListener('alpine:init', () => {
    Alpine.data('farmingBatchListPage', (serverData = {}) => {
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
                tableInst = new window.Tabulator('#farming-batch-table', {
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
                        {
                            title: 'Mã lô', field: 'batch_code', width: 160, sorter: 'string', frozen: true,
                            formatter: (c) => {
                                const d = c.getRow().getData();
                                return '<a href="' + esc(d.show_url) + '" class="font-mono text-xs font-semibold link link-primary">' + esc(d.batch_code) + '</a>';
                            },
                        },
                        { title: 'Nông hộ', field: 'vendor_name', minWidth: 160, sorter: 'string' },
                        { title: 'Vùng trồng', field: 'farming_source_name', minWidth: 160, headerSort: false },
                        { title: 'Giống', field: 'seed_name', minWidth: 160, headerSort: false },
                        { title: 'Mặt hàng', field: 'partner_product_name', minWidth: 160, headerSort: false },
                        { title: 'Ngày gieo', field: 'sowing_date', width: 110, sorter: 'string', formatter: (c) => esc(c.getValue()) || '<span class="text-base-content/25 text-xs">—</span>' },
                        {
                            title: 'Trạng thái', field: 'status', width: 260, hozAlign: 'center', headerSort: false,
                            formatter: (c) => {
                                const d = c.getRow().getData();
                                return (STATUS_BADGE[d.status] ?? esc(d.status)) + ' ' + (PRE_HARVEST_BADGE[d.pre_harvest_status] ?? '');
                            },
                        },
                    ],
                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Chưa có vụ/lô sản xuất nào</p></div>',
                });
                window.farmingBatchTable = tableInst;
            },

            refresh() { tableInst?.replaceData(); },
            onFilterChange() { this.refresh(); },
            clearSearch() { this.filters.search = ''; this.refresh(); },
            reset() { this.filters = { search: '', status: '' }; this.refresh(); },
        };
    });
});
