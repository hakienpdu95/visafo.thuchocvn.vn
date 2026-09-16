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
            title: 'Hợp đồng', field: 'name', minWidth: 240, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<div>'
                    + '<a href="' + esc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + esc(d.name) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + esc(d.contract_number) + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Nhà cung cấp', field: 'vendor_name', minWidth: 180, sorter: 'string',
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Loại hợp đồng', field: 'contract_type', minWidth: 200, headerSort: false,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Giá trị', field: 'total_value', width: 140, hozAlign: 'right', sorter: 'number',
            formatter(cell) {
                const v = cell.getValue();
                return v != null
                    ? '<span class="font-mono text-xs">' + Number(v).toLocaleString('vi-VN') + '</span>'
                    : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Thời hạn', field: 'end_date', minWidth: 170, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<span class="text-xs">' + esc(d.start_date) + ' → ' + esc(d.end_date || 'Vô thời hạn') + '</span>';
                if (d.is_auto_renew) {
                    html += '<span class="badge badge-info badge-xs ml-1">Auto</span>';
                }
                return html;
            },
        },
        {
            title: 'Trạng thái', field: 'status_value', width: 130, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + esc(d.status_badge) + '">'
                    + esc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 110, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + esc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.name) + '"'
                        + ' onclick="window.contractDeleteConfirm(this.dataset.url, this.dataset.name)">'
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

window.contractDeleteConfirm = function (url, name) {
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

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('deleteModal')?.close();
                window.contractTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[contract] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            pendingDeleteUrl = null;
        }
    });
});

document.addEventListener('alpine:init', () => {

    Alpine.data('contractListPage', (serverData = {}) => {
        const {
            apiUrl        = '',
            statuses      = [],
            contractTypes = [],
            partyTypes    = [],
            vendors       = [],
            customers     = [],
            canDelete     = false,
        } = serverData;

        const COLUMNS = buildColumns(canDelete);

        let tableInst      = null;
        let tsStatus       = null;
        let tsContractType = null;
        let tsType         = null;
        let tsVendor       = null;
        let tsCustomer     = null;

        return {
            filters: { search: '', status: '', contractType: '', type: '', vendorId: '', customerId: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.status || f.contractType || f.type || f.vendorId || f.customerId);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.type) {
                    const pt = partyTypes.find(t => t.value === f.type);
                    chips.push({ key: 'type', label: pt ? pt.text : f.type });
                }
                if (f.vendorId) {
                    const v = vendors.find(x => x.id === f.vendorId);
                    chips.push({ key: 'vendorId', label: v ? v.name : f.vendorId });
                }
                if (f.customerId) {
                    const c = customers.find(x => x.id === f.customerId);
                    chips.push({ key: 'customerId', label: c ? c.name : f.customerId });
                }
                if (f.contractType) {
                    const ct = contractTypes.find(t => t.value === f.contractType);
                    chips.push({ key: 'contractType', label: ct ? ct.text : f.contractType });
                }
                if (f.status) {
                    const st = statuses.find(s => s.value === f.status);
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const statusEl       = document.getElementById('ts-status');
                const contractTypeEl = document.getElementById('ts-contract-type');
                const typeEl         = document.getElementById('ts-type');
                const vendorEl       = document.getElementById('ts-filter-vendor');
                const customerEl     = document.getElementById('ts-filter-customer');
                if (!statusEl || !contractTypeEl || !typeEl || !vendorEl || !customerEl) return;

                tsStatus = createTs(statusEl, {
                    placeholder: 'Tất cả trạng thái',
                    onChange() { statusEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsContractType = createTs(contractTypeEl, {
                    placeholder: 'Tất cả loại hợp đồng',
                    onChange() { contractTypeEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsType = createTs(typeEl, {
                    placeholder: 'Tất cả loại giao dịch',
                    onChange() { typeEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                if (this.filters.type === 'input')  this._ensurePartnerTs('vendor');
                if (this.filters.type === 'output') this._ensurePartnerTs('customer');
            },

            _ensurePartnerTs(kind) {
                if (kind === 'vendor' && !tsVendor) {
                    const el = document.getElementById('ts-filter-vendor');
                    tsVendor = createTs(el, {
                        placeholder: 'Tất cả nhà cung cấp',
                        onChange() { el.dispatchEvent(new Event('change', { bubbles: true })); },
                    });
                }
                if (kind === 'customer' && !tsCustomer) {
                    const el = document.getElementById('ts-filter-customer');
                    tsCustomer = createTs(el, {
                        placeholder: 'Tất cả khách hàng',
                        onChange() { el.dispatchEvent(new Event('change', { bubbles: true })); },
                    });
                }
            },

            onTypeChange() {
                if (this.filters.type === 'input') {
                    this.filters.customerId = '';
                    tsCustomer?.setValue('', true);
                    requestAnimationFrame(() => this._ensurePartnerTs('vendor'));
                } else if (this.filters.type === 'output') {
                    this.filters.vendorId = '';
                    tsVendor?.setValue('', true);
                    requestAnimationFrame(() => this._ensurePartnerTs('customer'));
                } else {
                    this.filters.vendorId   = '';
                    this.filters.customerId = '';
                    tsVendor?.setValue('', true);
                    tsCustomer?.setValue('', true);
                }
                this.onFilterChange();
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#contract-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)       p.search           = f.search;
                        if (f.status)       p.status           = f.status;
                        if (f.contractType) p.contract_type_id = f.contractType;
                        if (f.type)         p.type             = f.type;
                        if (f.vendorId)     p.vendor_id        = f.vendorId;
                        if (f.customerId)   p.customer_id      = f.customerId;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[contract] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'created_at', dir: 'desc' }],

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
                        + '<p class="text-sm">Không có hợp đồng nào</p></div>',
                });

                window.contractTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search       = p.get('q');
                if (p.has('st')) this.filters.status       = p.get('st');
                if (p.has('ct')) this.filters.contractType = p.get('ct');
                if (p.has('ty')) this.filters.type         = p.get('ty');
                if (p.has('vd')) this.filters.vendorId     = p.get('vd');
                if (p.has('cu')) this.filters.customerId   = p.get('cu');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)       p.set('q', f.search);
                if (f.status)       p.set('st', f.status);
                if (f.contractType) p.set('ct', f.contractType);
                if (f.type)         p.set('ty', f.type);
                if (f.vendorId)     p.set('vd', f.vendorId);
                if (f.customerId)   p.set('cu', f.customerId);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search')       this.filters.search = '';
                if (key === 'status')       { this.filters.status = ''; tsStatus?.setValue('', true); }
                if (key === 'contractType') { this.filters.contractType = ''; tsContractType?.setValue('', true); }
                if (key === 'vendorId')     { this.filters.vendorId = ''; tsVendor?.setValue('', true); }
                if (key === 'customerId')   { this.filters.customerId = ''; tsCustomer?.setValue('', true); }
                if (key === 'type') {
                    this.filters.type       = '';
                    this.filters.vendorId   = '';
                    this.filters.customerId = '';
                    tsType?.setValue('', true);
                    tsVendor?.setValue('', true);
                    tsCustomer?.setValue('', true);
                }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', status: '', contractType: '', type: '', vendorId: '', customerId: '' };
                tsStatus?.setValue('', true);
                tsContractType?.setValue('', true);
                tsType?.setValue('', true);
                tsVendor?.setValue('', true);
                tsCustomer?.setValue('', true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
