import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const PEN = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.536 9 17l.464-3.536z"/></svg>';
const LINK = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>';
const LS_COLS = 'hanoicheck-order-list-hidden-cols';

const SYNC = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';

function openDetail(row) {
    window.dispatchEvent(new CustomEvent('open-hanoicheck-detail', { detail: row }));
}

function openLink(row) {
    window.dispatchEvent(new CustomEvent('open-hanoicheck-link', { detail: row }));
}

const COLUMNS = [
    {
        title: 'Đơn VISAFO', field: 'misa_ref_id', width: 130, sorter: 'string', frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<a href="' + esc(d.order_url) + '" class="font-mono font-semibold text-sm text-primary hover:underline" title="Mở chi tiết đơn hàng">' + esc(d.misa_ref_id) + '</a>';
        },
    },
    {
        title: 'Khách hàng', field: 'customer_name', minWidth: 200, sorter: 'string',
        formatter(cell) { return '<span class="whitespace-normal leading-snug">' + (esc(cell.getValue()) || '—') + '</span>'; },
    },
    { title: 'Ngày giao', field: 'delivery_date', width: 110, hozAlign: 'center', sorter: 'string' },
    {
        title: 'Trạng thái', field: 'status', width: 120, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="badge badge-sm whitespace-nowrap ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: 'Mã đơn HanoiCheck', field: 'hanoicheck_order_code', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            const value = d.hanoicheck_order_code
                ? '<span class="font-mono font-semibold text-sm">' + esc(d.hanoicheck_order_code) + '</span>'
                : '<span class="badge badge-sm badge-ghost">Chưa liên kết</span>';
            const note = d.note ? '<p class="text-xs text-base-content/50 truncate" title="' + esc(d.note) + '">' + esc(d.note) + '</p>' : '';
            const btn = d.can_link
                ? '<button type="button" data-action="link" class="btn btn-xs btn-ghost gap-1 ' + (d.hanoicheck_order_code ? 'text-warning' : 'text-primary') + '">'
                    + (d.hanoicheck_order_code ? PEN + 'Sửa' : LINK + 'Nhập mã') + '</button>'
                : '';
            return '<div class="flex items-center justify-between gap-2"><div class="min-w-0 leading-snug">' + value + note + '</div>' + btn + '</div>';
        },
        cellClick(e, cell) {
            if (e.target.closest('button[data-action="link"]')) openLink(cell.getRow().getData());
        },
    },
    {
        title: 'Trạng thái Sở', field: 'remote_status', width: 170, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.hanoicheck_order_code) return '<span class="text-base-content/25 text-xs">—</span>';
            const badge = d.remote_status_label
                ? '<span class="badge badge-sm whitespace-nowrap ' + esc(d.remote_status_badge) + '">' + esc(d.remote_status_label) + '</span>'
                : '<span class="text-xs text-base-content/40">Chưa đọc</span>';
            const school = d.school_name ? '<p class="text-xs text-base-content/50 truncate" title="' + esc(d.school_name) + '">' + esc(d.school_name) + '</p>' : '';
            return '<div class="leading-snug">' + badge + school + '</div>';
        },
    },
    {
        title: 'Đồng bộ', field: 'sync_status', minWidth: 200, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.hanoicheck_order_code) return '<span class="text-base-content/25 text-xs">—</span>';
            const msg = d.sync_message ? '<p class="text-xs text-base-content/60 truncate" title="' + esc(d.sync_message) + '">' + esc(d.sync_message) + '</p>' : '';
            return '<div class="flex items-center justify-between gap-2"><div class="min-w-0 leading-snug"><span class="badge badge-sm whitespace-nowrap ' + esc(d.sync_status_badge) + '">' + esc(d.sync_status_label) + '</span>' + msg + '</div>'
                + '<button type="button" data-action="detail" class="btn btn-xs btn-ghost gap-1 text-info shrink-0">' + SYNC + 'Xử lý</button></div>';
        },
        cellClick(e, cell) {
            if (e.target.closest('button[data-action="detail"]')) openDetail(cell.getRow().getData());
        },
    },
    {
        title: 'Liên kết bởi', field: 'linked_at', width: 170, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.linked_at) return '<span class="text-base-content/25 text-xs">—</span>';
            return '<div class="leading-snug"><p class="text-sm">' + esc(d.linked_by || '—') + '</p><p class="text-xs text-base-content/50">' + esc(d.linked_at) + '</p></div>';
        },
    },
];

function headers() {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
    };
}

document.addEventListener('alpine:init', () => {

    Alpine.data('hanoiCheckOrderListPage', (serverData = {}) => {
        const { apiUrl = '', statuses = [], links = [], syncStatuses = [] } = serverData;

        let tableInst = null;
        let tsStatus = null;
        let tsLink = null;
        let tsSync = null;
        let fpFrom = null;
        let fpTo = null;

        return {
            filters: { search: '', link: '', status: '', dateFrom: '', dateTo: '', sync: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS.filter(c => c.field !== 'misa_ref_id' && c.field !== 'hanoicheck_order_code')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.link || f.status || f.dateFrom || f.dateTo || f.sync);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.link) chips.push({ key: 'link', label: links.find(x => x.value === f.link)?.text ?? f.link });
                if (f.status) chips.push({ key: 'status', label: statuses.find(x => x.value === f.status)?.text ?? f.status });
                if (f.sync) chips.push({ key: 'sync', label: 'Đồng bộ: ' + (syncStatuses.find(x => x.value === f.sync)?.text ?? f.sync) });
                if (f.dateFrom || f.dateTo) chips.push({ key: 'date', label: 'Ngày giao: ' + (f.dateFrom || '...') + ' → ' + (f.dateTo || '...') });
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
                });
                window.addEventListener('hanoicheck-refresh', () => tableInst?.replaceData());
                window.addEventListener('hanoicheck-linked', (e) => {
                    const row = tableInst?.getRow(e.detail.id);
                    if (row) row.update(e.detail.patch);
                });
            },

            _initTomSelects() {
                const statusEl = document.getElementById('ts-status');
                const linkEl = document.getElementById('ts-link');
                if (statusEl) tsStatus = createTs(statusEl, { placeholder: 'Tất cả trạng thái', onChange() { statusEl.dispatchEvent(new Event('change', { bubbles: true })); } });
                const syncEl = document.getElementById('ts-sync');
                if (syncEl) tsSync = createTs(syncEl, { placeholder: 'Tất cả', onChange() { syncEl.dispatchEvent(new Event('change', { bubbles: true })); } });
                if (linkEl) tsLink = createTs(linkEl, { placeholder: 'Tất cả', onChange() { linkEl.dispatchEvent(new Event('change', { bubbles: true })); } });
            },

            _initDatePickers() {
                const fromEl = document.getElementById('fp-date-from');
                const toEl = document.getElementById('fp-date-to');
                if (!fromEl || !toEl || !window.initDatePicker) return;
                const opts = (key, other) => ({
                    dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: true, disableMobile: true,
                    onChange: (_sel, dateStr) => {
                        this.filters[key] = dateStr;
                        other()?.set(key === 'dateFrom' ? 'minDate' : 'maxDate', dateStr || null);
                        this.onFilterChange();
                    },
                });
                fpFrom = window.initDatePicker(fromEl, { ...opts('dateFrom', () => fpTo), defaultDate: this.filters.dateFrom || null });
                fpTo = window.initDatePicker(toEl, { ...opts('dateTo', () => fpFrom), defaultDate: this.filters.dateTo || null });
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
                tableInst = new window.Tabulator('#hanoicheck-order-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.link) p.link = f.link;
                        if (f.status) p.status = f.status;
                        if (f.dateFrom) p.date_from = f.dateFrom;
                        if (f.dateTo) p.date_to = f.dateTo;
                        if (f.sync) p.sync = f.sync;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[hanoicheck-order] API error', error),
                    index: 'id',
                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'delivery_date', dir: 'desc' }],
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
                    selectableRows: false,
                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Không có đơn hàng nào</p></div>',
                });
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));
            },

            toggleCol(field) {
                const i = this.hiddenCols.indexOf(field);
                if (i === -1) { this.hiddenCols.push(field); tableInst?.hideColumn(field); }
                else { this.hiddenCols.splice(i, 1); tableInst?.showColumn(field); }
                localStorage.setItem(LS_COLS, JSON.stringify(this.hiddenCols));
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                this.filters.search = p.get('q') ?? '';
                this.filters.link = p.get('lk') ?? '';
                this.filters.status = p.get('st') ?? '';
                this.filters.dateFrom = p.get('from') ?? '';
                this.filters.dateTo = p.get('to') ?? '';
                this.filters.sync = p.get('sy') ?? '';
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.link) p.set('lk', f.link);
                if (f.status) p.set('st', f.status);
                if (f.dateFrom) p.set('from', f.dateFrom);
                if (f.dateTo) p.set('to', f.dateTo);
                if (f.sync) p.set('sy', f.sync);
                const qs = p.toString();
                history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
            },

            refresh() { tableInst?.setPage(1); },

            onFilterChange() { this.saveState(); this.refresh(); },

            clearSearch() { this.filters.search = ''; this.onFilterChange(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'link') { this.filters.link = ''; tsLink?.setValue('', true); }
                if (key === 'status') { this.filters.status = ''; tsStatus?.setValue('', true); }
                if (key === 'sync') { this.filters.sync = ''; tsSync?.setValue('', true); }
                if (key === 'date') this._clearDates();
                this.onFilterChange();
            },

            reset() {
                this.filters = { search: '', link: '', status: '', dateFrom: '', dateTo: '', sync: '' };
                this._clearDates();
                tsSync?.setValue('', true);
                tsLink?.setValue('', true);
                tsStatus?.setValue('', true);
                this.onFilterChange();
            },
        };
    });

    Alpine.data('hanoiCheckLinkModal', (cfg = {}) => ({
        open: false,
        saving: false,
        row: null,
        form: { code: '', note: '' },
        error: '',
        live: !!cfg.live,
        remote: { loading: false, rows: [], message: '', date: '', keyword: '' },

        show(row) {
            this.row = row;
            this.form = { code: row.hanoicheck_order_code ?? '', note: row.note ?? '' };
            this.error = '';
            this.remote = { loading: false, rows: [], message: '', date: row.delivery_date_iso ?? '', keyword: '' };
            this.open = true;
            this.$nextTick(() => this.$refs.code?.focus());
            if (this.live) this.searchRemote();
        },

        async searchRemote() {
            this.remote.loading = true;
            this.remote.message = '';
            const p = new URLSearchParams();
            if (this.remote.date) { p.set('date_from', this.remote.date); p.set('date_to', this.remote.date); }
            if (this.remote.keyword) p.set('keyword', this.remote.keyword);
            try {
                const res = await fetch(cfg.remoteUrl + '?' + p.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json().catch(() => ({}));
                this.remote.rows = data.data ?? [];
                this.remote.message = data.message ?? (this.remote.rows.length === 0 ? 'Không có đơn nào trên HanoiCheck khớp điều kiện.' : '');
            } catch (_) {
                this.remote.message = 'Mất kết nối, vui lòng thử lại.';
            } finally {
                this.remote.loading = false;
            }
        },

        pick(r) {
            if (r.linked_to && r.linked_to !== this.row?.misa_ref_id) return;
            this.form.code = r.code;
            if (!this.form.note && r.school_name) this.form.note = r.school_name;
        },

        get unlinking() {
            return !!this.row?.hanoicheck_order_code && this.form.code.trim() === '';
        },

        async save() {
            if (!this.row || this.saving) return;
            if (this.unlinking && !confirm('Gỡ liên kết mã HanoiCheck của đơn ' + this.row.misa_ref_id + '?')) return;
            this.saving = true;
            this.error = '';
            try {
                const res = await fetch(this.row.link_url, {
                    method: 'PUT',
                    headers: headers(),
                    body: JSON.stringify({ hanoicheck_order_code: this.form.code.trim() || null, note: this.form.note.trim() || null }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.error = data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Không lưu được, vui lòng thử lại.');
                    return;
                }
                const link = data.link;
                window.dispatchEvent(new CustomEvent('hanoicheck-linked', {
                    detail: {
                        id: this.row.id,
                        patch: {
                            hanoicheck_order_code: link?.hanoicheck_order_code ?? null,
                            note: link?.note ?? null,
                            linked_at: link?.linked_at ?? null,
                            linked_by: link?.linked_by ?? null,
                            sync_status: link?.sync_status ?? null,
                            sync_status_label: link?.sync_status_label ?? null,
                            sync_status_badge: link?.sync_status_badge ?? null,
                            sync_message: null,
                            remote_status: null,
                            remote_status_label: null,
                            school_name: null,
                        },
                    },
                }));
                window.Toast?.success?.(data.message, { duration: 4000 });
                this.open = false;
            } catch (_) {
                this.error = 'Mất kết nối, vui lòng thử lại.';
            } finally {
                this.saving = false;
            }
        },
    }));

    Alpine.data('hanoiCheckDetailModal', () => ({
        open: false,
        loading: false,
        saving: false,
        row: null,
        data: null,
        error: '',
        form: { transporter_id: '', remove: [], files: [] },

        async show(row) {
            this.row = row;
            this.open = true;
            await this.load();
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch(this.row.detail_url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error();
                this.data = await res.json();
                this.form = { transporter_id: this.data.link.transporter_id ?? '', remove: [], files: [] };
                if (this.$refs.photos) this.$refs.photos.value = '';
            } catch (_) {
                this.error = 'Không tải được thông tin đơn.';
            } finally {
                this.loading = false;
            }
        },

        get keptPhotos() {
            return (this.data?.link.photos ?? []).filter(p => !this.form.remove.includes(String(p.id)));
        },

        get photoSlots() {
            return 3 - this.keptPhotos.length;
        },

        onFiles(e) {
            this.form.files = Array.from(e.target.files || []);
            if (this.form.files.length > this.photoSlots) {
                this.error = 'Chỉ còn ' + this.photoSlots + ' chỗ cho ảnh tổng quan (tối đa 3 ảnh).';
            } else {
                this.error = '';
            }
        },

        toggleRemove(id) {
            id = String(id);
            const i = this.form.remove.indexOf(id);
            i === -1 ? this.form.remove.push(id) : this.form.remove.splice(i, 1);
        },

        async save() {
            if (this.saving || !this.data) return;
            if (this.form.files.length > this.photoSlots) return;
            this.saving = true;
            this.error = '';
            const fd = new FormData();
            fd.append('transporter_id', this.form.transporter_id || '');
            this.form.remove.forEach(id => fd.append('remove_photo_ids[]', id));
            this.form.files.forEach(f => fd.append('photos[]', f));
            try {
                const h = headers();
                delete h['Content-Type'];
                const res = await fetch(this.data.delivery_url, { method: 'POST', headers: h, body: fd });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.error = json.errors ? Object.values(json.errors).flat()[0] : (json.message || 'Không lưu được.');
                    return;
                }
                window.Toast?.success?.(json.message, { duration: 4000 });
                await this.load();
                window.dispatchEvent(new CustomEvent('hanoicheck-refresh'));
            } catch (_) {
                this.error = 'Mất kết nối, vui lòng thử lại.';
            } finally {
                this.saving = false;
            }
        },

        async syncNow() {
            if (!this.data) return;
            this.saving = true;
            try {
                const res = await fetch(this.data.sync_url, { method: 'POST', headers: headers(), body: '{}' });
                const json = await res.json().catch(() => ({}));
                (res.ok ? window.Toast?.success : window.Toast?.error)?.(json.message, { duration: 4000 });
                setTimeout(() => { this.load(); window.dispatchEvent(new CustomEvent('hanoicheck-refresh')); }, 4000);
            } finally {
                this.saving = false;
            }
        },
    }));

    Alpine.data('hanoiCheckTransporterPage', (cfg = {}) => ({
        rows: [],
        loading: false,
        open: false,
        saving: false,
        error: '',
        editing: null,
        form: {},

        init() { this.load(); },

        async load() {
            this.loading = true;
            try {
                const res = await fetch(cfg.listUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                this.rows = (await res.json()).data ?? [];
            } finally {
                this.loading = false;
            }
        },

        create() { this.edit(null); },

        edit(row) {
            this.editing = row;
            this.form = {
                code: row?.code ?? '', name: row?.name ?? '', phone: row?.phone ?? '', cccd: row?.cccd ?? '',
                date_of_birth: row?.date_of_birth ?? '', address: row?.address ?? '', transport_mean: row?.transport_mean ?? '',
                license_plate: row?.license_plate ?? '', is_active: row ? !!row.is_active : true,
            };
            this.error = '';
            this.open = true;
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            this.error = '';
            try {
                const res = await fetch(this.editing ? this.editing.update_url : cfg.storeUrl, {
                    method: this.editing ? 'PUT' : 'POST', headers: headers(), body: JSON.stringify(this.form),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.error = json.errors ? Object.values(json.errors).flat()[0] : (json.message || 'Không lưu được.');
                    return;
                }
                window.Toast?.success?.(json.message, { duration: 4000 });
                this.open = false;
                await this.load();
            } finally {
                this.saving = false;
            }
        },
    }));
});
