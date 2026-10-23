import { createTs } from '@shared/tom-select-factory.js';
import { printSourceCascade, printSourcePayload, printSourceState } from '@shared/print-source-cascade.js';

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
    {
        title: 'Ngành hàng', field: 'category_name', minWidth: 140, headerSort: false,
        formatter: (cell) => cell.getValue() ? esc(cell.getValue()) : '<span class="text-base-content/25 text-xs">—</span>',
    },
    { title: 'ĐVT', field: 'unit', width: 90, headerSort: false },
    {
        title: 'Số lượng', field: 'requested_qty', width: 150, hozAlign: 'right', headerSort: false,
        formatter: (cell) => '<span class="font-mono">' + esc(cell.getValue()) + '</span>',
    },
    {
        title: 'Thực xuất', field: 'actual_qty', width: 150, hozAlign: 'right', headerSort: false,
        cssClass: 'bg-primary/5', // highlight sẵn — sau này đổi thành editor nhập liệu cho kho
        formatter: (cell) => cell.getValue() != null
            ? '<span class="font-mono">' + esc(cell.getValue()) + '</span>' : EMPTY,
    },
    {
        title: 'QC trước xuất', field: 'qc', width: 140, hozAlign: 'center', headerSort: false,
        formatter: (cell) => {
            const q = cell.getValue();
            return q
                ? '<span class="badge badge-sm ' + (q.result === 'pass' ? 'badge-success' : 'badge-error') + '" title="' + esc(q.at) + '">' + esc(q.label) + '</span>'
                : EMPTY;
        },
    },
    {
        title: 'Thao tác', field: 'print_url', width: 240, hozAlign: 'center', headerSort: false, frozen: true,
        formatter: (cell) => {
            const d = cell.getRow().getData();
            return '<div class="flex items-center justify-center gap-1">'
                + (d.print_url
                    ? '<button type="button" data-action="print" data-item-id="' + esc(d.id) + '" class="btn btn-outline btn-primary btn-xs gap-1">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>In Tem</button>'
                    : '')
                + (d.qc_url
                    ? '<button type="button" data-action="qc" data-item-id="' + esc(d.id) + '" class="btn btn-ghost btn-xs" title="Ghi QC trước xuất">QC</button>'
                    : '')
                + '<button type="button" data-action="history" data-item-id="' + esc(d.id) + '" class="btn btn-ghost btn-xs gap-1" title="Lịch sử in / In lại">'
                + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3M3.05 11a9 9 0 11.5 4M3 4v5h5"/></svg>Lịch sử</button>'
                + '</div>';
        },
    },
];

// ── Modal "Cấu hình In Tem" ──────────────────────────────────────────
const pad = (n) => String(n).padStart(2, '0');
const toYmd = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
const fromYmd = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
// 'Y-m-d' → 'ddMMyy' (dùng để ráp Mã lô LOT-[NSX]-[HSD]).
const fmtKg = (n) => (Math.round(Number(n) * 1000) / 1000).toLocaleString('vi-VN', { maximumFractionDigits: 3 });
const groupQty = (g) => (Number.isInteger(Number(g.qty)) ? Number(g.qty) : 0);
const splitKey = (groups) => {
    const m = {};
    (groups ?? []).forEach((g) => {
        const k = (Math.round((Number(g.weight) || 0) * 1000) / 1000).toFixed(3);
        m[k] = (m[k] ?? 0) + groupQty(g);
    });
    return Object.keys(m).sort().filter((k) => m[k] > 0).map((k) => k + 'x' + m[k]).join('|');
};
const splitMap = (groups) => {
    const m = {};
    (groups ?? []).forEach((g) => {
        const k = (Math.round((Number(g.weight) || 0) * 1000) / 1000).toFixed(3);
        m[k] = (m[k] ?? 0) + groupQty(g);
    });
    return m;
};
const appendCountOf = (printed, groups) => {
    const current = splitMap(printed);
    const target = splitMap(groups);
    if (Object.entries(current).some(([k, qty]) => (target[k] ?? 0) < qty)) return 0;
    return Object.entries(target).reduce((sum, [k, qty]) => sum + Math.max(0, qty - (current[k] ?? 0)), 0);
};
const groupsFrom = (printed, nextUid) => printed.map((g) => ({ uid: nextUid(), weight: g.weight, qty: g.qty }));
const groupsTotalOf = (groups) => groups.reduce((sum, g) => sum + Math.round((Number(g.weight) || 0) * 1000) * groupQty(g), 0) / 1000;
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
        let batchTs = null;
        let defaultTemplateId = '';

        return {
            open: false,
            item: null,
            submitting: false,
            message: '',
            blockedUrl: '',
            errors: {},
            attributes: [],
            loadingAttributes: false,
            _uid: 0,
            requiredTotal: 0,
            loadingBatches: false,
            batchCount: 0,
            batchVendors: {},
            form: { groups: [], printMode: 'append', mfg: '', exp: '', supplierManual: false, supplierText: '', vendorId: '', batchId: '', batchCode: '', template: '' },

            get canSubmit() {
                return !this.submitting
                    && this.groupsValid
                    && this.labelCount <= 200
                    && !(this.splitChanged && this.form.printMode === 'append' && this.appendCount === 0)
                    && !!this.form.exp;
            },

            fmtKg,

            get groupsValid() {
                return this.form.groups.length > 0 && this.form.groups.every((g) =>
                    Number(g.weight) > 0 && Number.isInteger(Number(g.qty)) && Number(g.qty) >= 1);
            },

            get labelCount() {
                return this.form.groups.reduce((sum, g) => sum + (Number.isInteger(Number(g.qty)) ? Number(g.qty) : 0), 0);
            },

            get groupsTotal() {
                const milli = this.form.groups.reduce((sum, g) =>
                    sum + Math.round((Number(g.weight) || 0) * 1000) * (Number.isInteger(Number(g.qty)) ? Number(g.qty) : 0), 0);
                return milli / 1000;
            },

            get groupsMatch() {
                return Math.round(this.groupsTotal * 1000) === Math.round(this.requiredTotal * 1000);
            },

            get printedGroups() {
                return this.item?.print_prefill?.printed_groups ?? [];
            },

            get splitChanged() {
                return this.printedGroups.length > 0 && splitKey(this.form.groups) !== splitKey(this.printedGroups);
            },

            get appendCount() {
                return appendCountOf(this.printedGroups, this.form.groups);
            },

            autoSplit() {
                this.form.groups = this.printedGroups.length > 0
                    ? groupsFrom(this.printedGroups, () => ++this._uid)
                    : [{ uid: ++this._uid, weight: this.requiredTotal > 0 ? this.requiredTotal : '', qty: 1 }];
            },

            addGroup() {
                this.form.groups.push({ uid: ++this._uid, weight: '', qty: 1 });
            },

            removeGroup(index) {
                if (this.form.groups.length > 1) this.form.groups.splice(index, 1);
            },

            init() {
                this.$nextTick(() => {
                    // Select tìm kiếm được (Tom Select) — dùng được khi có hàng chục mẫu tem.
                    const tplEl = document.getElementById('ts-label-template');
                    if (tplEl) defaultTemplateId = tplEl.dataset.defaultTemplateId || '';
                    if (tplEl && !tplEl.tomselect) {
                        templateTs = createTs(tplEl, {
                            placeholder: '— Mặc định: Mẫu tem truy xuất VISAFO - Cỡ lớn (Khổ giấy in nhãn 100x75mm) —',
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
                                this.form.vendorId = value || '';
                                this.form.supplierText = value ? (supplierTs.options[value]?.text ?? '') : '';
                            },
                        });
                    }

                    const batchEl = document.getElementById('ts-batch');
                    if (batchEl && !batchEl.tomselect) {
                        batchTs = createTs(batchEl, {
                            placeholder: '— Không chọn lô —',
                            maxOptions: null,
                            dropdownParent: 'body',
                            onChange: (value) => {
                                this.form.batchId = value || '';
                                const vendorId = this.batchVendors[value];
                                if (vendorId && supplierTs?.options[vendorId]) {
                                    this.form.supplierManual = false;
                                    supplierTs.setValue(vendorId);
                                }
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

            // HSD = NSX + shelf_life_days (nếu sản phẩm có cấu hình riêng), mặc định NSX + 2 ngày nếu chưa cấu hình.
            // Chỉ tính lại khi NSX đổi — người dùng vẫn sửa tay HSD sau đó mà không bị ghi đè.
            recalcExp() {
                if (!this.form.mfg) return;
                const days = Number(this.item?.shelf_life_days) || 2;
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
                this.form.vendorId = '';
            },

            async loadBatches(row) {
                batchTs?.clear(true);
                batchTs?.clearOptions();
                this.batchVendors = {};
                this.batchCount = 0;
                if (!row.batches_url) return;
                this.loadingBatches = true;
                try {
                    const res = await fetch(row.batches_url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (this.item?.id !== row.id) return;
                    (data.data ?? []).forEach((b) => {
                        batchTs?.addOption({ value: b.value, text: b.text });
                        this.batchVendors[b.value] = b.vendor_id;
                    });
                    this.batchCount = (data.data ?? []).length;
                    batchTs?.refreshOptions(false);
                } catch (e) {
                    console.error('[print-label] load batches failed', e);
                } finally {
                    this.loadingBatches = false;
                }
            },

            openFor(row) {
                this.item = row;
                this.errors = {};
                this.message = '';
                this.blockedUrl = '';
                this.requiredTotal = Math.round(Number(row.requested_qty_raw) * 1000) / 1000;
                this.form = {
                    groups: [],
                    printMode: 'append',
                    mfg: toYmd(new Date()),
                    exp: '',
                    supplierManual: false,
                    supplierText: '',
                    vendorId: '',
                    batchId: '',
                    batchCode: '',
                    // Ưu tiên mẫu tem gán riêng cho sản phẩm; nếu chưa có thì dùng mẫu mặc định của đơn.
                    template: row.label_template_id || defaultTemplateId || '',
                };
                templateTs?.setValue(this.form.template, true);
                supplierTs?.clear(true);
                mfgPicker?.setDate(this.form.mfg, false);
                expPicker?.clear(false);
                this.autoSplit();
                this.recalcExp();
                this.recalcBatchCode();
                this.open = true;
                this.loadBatchAttributes(row);
                this.loadBatches(row);
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
                            label_groups: this.form.groups.map((g) => ({
                                weight_per_label: Number(g.weight),
                                label_count: Number(g.qty),
                            })),
                            print_mode: this.splitChanged ? this.form.printMode : null,
                            mfg_date: this.form.mfg || null,
                            exp_date: this.form.exp,
                            supplier_name: this.form.supplierText.trim() || null,
                            vendor_id: this.form.supplierManual ? null : (this.form.vendorId || null),
                            product_batch_id: this.form.batchId || null,
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
                            const field = k.startsWith('extra_attributes') ? 'extra_attributes'
                                : (k.startsWith('label_groups') ? 'label_groups' : k);
                            this.errors[field] ??= v[0];
                        });
                        return;
                    }
                    if (!res.ok) {
                        win?.close();
                        this.message = data.message || 'In tem thất bại. Vui lòng thử lại.';
                        return;
                    }

                    if (!data.reused && this.item) {
                        this.item.print_prefill = {
                            ...(this.item.print_prefill ?? {}),
                            printed: data.appended ? (this.item.print_prefill?.printed ?? 0) + data.label_count : data.label_count,
                            printed_groups: this.form.groups.map((g) => ({ weight: Number(g.weight), qty: groupQty(g) })),
                        };
                    }

                    if (win) {
                        win.location = data.print_url;
                        // In lại mã cũ (Check & Reuse) → giữ modal để báo rõ dữ liệu vừa nhập không được dùng
                        if (data.message) this.message = data.message;
                        else this.close();
                    } else {
                        this.blockedUrl = data.print_url;
                        if (data.message) this.message = data.message;
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
        reprinting: false,
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

        async reprint(log) {
            if (!log.reprint_url || this.reprinting) return;
            const win = window.open('', '_blank');
            this.reprinting = true;
            try {
                const res = await fetch(log.reprint_url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);
                if (win) win.location = data.print_url;
                else window.open(data.print_url, '_blank');
                await this.openFor(this.item);
            } catch (e) {
                console.error('[print-history] reprint failed', e);
                win?.close();
                this.error = 'In lại thất bại: ' + e.message;
            } finally {
                this.reprinting = false;
            }
        },
    }));

    // ── In tem toàn bộ đơn (Bulk Print): lô xuất kho / nguồn cung điền sẵn theo từng mặt hàng ──
    Alpine.data('bulkPrintOrder', ({ url, defaultTemplateId = '', vendors = [] }) => {
        let templateTs = null;
        let globalTs = null;
        const cascade = printSourceCascade({
            vendors,
            logTag: 'bulk-print',
            onUserChange(row, kind) {
                row.dirty = true;
                if (kind === 'text') return;
                row.manual = true;
                if (kind !== 'partner') row.viaGlobal = false;
            },
        });
        let mfgPicker = null;
        let expPicker = null;
        const draftKey = 'bulk-print-draft:' + url;
        let draft = {};
        try { draft = JSON.parse(localStorage.getItem(draftKey) || '{}') || {}; } catch { draft = {}; }

        const saveDraft = (items) => {
            items.forEach((row) => {
                draft[row.id] = {
                    batchId: row.batchId, batchText: row.batchText, vendorId: row.vendorId, vendorName: row.vendorName, partnerProductId: row.partnerProductId,
                    supplierManual: row.supplierManual, supplierText: row.supplierText, dirty: row.dirty, viaGlobal: row.viaGlobal, manual: row.manual,
                    groups: row.groups.map((g) => ({ weight: g.weight, qty: g.qty })), total: row.total,
                };
            });
            try { localStorage.setItem(draftKey, JSON.stringify(draft)); } catch {}
        };

        const clearDraft = () => {
            draft = {};
            try { localStorage.removeItem(draftKey); } catch {}
        };

        return {
            confirming: false,
            submitting: false,
            message: '',
            ok: true,
            errors: {},
            preview: { total: 0 },
            items: [],
            _uid: 0,
            form: { template: '', mfg: '', exp: '', batchCode: '' },
            globalVendorId: '',

            fmtKg,
            ...cascade,

            get introText() {
                return `Lô xuất kho và nguồn cung của ${this.preview.total} mặt hàng đã được điền sẵn (theo lần in trước, hoặc lô còn tồn cũ nhất). Kiểm tra lại rồi bấm "In tem"; bấm biểu tượng bút nếu cần đổi lô hoặc cách chia tem.`;
            },

            get globalAppliedCount() {
                return this.items.filter((row) => row.viaGlobal).length;
            },

            sourceChanged(row) {
                const p = row.printedSource;
                if (!p || row.printed === 0) return false;
                const vendorId = row.supplierManual ? '' : row.vendorId;
                return (row.batchId || '') !== p.batchId || vendorId !== p.vendorId
                    || (vendorId ? row.partnerProductId || '' : '') !== p.partnerProductId
                    || (!vendorId && (row.supplierManual ? row.supplierText.trim() : '') !== p.supplierText);
            },

            applyGlobalVendor(value) {
                const name = value ? (vendors.find((v) => v.value === value)?.text ?? '') : '';
                this.globalVendorId = value || '';
                this.items.forEach((row) => {
                    if (row.manual) return;
                    if (value) {
                        if (!row.viaGlobal) {
                            row.beforeGlobal = { batchId: row.batchId, batchText: row.batchText, vendorId: row.vendorId, vendorName: row.vendorName, partnerProductId: row.partnerProductId, supplierManual: row.supplierManual, supplierText: row.supplierText };
                        }
                        const partnerProductId = row.vendorId === value ? row.partnerProductId : '';
                        Object.assign(row, { batchId: '', batchText: '', vendorId: value, vendorName: name, partnerProductId, supplierManual: false, supplierText: '', viaGlobal: true, dirty: true });
                    } else if (row.viaGlobal) {
                        Object.assign(row, row.beforeGlobal ?? { batchId: '', batchText: '', vendorId: '', vendorName: '', partnerProductId: '', supplierManual: false, supplierText: '' }, { viaGlobal: false });
                    }
                    this.syncRowSupplier(row);
                    this.syncRowBatch(row);
                    if (row.editing) this.loadRowPartners(row);
                });
            },

            batchCodeOf(row) {
                return (row.batchText || '').split(' · ')[0];
            },


            get totalLabels() {
                return this.items.reduce((sum, row) => sum + row.groups.reduce((s, g) => s + groupQty(g), 0), 0);
            },

            // Nguồn của dòng đúng như trên màn hình: lô, NCC hoặc tên nhập tay — server không tự gán mặc định; thiếu nguồn vẫn in được (chỉ cảnh báo).
            rowHasSource(row) {
                return !!row.batchId || (row.supplierManual ? !!row.supplierText.trim() : !!row.vendorId);
            },

            get missingSourceCount() {
                return this.items.filter((row) => !this.rowHasSource(row)).length;
            },

            get canRun() {
                return !this.submitting && this.items.length > 0 && !!this.form.exp && !!this.form.template
                    && this.totalLabels <= 2000
                    && this.items.every((row) => row.groups.length > 0 && row.groups.every((g) => this.groupValid(g)))
                    && !this.items.some((row) => this.splitChanged(row) && row.printMode === 'append' && this.appendCount(row) === 0);
            },

            groupValid(g) {
                return Number(g.weight) > 0 && Number.isInteger(Number(g.qty)) && Number(g.qty) >= 1 && Number(g.qty) <= 200;
            },

            rowTotal(row) {
                return groupsTotalOf(row.groups);
            },

            rowMatch(row) {
                return Math.round(this.rowTotal(row) * 1000) === Math.round(row.total * 1000);
            },

            autoSplit(row) {
                row.groups = row.printedGroups?.length > 0
                    ? groupsFrom(row.printedGroups, () => ++this._uid)
                    : [{ uid: ++this._uid, weight: row.total, qty: 1 }];
            },

            splitChanged(row) {
                return row.printed > 0 && row.printedGroups?.length > 0 && splitKey(row.groups) !== splitKey(row.printedGroups);
            },

            appendCount(row) {
                return appendCountOf(row.printedGroups, row.groups);
            },

            rowLabelCount(row) {
                return row.groups.reduce((s, g) => s + groupQty(g), 0);
            },

            addGroup(row) {
                row.groups.push({ uid: ++this._uid, weight: '', qty: 1 });
            },

            removeGroup(row, index) {
                if (row.groups.length > 1) row.groups.splice(index, 1);
            },

            toggleRow(row) {
                row.editing = !row.editing;
                if (row.editing) {
                    this.loadRowBatches(row);
                    this.loadRowPartners(row);
                }
            },


            buildItems(rows) {
                const prev = new Map(this.items.map((row) => [row.id, row]));
                this.items = rows.map((r) => {
                    const total = Math.round(Number(r.requested_qty_raw) * 1000) / 1000;
                    const old = prev.get(r.id);
                    if (old && old.total === total) {
                        old.editing = false;
                        return old;
                    }

                    const pf = r.print_prefill ?? {};
                    // Bản nháp chỉ giữ nguồn cung khi người dùng đã tự chọn (bản nháp cũ có thể chứa gợi ý FIFO).
                    const d = draft[r.id];
                    const ds = d && (d.dirty || d.manual || d.viaGlobal) ? d : null;
                    const src = ds ?? {
                        batchId: pf.batch_id ?? '', batchText: pf.batch_text ?? '', vendorId: pf.vendor_id ?? '', vendorName: pf.vendor_name ?? '',
                        supplierManual: !pf.vendor_id && !!pf.supplier_name, supplierText: pf.supplier_name ?? '', dirty: false,
                    };
                    const row = printSourceState({
                        id: r.id,
                        name: r.product_name || r.name,
                        unit: r.unit,
                        total,
                        groups: [],
                        printMode: 'append',
                        editing: false,
                        batchesUrl: r.batches_url,
                        partnerProductsUrl: r.partner_products_url,
                        hasBatches: !!pf.has_batches,
                        printed: pf.printed ?? 0,
                        printedGroups: pf.printed_groups ?? [],
                        origin: pf.origin ?? null,
                        batchId: src.batchId || '',
                        batchText: src.batchText || '',
                        vendorId: src.vendorId || '',
                        vendorName: src.vendorName || '',
                        // Bản nháp cũ chưa có khóa này → lấy theo lần in trước nếu cùng NCC.
                        partnerProductId: (ds && 'partnerProductId' in ds ? ds.partnerProductId : ((src.vendorId || '') === (pf.vendor_id ?? '') ? pf.partner_product_id : '')) || '',
                        supplierManual: !!src.supplierManual,
                        supplierText: src.supplierText || '',
                        dirty: !!src.dirty,
                        viaGlobal: !!src.viaGlobal,
                        manual: !!src.manual,
                        beforeGlobal: null,
                        printedSource: pf.origin === 'history' && (pf.printed ?? 0) > 0
                            ? { batchId: pf.batch_id ?? '', vendorId: pf.vendor_id ?? '', partnerProductId: pf.vendor_id ? (pf.partner_product_id ?? '') : '', supplierText: pf.vendor_id ? '' : (pf.supplier_name ?? '') }
                            : null,
                    });
                    if (d?.groups?.length && d.total === total) {
                        row.groups = d.groups.map((g) => ({ uid: ++this._uid, weight: g.weight, qty: g.qty }));
                    } else {
                        this.autoSplit(row);
                    }
                    return row;
                }).filter((row) => row.total > 0);
                this.preview = { total: this.items.length };
            },

            get alertClass() { return this.ok ? 'alert-success' : 'alert-warning'; },

            init() {
                this.$watch('items', () => saveDraft(this.items), { deep: true });

                this.$nextTick(() => {
                    const tplEl = document.getElementById('bp-label-template');
                    if (tplEl && !tplEl.tomselect) {
                        templateTs = createTs(tplEl, {
                            placeholder: '— Chọn mẫu tem in —',
                            maxOptions: null,
                            dropdownParent: 'body',
                            onChange: (value) => { this.form.template = value || ''; },
                        });
                    }

                    const globalEl = document.getElementById('bp-supplier');
                    if (globalEl && !globalEl.tomselect) {
                        globalTs = createTs(globalEl, {
                            placeholder: '— Không áp dụng —',
                            maxOptions: null,
                            dropdownParent: 'body',
                            onChange: (value) => this.applyGlobalVendor(value),
                        });
                    }

                    if (!window.initDatePicker) return;
                    const base = { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: true, disableMobile: true, static: true };

                    mfgPicker = window.initDatePicker('#bp-mfg', {
                        ...base,
                        onChange: (_s, dateStr) => { this.form.mfg = dateStr; this.recalcExp(); this.recalcBatchCode(); },
                    });
                    expPicker = window.initDatePicker('#bp-exp', {
                        ...base,
                        onChange: (_s, dateStr) => { this.form.exp = dateStr; this.recalcBatchCode(); },
                    });
                });
            },

            // HSD = NSX + 2 ngày (mặc định chung cho cả đơn) — người dùng vẫn sửa tay sau đó được.
            recalcExp() {
                if (!this.form.mfg) return;
                const d = fromYmd(this.form.mfg);
                d.setDate(d.getDate() + 2);
                this.form.exp = toYmd(d);
                expPicker?.setDate(this.form.exp, false);
                this.recalcBatchCode();
            },

            recalcBatchCode() {
                const mfg = toDdMmYy(this.form.mfg);
                const exp = toDdMmYy(this.form.exp);
                this.form.batchCode = (mfg && exp) ? `LOT-${mfg}-${exp}` : '';
            },

            openConfirm() {
                const rows = window.salesOrderItemsTable?.getData() ?? [];
                this.errors = {};
                this.message = '';

                if (rows.length === 0) {
                    this.confirming = false;
                    this.ok = false;
                    this.message = 'Đơn hàng chưa có mặt hàng nào.';
                    return;
                }

                this.buildItems(rows);
                this.openForm();
            },

            openForm() {
                if (!this.form.mfg) {
                    this.form = { template: defaultTemplateId || '', mfg: toYmd(new Date()), exp: '', batchCode: '' };
                    templateTs?.setValue(this.form.template, true);
                    mfgPicker?.setDate(this.form.mfg, false);
                    expPicker?.clear(false);
                    this.recalcExp();
                }
                this.confirming = true;
            },

            async run() {
                if (!this.canRun) return;

                // Mở tab ngay trong sự kiện click để không bị trình duyệt chặn popup.
                const win = window.open('', '_blank');
                this.submitting = true;
                this.errors = {};

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            label_template_id: this.form.template || null,
                            mfg_date: this.form.mfg || null,
                            exp_date: this.form.exp,
                            batch_code: this.form.batchCode || null,
                            items: this.items.map((row) => ({
                                order_item_id: row.id,
                                ...printSourcePayload(row),
                                label_groups: row.groups.map((g) => ({ weight_per_label: Number(g.weight), label_count: Number(g.qty) })),
                                print_mode: this.splitChanged(row) ? row.printMode : null,
                            })),
                        }),
                    });
                    const data = await res.json().catch(() => ({}));

                    if (res.status === 422) {
                        win?.close();
                        Object.entries(data.errors ?? {}).forEach(([k, v]) => {
                            const field = k.startsWith('items') ? 'items' : k;
                            this.errors[field] ??= v[0];
                        });
                        return;
                    }
                    if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);

                    this.items.forEach((row) => {
                        if (row.printed === 0 || this.splitChanged(row)) {
                            row.printed = this.rowLabelCount(row);
                            row.printedGroups = row.groups.map((g) => ({ weight: Number(g.weight), qty: groupQty(g) }));
                        }
                        row.printedSource = { batchId: row.batchId || '', vendorId: row.supplierManual ? '' : row.vendorId, partnerProductId: row.supplierManual || !row.vendorId ? '' : (row.partnerProductId || ''), supplierText: row.supplierManual ? row.supplierText.trim() : '' };
                        row.origin = 'history';
                        row.dirty = false;
                        row.viaGlobal = false;
                        row.manual = false;
                    });
                    this.globalVendorId = '';
                    globalTs?.clear(true);
                    clearDraft();

                    this.confirming = false;
                    this.ok = true;
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
        };
    });
});

function openItemQcModal(row) {
    const form = document.getElementById('itemQcForm');
    if (!form) return;
    form.action = row.qc_url;
    form.reset();
    document.getElementById('itemQcModalName').textContent = row.product_name || row.name || '';
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('iqc-checked-at').value = now.toISOString().slice(0, 16);
    document.getElementById('itemQcModal').showModal();
}

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('sales-order-items-table');
    if (!el || !window.initTabulator) return;

    const rows = JSON.parse(el.dataset.rows || '[]');
    const rowsById = new Map(rows.map((r) => [String(r.id), r]));

    el.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action][data-item-id]');
        if (!btn || !el.contains(btn)) return;
        const row = rowsById.get(btn.dataset.itemId);
        if (!row) return;
        e.preventDefault();
        e.stopPropagation();
        if (btn.dataset.action === 'print' && row.print_url) window.dispatchEvent(new CustomEvent('open-print-label', { detail: row }));
        if (btn.dataset.action === 'qc' && row.qc_url) openItemQcModal(row);
        if (btn.dataset.action === 'history') window.dispatchEvent(new CustomEvent('open-print-history', { detail: row }));
    });

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
