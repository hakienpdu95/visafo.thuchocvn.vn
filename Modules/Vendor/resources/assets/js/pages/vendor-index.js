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
            title: 'Nhà cung cấp', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<div>'
                    + '<a href="' + esc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + esc(d.name) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + esc(d.vendor_code || '—') + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Mã số thuế', field: 'tax_code', width: 140, headerSort: false,
            formatter(cell) {
                const v = cell.getValue();
                return v ? '<span class="font-mono text-xs">' + esc(v) + '</span>'
                         : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Người đại diện', field: 'representative_name', minWidth: 150, sorter: 'string',
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Email / SĐT', field: 'email', minWidth: 180, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                const parts = [];
                if (d.email) parts.push('<p class="text-sm">' + esc(d.email) + '</p>');
                if (d.phone_number) parts.push('<p class="text-xs text-base-content/50">' + esc(d.phone_number) + '</p>');
                return parts.length ? parts.join('') : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Chứng chỉ mới nhất', field: 'certificate_type', minWidth: 220, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.certificate_type) {
                    return '<span class="text-base-content/25 text-xs">Chưa có</span>';
                }
                let html = '<span class="text-sm">' + esc(d.certificate_type) + '</span>';
                if (d.certificate_expired) {
                    html += '<span class="badge badge-error badge-xs ml-1">Hết hạn</span>';
                } else if (d.certificate_expiring) {
                    html += '<span class="badge badge-warning badge-xs ml-1">Sắp hết hạn</span>';
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
                        + ' onclick="window.vendorDeleteConfirm(this.dataset.url, this.dataset.name)">'
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

window.vendorDeleteConfirm = function (url, name) {
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
                window.vendorTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[vendor] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            pendingDeleteUrl = null;
        }
    });
});

const LS_COLS = 'vendor-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('vendorListPage', (serverData = {}) => {
        const {
            apiUrl       = '',
            wardsApiUrl  = '/api/provinces',
            statuses     = [],
            provinces    = [],
            sourceGroups = [],
            canDelete    = false,
        } = serverData;

        const COLUMNS = buildColumns(canDelete);

        let tableInst     = null;
        let tsStatus      = null;
        let tsProvince    = null;
        let tsWard        = null;
        let tsSourceGroup = null;

        return {
            filters: { search: '', status: '', province: '', ward: '', phone: '', sourceGroup: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.status || f.province || f.ward || f.phone || f.sourceGroup);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.province) {
                    const pv = provinces.find(p => p.value === f.province);
                    chips.push({ key: 'province', label: pv ? pv.text : f.province });
                }
                if (f.ward) {
                    const wd = tsWard?.options?.[f.ward];
                    chips.push({ key: 'ward', label: wd ? wd.text : f.ward });
                }
                if (f.phone) chips.push({ key: 'phone', label: 'SĐT: ' + f.phone });
                if (f.status) {
                    const st = statuses.find(s => s.value === f.status);
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                if (f.sourceGroup) {
                    const sg = sourceGroups.find(g => g.value === f.sourceGroup);
                    chips.push({ key: 'sourceGroup', label: sg ? sg.text : f.sourceGroup });
                }
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const statusEl      = document.getElementById('ts-status');
                const provinceEl    = document.getElementById('ts-province');
                const wardEl        = document.getElementById('ts-ward');
                const sourceGroupEl = document.getElementById('ts-source-group');
                if (!statusEl || !provinceEl || !wardEl || !sourceGroupEl) return;

                tsStatus = createTs(statusEl, {
                    placeholder: 'Tất cả trạng thái',
                    onChange() { statusEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsSourceGroup = createTs(sourceGroupEl, {
                    placeholder: 'Tất cả nhóm nguồn',
                    onChange() { sourceGroupEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsWard = createTs(wardEl, {
                    placeholder: 'Chọn tỉnh / thành phố trước',
                    maxOptions: null,
                    onChange() { wardEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });
                tsWard.disable();

                tsProvince = createTs(provinceEl, {
                    placeholder: 'Tất cả tỉnh / thành phố',
                    maxOptions: null,
                    onChange() { provinceEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                if (this.filters.province) this.loadWardOptions(this.filters.province, this.filters.ward);
            },

            async loadWardOptions(provinceCode, preselectWard = '') {
                if (!tsWard) return;

                tsWard.clear(true);
                tsWard.clearOptions();
                tsWard.addOption({ value: '', text: 'Tất cả' });
                tsWard.disable();

                if (!provinceCode) return;

                tsWard.settings.placeholder = 'Đang tải...';
                tsWard.control_input.placeholder = 'Đang tải...';

                try {
                    const res = await fetch(wardsApiUrl + '/' + provinceCode + '/wards', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const wards = res.ok ? await res.json() : [];
                    wards.forEach(w => tsWard.addOption({ value: w.ward_code, text: w.name }));

                    tsWard.settings.placeholder = 'Tất cả phường / xã';
                    tsWard.control_input.placeholder = 'Tất cả phường / xã';
                    tsWard.enable();

                    if (preselectWard && wards.some(w => w.ward_code === preselectWard)) {
                        tsWard.setValue(preselectWard, true);
                        this.filters.ward = preselectWard;
                    }
                } catch (e) {
                    console.error('[vendor] load wards failed', e);
                    tsWard.settings.placeholder = 'Lỗi tải dữ liệu';
                    tsWard.control_input.placeholder = 'Lỗi tải dữ liệu';
                    tsWard.enable();
                }
            },

            onProvinceChange() {
                this.loadWardOptions(this.filters.province).then(() => this.onFilterChange());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#vendor-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)      p.search        = f.search;
                        if (f.status)      p.status        = f.status;
                        if (f.province)    p.province_code = f.province;
                        if (f.ward)        p.ward_code     = f.ward;
                        if (f.phone)       p.phone_number  = f.phone;
                        if (f.sourceGroup) p.source_group  = f.sourceGroup;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[vendor] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18v4H3V3zm2 4h14v14H5V7zm3 4h8m-8 4h5"/></svg>'
                        + '<p class="text-sm">Không có nhà cung cấp nào</p></div>',
                });

                window.vendorTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search      = p.get('q');
                if (p.has('st'))   this.filters.status      = p.get('st');
                if (p.has('prov')) this.filters.province    = p.get('prov');
                if (p.has('ward')) this.filters.ward        = p.get('ward');
                if (p.has('ph'))   this.filters.phone       = p.get('ph');
                if (p.has('sg'))   this.filters.sourceGroup = p.get('sg');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)      p.set('q', f.search);
                if (f.status)      p.set('st', f.status);
                if (f.province)    p.set('prov', f.province);
                if (f.ward)        p.set('ward', f.ward);
                if (f.phone)       p.set('ph', f.phone);
                if (f.sourceGroup) p.set('sg', f.sourceGroup);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search')      this.filters.search = '';
                if (key === 'phone')       this.filters.phone = '';
                if (key === 'status')      { this.filters.status = ''; tsStatus?.setValue('', true); }
                if (key === 'ward')        { this.filters.ward = ''; tsWard?.setValue('', true); }
                if (key === 'sourceGroup') { this.filters.sourceGroup = ''; tsSourceGroup?.setValue('', true); }
                if (key === 'province') {
                    this.filters.province = '';
                    tsProvince?.setValue('', true);
                    this.loadWardOptions('');
                }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', status: '', province: '', ward: '', phone: '', sourceGroup: '' };
                tsStatus?.setValue('', true);
                tsProvince?.setValue('', true);
                tsSourceGroup?.setValue('', true);
                this.loadWardOptions('');
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
