import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

const COLUMNS = [
    { title: 'STT', field: 'line_no', width: 70, hozAlign: 'center', headerSort: false },
    { title: 'Tên hàng', field: 'name', minWidth: 240, sorter: 'string' },
    {
        title: 'Mã hàng', field: 'sku', width: 140, sorter: 'string',
        formatter: (cell) => '<span class="font-mono text-xs">' + esc(cell.getValue()) + '</span>',
    },
    { title: 'ĐVT', field: 'unit', width: 90, headerSort: false },
    {
        title: 'SL yêu cầu', field: 'requested_qty', width: 150, hozAlign: 'right', headerSort: false,
        formatter: (cell) => '<span class="font-mono">' + esc(cell.getValue()) + '</span>',
    },
    {
        title: 'Đã in tem (kg)', field: 'printed_qty', width: 150, hozAlign: 'right', headerSort: false,
        formatter: (cell) => {
            const d = cell.getRow().getData();
            const num = '<span class="font-mono">' + esc(cell.getValue()) + '</span>';
            if (!(d.printed_qty_raw > 0)) return num;
            return '<button type="button" class="inline-flex items-center gap-1 text-primary hover:underline" title="Xem lịch sử in / In lại">'
                + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3M3.05 11a9 9 0 11.5 4M3 4v5h5"/></svg>'
                + num + '</button>';
        },
        cellClick(_e, cell) {
            const row = cell.getRow().getData();
            if (row.printed_qty_raw > 0) window.dispatchEvent(new CustomEvent('open-print-history', { detail: row }));
        },
    },
    {
        title: 'Thực xuất', field: 'actual_qty', width: 150, hozAlign: 'right', headerSort: false,
        cssClass: 'bg-primary/5', // highlight sẵn — sau này đổi thành editor nhập liệu cho kho
        formatter: (cell) => cell.getValue() != null
            ? '<span class="font-mono">' + esc(cell.getValue()) + '</span>' : EMPTY,
    },
    {
        title: 'Thao tác', field: 'print_url', width: 110, hozAlign: 'center', headerSort: false, frozen: true,
        formatter: (cell) => cell.getValue()
            ? '<button type="button" class="btn btn-outline btn-primary btn-xs gap-1">'
                + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>In Tem</button>'
            : '',
        cellClick(_e, cell) {
            const row = cell.getRow().getData();
            if (row.print_url) window.dispatchEvent(new CustomEvent('open-print-label', { detail: row }));
        },
    },
];

// ── Modal "Cấu hình In Tem" ──────────────────────────────────────────
const pad = (n) => String(n).padStart(2, '0');
const toYmd = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
const fromYmd = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
// 'Y-m-d' → 'ddMMyy' (dùng để ráp Mã lô LOT-[NSX]-[HSD]).
const toDdMmYy = (ymd) => {
    if (!ymd) return '';
    const [y, m, d] = ymd.split('-');
    return d + m + y.slice(2);
};

document.addEventListener('alpine:init', () => {
    Alpine.data('printLabelModal', () => {
        let mfgPicker = null;
        let expPicker = null;
        let templateTs = null;
        let supplierTs = null;

        return {
            open: false,
            item: null,
            submitting: false,
            message: '',
            blockedUrl: '',
            errors: {},
            remaining: 0,
            attributes: [],
            loadingAttributes: false,
            _uid: 0,
            form: { weight: '', count: 1, mfg: '', exp: '', overrideLock: false, supplierManual: false, supplierText: '', batchCode: '', template: '' },

            get canSubmit() {
                return !this.submitting
                    && (this.remaining > 0 || this.form.overrideLock)
                    && Number(this.form.weight) > 0
                    && Number(this.form.count) >= 1
                    && !!this.form.exp;
            },

            init() {
                this.$nextTick(() => {
                    // Select tìm kiếm được (Tom Select) — dùng được khi có hàng chục mẫu tem.
                    const tplEl = document.getElementById('ts-label-template');
                    if (tplEl && !tplEl.tomselect) {
                        templateTs = createTs(tplEl, {
                            placeholder: '— Mặc định: Tem Rau Củ Quả (60x40) —',
                            maxOptions: null,
                            // Gắn danh sách vào <body> (z-index 9999 > modal 999): .modal-box của DaisyUI có transform +
                            // overflow nên gắn vào đó sẽ làm danh sách bị lệch xuống đáy modal.
                            dropdownParent: 'body',
                            onChange: (value) => { this.form.template = value || ''; },
                        });
                    }

                    // Select tìm kiếm được cho Nguồn cung — lấy nhãn hiển thị trực tiếp từ option đã chọn.
                    const supplierEl = document.getElementById('ts-supplier');
                    if (supplierEl && !supplierEl.tomselect) {
                        supplierTs = createTs(supplierEl, {
                            placeholder: '— Chọn nhà cung cấp —',
                            maxOptions: null,
                            dropdownParent: 'body',
                            onChange: (value) => {
                                this.form.supplierText = value ? (supplierTs.options[value]?.text ?? '') : '';
                            },
                        });
                    }

                    if (!window.initDatePicker) return;
                    const base = { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: true, disableMobile: true, static: true };

                    mfgPicker = window.initDatePicker('#fp-label-mfg', {
                        ...base,
                        onChange: (_s, dateStr) => { this.form.mfg = dateStr; this.recalcExp(); this.recalcBatchCode(); },
                    });
                    expPicker = window.initDatePicker('#fp-label-exp', {
                        ...base,
                        onChange: (_s, dateStr) => { this.form.exp = dateStr; this.recalcBatchCode(); },
                    });
                });
            },

            // HSD = NSX + shelf_life_days (chỉ khi sản phẩm có cấu hình)
            recalcExp() {
                const days = Number(this.item?.shelf_life_days) || 0;
                if (!days || !this.form.mfg) return;
                const d = fromYmd(this.form.mfg);
                d.setDate(d.getDate() + days);
                this.form.exp = toYmd(d);
                expPicker?.setDate(this.form.exp, false);
                this.recalcBatchCode();
            },

            // Mã lô = LOT-[NSX ddMMyy]-[HSD ddMMyy], cập nhật real-time mỗi khi NSX/HSD đổi.
            recalcBatchCode() {
                const mfg = toDdMmYy(this.form.mfg);
                const exp = toDdMmYy(this.form.exp);
                this.form.batchCode = (mfg && exp) ? `LOT-${mfg}-${exp}` : '';
            },

            // Bật/tắt "Khác / Nhập tay": đổi chế độ thì xoá lựa chọn của chế độ kia để tránh lẫn dữ liệu.
            onSupplierManualToggle() {
                supplierTs?.clear(true);
                this.form.supplierText = '';
            },

            openFor(row) {
                this.item = row;
                this.errors = {};
                this.message = '';
                this.blockedUrl = '';
                // Khối lượng/tem khoá cứng theo SL yêu cầu của đơn — không bao giờ đổi theo phần còn lại đã in.
                const remaining = Math.round((Number(row.requested_qty_raw) - Number(row.printed_qty_raw)) * 1000) / 1000;
                this.remaining = Math.max(remaining, 0);
                this.form = {
                    weight: Math.round(Number(row.requested_qty_raw) * 1000) / 1000,
                    count: 1,
                    mfg: toYmd(new Date()),
                    exp: '',
                    overrideLock: false,
                    supplierManual: false,
                    supplierText: '',
                    batchCode: '',
                    template: row.label_template_id || '',
                };
                templateTs?.setValue(this.form.template, true);
                supplierTs?.clear(true);
                mfgPicker?.setDate(this.form.mfg, false);
                expPicker?.clear(false);
                this.recalcExp();
                this.recalcBatchCode();
                this.open = true;
                this.loadBatchAttributes(row);
                this.$nextTick(() => this.$refs.weight?.focus());
            },

            close() { this.open = false; },

            addAttribute(key = '', value = '') {
                this.attributes.push({ uid: ++this._uid, key, value });
            },

            removeAttribute(index) {
                this.attributes.splice(index, 1);
            },

            // Điền sẵn thông tin bổ sung (EAV) của lô hàng tương ứng; nhân viên có thể sửa/xóa/thêm trước khi in.
            async loadBatchAttributes(row) {
                this.attributes = [];
                this.loadingAttributes = true;
                try {
                    const res = await fetch(row.attributes_url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    // Bỏ kết quả nếu modal đã chuyển sang dòng hàng khác trong lúc chờ.
                    if (this.item?.id !== row.id) return;
                    (data.attributes ?? []).forEach((a) => this.addAttribute(a.key, a.value));
                } catch (e) {
                    console.error('[print-label] load attributes failed', e);
                } finally {
                    this.loadingAttributes = false;
                }
            },

            async submit() {
                if (!this.canSubmit) return;

                // Mở tab ngay trong sự kiện click để không bị trình duyệt chặn popup.
                const win = window.open('', '_blank');
                this.submitting = true;
                this.errors = {};
                this.message = '';
                this.blockedUrl = '';

                try {
                    const res = await fetch(this.item.print_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            weight_per_label: this.form.weight,
                            label_count: this.form.count,
                            mfg_date: this.form.mfg || null,
                            exp_date: this.form.exp,
                            supplier_name: this.form.supplierText.trim() || null,
                            batch_code: this.form.batchCode || null,
                            label_template_id: this.form.template || null,
                            extra_attributes: this.attributes
                                .filter((a) => a.key.trim() !== '' || a.value.trim() !== '')
                                .map((a) => ({ key: a.key.trim(), value: a.value.trim() })),
                        }),
                    });
                    const data = await res.json().catch(() => ({}));

                    if (res.status === 422) {
                        win?.close();
                        Object.entries(data.errors ?? {}).forEach(([k, v]) => {
                            // extra_attributes.2.key → gom về một thông báo chung của khu vực thông tin bổ sung
                            const field = k.startsWith('extra_attributes') ? 'extra_attributes' : k;
                            this.errors[field] ??= v[0];
                        });
                        return;
                    }
                    if (!res.ok) {
                        win?.close();
                        this.message = data.message || 'In tem thất bại. Vui lòng thử lại.';
                        return;
                    }

                    window.salesOrderItemsTable?.updateData([{
                        id: this.item.id, printed_qty: data.printed_qty, printed_qty_raw: data.printed_qty_raw,
                    }]);

                    if (win) {
                        win.location = data.print_url;
                        this.close();
                    } else {
                        this.blockedUrl = data.print_url;
                    }
                } catch (e) {
                    console.error('[print-label] failed', e);
                    win?.close();
                    this.message = 'Lỗi kết nối. Vui lòng thử lại.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    });
});

// ── Modal "Lịch sử in tem" + In lại ───────────────────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.data('printHistoryModal', () => ({
        open: false,
        item: null,
        logs: [],
        loading: false,
        error: '',

        async openFor(row) {
            this.item = row;
            this.logs = [];
            this.error = '';
            this.loading = true;
            this.open = true;

            try {
                const res = await fetch(row.history_url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.logs = (await res.json()).data ?? [];
            } catch (e) {
                console.error('[print-history] failed', e);
                this.error = 'Không tải được lịch sử in. Vui lòng thử lại.';
            } finally {
                this.loading = false;
            }
        },

        close() { this.open = false; },

        // Chỉ mở lại tem đã có — server không ghi log mới và không cộng dồn printed_qty.
        reprint(log) {
            if (log.reprint_url) window.open(log.reprint_url, '_blank');
        },
    }));

    // ── In tem toàn bộ đơn (Bulk Print) ──────────────────────────────
    Alpine.data('bulkPrintOrder', ({ url }) => ({
        confirming: false,
        submitting: false,
        message: '',
        ok: true,
        preview: { total: 0, printable: 0, manual: 0 },

        get alertClass() { return this.ok ? 'alert-success' : 'alert-warning'; },

        openConfirm() {
            const rows = window.salesOrderItemsTable?.getData() ?? [];
            const pending = rows.filter(r => Math.round((r.requested_qty_raw - r.printed_qty_raw) * 1000) > 0);
            const printable = pending.filter(r => Number(r.shelf_life_days) > 0).length;
            this.preview = { total: pending.length, printable, manual: pending.length - printable };
            this.confirming = true;
        },

        async run() {
            if (this.submitting || this.preview.printable === 0) return;

            // Mở tab ngay trong sự kiện click để không bị trình duyệt chặn popup.
            const win = window.open('', '_blank');
            this.submitting = true;

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);

                window.salesOrderItemsTable?.updateData(data.items ?? []);
                this.confirming = false;
                this.ok = data.manual === 0;
                this.message = data.message;

                if (data.url && win) {
                    win.location = data.url;
                } else {
                    win?.close();
                    if (data.url) this.message += ' Trình duyệt đã chặn cửa sổ in — cho phép popup rồi bấm In lại từ lịch sử.';
                }
            } catch (e) {
                console.error('[bulk-print] failed', e);
                win?.close();
                this.confirming = false;
                this.ok = false;
                this.message = 'In tem hàng loạt thất bại: ' + e.message;
            } finally {
                this.submitting = false;
            }
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('sales-order-items-table');
    if (!el || !window.initTabulator) return;

    const rows = JSON.parse(el.dataset.rows || '[]');

    window.salesOrderItemsTable = window.initTabulator('#sales-order-items-table', COLUMNS, rows, {
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100],
        paginationCounter: 'rows',
        layout: 'fitColumns',
        movableColumns: false,
        placeholder: '<div class="py-10 text-center text-sm text-base-content/40">Đơn này chưa có dòng hàng nào.</div>',
    });
});
