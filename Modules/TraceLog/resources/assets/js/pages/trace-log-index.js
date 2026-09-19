import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EYE = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

function openDetail(row) {
    window.dispatchEvent(new CustomEvent('open-trace-detail', { detail: row }));
}

const COLUMNS = [
    {
        title: 'Mã TXNG', field: 'trace_code', minWidth: 140, sorter: 'string', frozen: true,
        formatter(cell) {
            return '<button type="button" class="font-mono font-semibold text-sm text-primary hover:underline" title="Xem chi tiết tem">'
                + esc(String(cell.getValue()).toUpperCase()) + '</button>';
        },
        cellClick(_e, cell) { openDetail(cell.getRow().getData()); },
    },
    {
        title: 'Sản phẩm', field: 'product_name', minWidth: 200, headerSort: false,
        formatter(cell) { return esc(cell.getValue()) || EMPTY; },
    },
    {
        title: 'Đơn hàng', field: 'order_ref', minWidth: 130, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            return d.order_url
                ? '<a href="' + esc(d.order_url) + '" class="font-mono text-sm hover:text-primary transition-colors" title="Mở chi tiết đơn hàng">' + esc(d.order_ref) + '</a>'
                : EMPTY;
        },
    },
    {
        title: 'Khách hàng', field: 'customer_name', minWidth: 200, headerSort: false,
        formatter(cell) { return esc(cell.getValue()) || EMPTY; },
    },
    {
        title: 'KL / Tem (kg)', field: 'weight_per_label', width: 130, hozAlign: 'right', sorter: 'number',
        formatter(cell) { return '<span class="font-mono">' + esc(cell.getRow().getData().weight) + '</span>'; },
    },
    {
        title: 'Ngày in / Người in', field: 'created_at', minWidth: 160, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<div><p class="text-sm">' + esc(d.printed_at) + '</p>'
                + '<p class="text-xs text-base-content/50">' + (esc(d.printed_by) || '—') + '</p></div>';
        },
    },
    {
        title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="badge badge-sm badge-soft ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: 'Thao tác', field: 'id', width: 130, hozAlign: 'center', headerSort: false, frozen: true,
        formatter() {
            return '<button type="button" title="Xem chi tiết tem" '
                + 'class="btn btn-xs btn-outline border-blue-500 text-blue-600 hover:bg-blue-50 hover:border-blue-500 gap-1 whitespace-nowrap">'
                + EYE + 'Chi tiết</button>';
        },
        cellClick(_e, cell) { openDetail(cell.getRow().getData()); },
    },
];

const LS_COLS = 'trace-log-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    // ── Danh sách + bộ lọc ─────────────────────────────────────────────
    Alpine.data('traceLogListPage', (serverData = {}) => {
        const { apiUrl = '', customers = [], statuses = [] } = serverData;

        let tableInst = null;
        let tsCustomer = null;
        let tsStatus = null;
        let fpFrom = null;
        let fpTo = null;
        let openIfSingle = false;

        return {
            filters: { search: '', customer: '', status: '', dateFrom: '', dateTo: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'trace_code')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.customer || f.status || f.dateFrom || f.dateTo);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.customer) chips.push({ key: 'customer', label: f.customer });
                if (f.status) {
                    const st = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                if (f.dateFrom || f.dateTo) {
                    chips.push({ key: 'date', label: 'Ngày in: ' + (f.dateFrom || '...') + ' → ' + (f.dateTo || '...') });
                }
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (_) {}
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
                    initialSort: [{ column: 'created_at', dir: 'desc' }],

                    layout: 'fitColumns',
                    responsiveLayout: 'collapse',
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
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.customer) p.set('cu', f.customer);
                if (f.status) p.set('st', f.status);
                if (f.dateFrom) p.set('from', f.dateFrom);
                if (f.dateTo) p.set('to', f.dateTo);
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
                if (key === 'date') this._clearDates();
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', customer: '', status: '', dateFrom: '', dateTo: '' };
                this._clearDates();
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
