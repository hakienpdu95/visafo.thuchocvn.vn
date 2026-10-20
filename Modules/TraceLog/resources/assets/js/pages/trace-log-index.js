import { createTs } from '@shared/tom-select-factory.js';
import { printSourceCascade, printSourcePayload, printSourceState } from '@shared/print-source-cascade.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EYE = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
const PEN = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.536 9 17l.464-3.536z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 20h16"/></svg>';
const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

function openDetail(row) {
    window.dispatchEvent(new CustomEvent('open-trace-detail', { detail: row }));
}

function openEdit(row) {
    window.dispatchEvent(new CustomEvent('open-trace-edit', { detail: row }));
}

const toDdMmYy = (ymd) => {
    if (!ymd) return '';
    const [y, m, d] = ymd.split('-');
    return d + m + y.slice(2);
};

function initials(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '';
    return (parts.length === 1 ? parts[0].slice(0, 2) : parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

const COLUMNS = [
    {
        title: 'Mã TXNG', field: 'trace_code', width: 130, sorter: 'string', frozen: true,
        formatter(cell) {
            return '<button type="button" class="font-mono font-semibold text-sm text-primary hover:underline" title="Xem chi tiết tem">'
                + esc(String(cell.getValue()).toUpperCase()) + '</button>';
        },
        cellClick(_e, cell) { openDetail(cell.getRow().getData()); },
    },
    {
        title: 'Sản phẩm', field: 'product_name', minWidth: 180, headerSort: false,
        formatter(cell) { return '<span class="whitespace-normal leading-snug">' + (esc(cell.getValue()) || EMPTY) + '</span>'; },
    },
    {
        title: 'Khách hàng / Đơn hàng', field: 'customer_name', minWidth: 190, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            const order = d.order_url
                ? '<a href="' + esc(d.order_url) + '" class="font-mono hover:text-primary transition-colors" title="Mở chi tiết đơn hàng">ĐH: ' + esc(d.order_ref) + '</a>'
                : '<span>ĐH: —</span>';
            return '<div class="leading-snug"><p class="text-sm font-medium truncate" title="' + esc(d.customer_name) + '">' + (esc(d.customer_name) || '—') + '</p>'
                + '<p class="text-xs text-base-content/50">' + order + '</p></div>';
        },
    },
    {
        title: 'Nguồn gốc / Lô', field: 'supplier_name', minWidth: 190, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            const supplier = d.supplier_name
                ? '<span class="truncate" title="' + esc(d.supplier_name) + '">' + esc(d.supplier_name) + '</span>'
                    + (d.vendor_linked ? '' : ' <span class="badge badge-xs badge-warning shrink-0" title="Nguồn cung nhập tay — không truy vết được theo nhà cung cấp">Nhập tay</span>')
                : '<span class="text-base-content/30">—</span>';
            const lot = (d.receipt_batch
                    ? '<span title="Lô nhập kho">' + esc(d.receipt_batch) + '</span>'
                    : '<span class="badge badge-xs badge-soft badge-warning" title="Tem chưa gắn lô nhập kho">Chưa gắn lô</span>')
                + (d.batch_code ? ' <span class="text-base-content/50" title="Mã lô trên tem">' + esc(d.batch_code) + '</span>' : '');
            return '<div class="leading-snug"><p class="text-sm font-medium flex items-center gap-1 min-w-0">' + supplier + '</p>'
                + '<p class="font-mono text-xs text-base-content/50 truncate">' + lot + '</p></div>';
        },
    },
    {
        title: 'KL / Tem', field: 'weight_per_label', width: 95, hozAlign: 'right', sorter: 'number',
        formatter(cell) { return '<span class="font-mono text-sm">' + esc(cell.getRow().getData().weight) + '</span>'; },
    },
    {
        title: 'Ngày in / Người in', field: 'last_printed_at', width: 160, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            const by = d.printed_by
                ? '<span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-base-200 text-[10px] font-semibold text-base-content/70 shrink-0" title="Người in: ' + esc(d.printed_by) + '">' + esc(initials(d.printed_by)) + '</span>'
                : '';
            const reprinted = d.print_count > 1
                ? '<p class="text-xs text-base-content/50" title="In gần nhất ' + esc(d.last_printed_at) + '">In ' + d.print_count + ' lần · ' + esc(d.last_printed_at) + '</p>'
                : '';
            return '<div class="leading-snug"><p class="text-sm flex items-center gap-1.5">' + by + '<span>' + esc(d.printed_at) + '</span></p>' + reprinted + '</div>';
        },
    },
    {
        title: 'Trạng thái', field: 'status', width: 120, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="badge badge-sm badge-soft whitespace-nowrap ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: 'Thao tác', field: 'id', width: 90, minWidth: 90, hozAlign: 'center', headerSort: false, frozen: true, cssClass: 'whitespace-nowrap',
        formatter(cell) {
            const d = cell.getRow().getData();
            const editable = d.status === 'active';
            return '<div class="flex items-center justify-center gap-0.5 whitespace-nowrap">'
                + '<button type="button" data-action="detail" title="Xem chi tiết tem" class="btn btn-sm btn-ghost btn-square text-info">' + EYE + '</button>'
                + (d.can_edit
                    ? '<button type="button" data-action="edit" ' + (editable ? '' : 'disabled ')
                        + 'title="' + (editable ? 'Sửa thông tin TXNG (giữ nguyên mã QR)' : 'Chỉ sửa được tem đang lưu hành') + '" '
                        + 'class="btn btn-sm btn-ghost btn-square text-warning">' + PEN + '</button>'
                    : '')
                + '</div>';
        },
        cellClick(e, cell) {
            const btn = e.target.closest('button[data-action]');
            if (!btn || btn.disabled) return;
            const d = cell.getRow().getData();
            btn.dataset.action === 'edit' ? openEdit(d) : openDetail(d);
        },
    },
];

const LS_COLS = 'trace-log-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    // ── Danh sách + bộ lọc ─────────────────────────────────────────────
    Alpine.data('traceLogListPage', (serverData = {}) => {
        const { apiUrl = '', customers = [], statuses = [], vendors = [] } = serverData;

        let tableInst = null;
        let tsCustomer = null;
        let tsStatus = null;
        let tsVendor = null;
        let fpFrom = null;
        let fpTo = null;
        let openIfSingle = false;

        return {
            filters: { search: '', customer: '', status: '', dateFrom: '', dateTo: '', vendor: '', batch: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'trace_code')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.customer || f.status || f.dateFrom || f.dateTo || f.vendor || f.batch);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.customer) chips.push({ key: 'customer', label: f.customer });
                if (f.status) {
                    const st = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                if (f.vendor) {
                    const v = vendors.find(x => x.value === f.vendor);
                    chips.push({ key: 'vendor', label: 'NCC: ' + (v ? v.text : f.vendor) });
                }
                if (f.batch) chips.push({ key: 'batch', label: 'Lô: ' + f.batch });
                if (f.dateFrom || f.dateTo) {
                    chips.push({ key: 'date', label: 'Ngày in: ' + (f.dateFrom || '...') + ' → ' + (f.dateTo || '...') });
                }
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (_) {}
                this.hiddenCols = this.hiddenCols.filter(f => this.toggleableCols.some(c => c.field === f));
                this.$nextTick(() => {
                    this._setup();
                    this._initTomSelects();
                    this._initDatePickers();
                    document.getElementById('filter-search')?.focus(); // sẵn sàng cho súng quét mã
                });
            },

            _initTomSelects() {
                const customerEl = document.getElementById('ts-customer');
                const statusEl = document.getElementById('ts-status');
                if (customerEl) {
                    tsCustomer = createTs(customerEl, {
                        placeholder: 'Tất cả khách hàng',
                        maxOptions: null,
                        onChange() { customerEl.dispatchEvent(new Event('change', { bubbles: true })); },
                    });
                }
                const vendorEl = document.getElementById('ts-vendor');
                if (vendorEl) {
                    tsVendor = createTs(vendorEl, {
                        placeholder: 'Tất cả nhà cung cấp',
                        maxOptions: null,
                        onChange() { vendorEl.dispatchEvent(new Event('change', { bubbles: true })); },
                    });
                }
                if (statusEl) {
                    tsStatus = createTs(statusEl, {
                        placeholder: 'Tất cả trạng thái',
                        onChange() { statusEl.dispatchEvent(new Event('change', { bubbles: true })); },
                    });
                }
            },

            _initDatePickers() {
                const fromEl = document.getElementById('fp-date-from');
                const toEl = document.getElementById('fp-date-to');
                if (!fromEl || !toEl || !window.initDatePicker) return;

                const opts = (key, other) => ({
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: true,
                    disableMobile: true,
                    onChange: (_sel, dateStr) => {
                        this.filters[key] = dateStr;
                        other()?.set(key === 'dateFrom' ? 'minDate' : 'maxDate', dateStr || null);
                        this.onFilterChange();
                    },
                });

                fpFrom = window.initDatePicker(fromEl, { ...opts('dateFrom', () => fpTo), defaultDate: this.filters.dateFrom || null });
                fpTo = window.initDatePicker(toEl, { ...opts('dateTo', () => fpFrom), defaultDate: this.filters.dateTo || null });
                if (this.filters.dateFrom) fpTo.set('minDate', this.filters.dateFrom);
                if (this.filters.dateTo) fpFrom.set('maxDate', this.filters.dateTo);
            },

            _clearDates() {
                this.filters.dateFrom = '';
                this.filters.dateTo = '';
                fpFrom?.clear(false);
                fpTo?.clear(false);
                fpFrom?.set('maxDate', null);
                fpTo?.set('minDate', null);
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#trace-log-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.customer) p.customer = f.customer;
                        if (f.status) p.status = f.status;
                        if (f.dateFrom) p.date_from = f.dateFrom;
                        if (f.dateTo) p.date_to = f.dateTo;
                        if (f.vendor) p.vendor = f.vendor;
                        if (f.batch) p.batch = f.batch;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[trace-log] API error', error),

                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'last_printed_at', dir: 'desc' }],

                    layout: 'fitColumns',
                    movableColumns: true,
                    height: '68vh',

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Dòng/trang', page_title: 'Trang',
                                first: '«', last: '»', prev: '‹', next: '›',
                                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                            },
                        },
                    },

                    columns: COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Không tìm thấy tem nào</p></div>',
                });

                // Quét đúng một mã (súng quét + Enter) → mở luôn hồ sơ tem.
                tableInst.on('dataLoaded', (data) => {
                    if (openIfSingle && Array.isArray(data) && data.length === 1) openDetail(data[0]);
                    openIfSingle = false;
                });

                window.traceLogTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q')) this.filters.search = p.get('q');
                if (p.has('cu')) this.filters.customer = p.get('cu');
                if (p.has('st')) this.filters.status = p.get('st');
                if (p.has('from')) this.filters.dateFrom = p.get('from');
                if (p.has('to')) this.filters.dateTo = p.get('to');
                if (p.has('vd')) this.filters.vendor = p.get('vd');
                if (p.has('lot')) this.filters.batch = p.get('lot');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.customer) p.set('cu', f.customer);
                if (f.status) p.set('st', f.status);
                if (f.dateFrom) p.set('from', f.dateFrom);
                if (f.dateTo) p.set('to', f.dateTo);
                if (f.vendor) p.set('vd', f.vendor);
                if (f.batch) p.set('lot', f.batch);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh() { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },

            // Nhấn Enter (súng quét mã vạch/QR gửi Enter cuối chuỗi): tìm ngay, không chờ debounce, rồi bôi đen để quét tiếp.
            onSearchEnter(event) {
                openIfSingle = true;
                this.onFilterChange();
                event.target.select();
            },

            clearSearch() { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'customer') { this.filters.customer = ''; tsCustomer?.setValue('', true); }
                if (key === 'status') { this.filters.status = ''; tsStatus?.setValue('', true); }
                if (key === 'vendor') { this.filters.vendor = ''; tsVendor?.setValue('', true); }
                if (key === 'batch') this.filters.batch = '';
                if (key === 'date') this._clearDates();
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', customer: '', status: '', dateFrom: '', dateTo: '', vendor: '', batch: '' };
                this._clearDates();
                tsVendor?.setValue('', true);
                tsCustomer?.setValue('', true);
                tsStatus?.setValue('', true);
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

    Alpine.data('traceLogEditModal', (serverData = {}) => {
        const { vendors = [] } = serverData;
        const cascade = printSourceCascade({ vendors, logTag: 'trace-edit', dropdownParent: 'body' });
        let mfgPicker = null;
        let expPicker = null;
        let reqId = 0;
        let uid = 0;

        return {
            ...cascade,
            open: false,
            loading: false,
            loadError: '',
            saving: false,
            errors: {},
            data: null,
            row: null,
            form: { mfg: '', exp: '', batchCode: '', applyAll: false },

            get sourceError() {
                const e = this.errors;
                return e.product_batch_id || e.vendor_id || e.partner_product_id || e.supplier_name || '';
            },

            init() {
                this.$nextTick(() => {
                    if (!window.initDatePicker) return;
                    const opts = { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: true, disableMobile: true, static: true };
                    mfgPicker = window.initDatePicker('#te-mfg', {
                        ...opts,
                        onChange: (_s, dateStr) => { this.form.mfg = dateStr; this.recalcBatchCode(); },
                    });
                    expPicker = window.initDatePicker('#te-exp', {
                        ...opts,
                        onChange: (_s, dateStr) => { this.form.exp = dateStr; this.recalcBatchCode(); },
                    });
                });
            },

            recalcBatchCode() {
                const mfg = toDdMmYy(this.form.mfg);
                const exp = toDdMmYy(this.form.exp);
                this.form.batchCode = (mfg && exp) ? `LOT-${mfg}-${exp}` : '';
            },

            async openFor(listRow) {
                const id = ++reqId;
                this.open = true;
                this.loading = true;
                this.loadError = '';
                this.errors = {};
                this.data = null;
                this.row = null;
                try {
                    const res = await fetch(listRow.edit_url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const d = await res.json();
                    if (id !== reqId) return;
                    if (d.status !== 'active') {
                        this.loadError = 'Chỉ sửa được tem đang lưu hành (tem này: ' + d.status_label + ').';
                        return;
                    }
                    this._fill(d);
                } catch (e) {
                    console.error('[trace-log] edit load failed', e);
                    if (id === reqId) this.loadError = 'Không tải được thông tin tem. Vui lòng thử lại.';
                } finally {
                    if (id === reqId) this.loading = false;
                }
            },

            _fill(d) {
                const batches = d.batches ?? [];
                this.data = d;
                this.form = { mfg: d.mfg_date || '', exp: d.exp_date || '', batchCode: d.batch_code || '', applyAll: false };
                if (this.form.mfg) mfgPicker?.setDate(this.form.mfg, false); else mfgPicker?.clear(false);
                if (this.form.exp) expPicker?.setDate(this.form.exp, false); else expPicker?.clear(false);
                if (!this.form.batchCode) this.recalcBatchCode();

                this.row = printSourceState({
                    id: 'trace-edit',
                    uid: ++uid,
                    partnerProductsUrl: d.partner_products_url,
                    hasBatches: d.has_batches,
                    batches,
                    batchesLoaded: true,
                    batchId: d.product_batch_id || '',
                    batchText: d.batch_text || '',
                    batchPartnerId: batches.find((b) => b.value === d.product_batch_id)?.partner_product_id ?? '',
                    vendorId: d.vendor_id || '',
                    vendorName: d.vendor_name || '',
                    partnerProductId: d.partner_product_id || '',
                    supplierManual: !d.vendor_id && !!d.supplier_name,
                    supplierText: d.vendor_id ? '' : (d.supplier_name || ''),
                });
                this.$nextTick(() => this.loadRowPartners(this.row));
            },

            close() {
                if (this.saving) return;
                this.open = false;
            },

            async save() {
                if (!this.data || !this.row || this.saving) return;
                this.errors = {};
                if (!this.form.exp) {
                    this.errors.exp_date = 'HSD là bắt buộc.';
                    return;
                }

                this.saving = true;
                try {
                    const res = await fetch(this.data.update_url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            mfg_date: this.form.mfg || null,
                            exp_date: this.form.exp,
                            batch_code: this.form.batchCode || null,
                            ...printSourcePayload(this.row),
                            update_all_related: this.form.applyAll && (this.data.related_count ?? 0) > 1,
                        }),
                    });
                    const data = await res.json().catch(() => ({}));

                    if (res.status === 422) {
                        Object.entries(data.errors ?? {}).forEach(([k, v]) => { this.errors[k] = v[0]; });
                        return;
                    }
                    if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);

                    window.Toast?.success?.(data.message, { duration: 4000 });
                    window.traceLogTable?.replaceData();
                    this.saving = false;
                    this.close();
                } catch (e) {
                    console.error('[trace-log] update failed', e);
                    this.errors.general = 'Không cập nhật được thông tin TXNG: ' + e.message;
                } finally {
                    this.saving = false;
                }
            },
        };
    });

    // ── Modal hồ sơ tem + đổi trạng thái ───────────────────────────────
    Alpine.data('traceLogDetailModal', () => ({
        open: false,
        loading: false,
        error: '',
        saving: false,
        message: '',
        formError: '',
        detail: null,
        form: { status: 'active', reason: '', applySession: false },
        reissue: { confirming: false, reason: '', saving: false, error: '' },

        get statusOptions() {
            return [
                { value: 'active', label: 'Đang lưu hành' },
                { value: 'recalled', label: 'Thu hồi' },
                { value: 'error', label: 'Tem lỗi' },
            ];
        },

        async openFor(row) {
            this.open = true;
            this.loading = true;
            this.error = '';
            this.message = '';
            this.formError = '';
            this.detail = null;

            try {
                const res = await fetch(row.detail_url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this._setDetail(await res.json());
            } catch (e) {
                console.error('[trace-log] detail failed', e);
                this.error = 'Không tải được hồ sơ tem. Vui lòng thử lại.';
            } finally {
                this.loading = false;
            }
        },

        _setDetail(d) {
            this.detail = d;
            this.form = { status: d.status, reason: d.status_reason || '', applySession: false };
            this.reissue = { confirming: false, reason: '', saving: false, error: '' };
        },

        async doReissue() {
            if (!this.detail || this.reissue.saving) return;
            if (!this.reissue.reason.trim()) {
                this.reissue.error = 'Vui lòng nhập lý do hủy mã.';
                return;
            }

            // Mở cửa sổ ngay trong thao tác click để không bị chặn popup, điền URL in sau khi có mã mới
            const win = window.open('about:blank', '_blank');
            this.reissue.saving = true;
            this.reissue.error = '';
            try {
                const res = await fetch(this.detail.reissue_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    body: JSON.stringify({ reason: this.reissue.reason.trim() }),
                });
                const data = await res.json().catch(() => ({}));

                if (res.status === 422) {
                    win?.close();
                    this.reissue.error = Object.values(data.errors ?? {})[0]?.[0] ?? 'Dữ liệu không hợp lệ.';
                    return;
                }
                if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);

                const keepCan = this.detail.can_manage;
                this._setDetail({ ...data.detail, can_manage: keepCan }); // modal chuyển sang tem mới
                this.message = data.message;
                window.traceLogTable?.replaceData();

                if (win) {
                    win.location = data.print_url;
                } else {
                    this.message += ' Trình duyệt đã chặn cửa sổ in — cho phép popup rồi mở lại.';
                }
            } catch (e) {
                console.error('[trace-log] reissue failed', e);
                win?.close();
                this.reissue.error = 'Không cấp lại được mã: ' + e.message;
            } finally {
                this.reissue.saving = false;
            }
        },

        close() { this.open = false; },

        async saveStatus() {
            if (!this.detail || this.saving) return;
            this.formError = '';
            this.message = '';

            if (this.form.status !== 'active' && !this.form.reason.trim()) {
                this.formError = 'Vui lòng nhập lý do thu hồi/lỗi.';
                return;
            }

            this.saving = true;
            try {
                const res = await fetch(this.detail.status_url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        status: this.form.status,
                        reason: this.form.status === 'active' ? null : this.form.reason.trim(),
                        apply_to_session: this.form.applySession,
                    }),
                });
                const data = await res.json().catch(() => ({}));

                if (res.status === 422) {
                    this.formError = Object.values(data.errors ?? {})[0]?.[0] ?? 'Dữ liệu không hợp lệ.';
                    return;
                }
                if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);

                const keepCan = this.detail.can_manage;
                this._setDetail({ ...data.detail, can_manage: keepCan });
                this.message = data.message;
                window.traceLogTable?.replaceData();
            } catch (e) {
                console.error('[trace-log] status failed', e);
                this.formError = 'Không cập nhật được trạng thái: ' + e.message;
            } finally {
                this.saving = false;
            }
        },
    }));
});
