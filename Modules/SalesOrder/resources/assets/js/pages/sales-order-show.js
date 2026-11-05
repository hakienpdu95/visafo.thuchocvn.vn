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
    {
        title: 'Tên hàng', field: 'name', minWidth: 240, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            cell.getElement().style.whiteSpace = 'normal';
            return esc(d.name) + (d.name_raw ? '<div class="text-xs text-warning mt-0.5" title="Tên trên phiếu khác tên sản phẩm trong danh mục — kiểm tra lại mã hàng">⚠ Tên trên phiếu: ' + esc(d.name_raw) + '</div>' : '');
        },
    },
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
    return Object.entries(target).reduce((sum, [k, qty]) => sum + Math.max(0, qty - (current[k] ?? 0)), 0);
};
const mergeSplitOf = (printed, groups) => {
    const m = splitMap(printed);
    Object.entries(splitMap(groups)).forEach(([k, qty]) => { m[k] = Math.max(m[k] ?? 0, qty); });
    return Object.keys(m).sort().filter((k) => m[k] > 0).map((k) => ({ weight: Number(k), qty: m[k] }));
};
const toMilli = (w) => Math.round((Number(w) || 0) * 1000);
const matchParts = (printed, groups) => {
    const current = splitMap(printed);
    return Object.entries(splitMap(groups))
        .map(([k, qty]) => ({ weight: Number(k), keep: Math.min(qty, current[k] ?? 0), add: Math.max(0, qty - (current[k] ?? 0)) }))
        .filter((p) => p.keep + p.add > 0);
};
const reuseCountOf = (printed, groups) => matchParts(printed, groups).reduce((s, p) => s + p.keep, 0);
const splitSummary = (printed, unit) => (printed ?? []).map((g) => fmtKg(g.weight) + ' ' + (unit || 'kg') + ' × ' + g.qty).join(', ');
const groupNoteOf = (printed, groups, group) => {
    if (!(printed?.length > 0) || !(Number(group.weight) > 0) || groupQty(group) < 1) return '';
    const part = matchParts(printed, groups).find((p) => toMilli(p.weight) === toMilli(group.weight));
    if (!part) return '';
    if (part.add === 0) return 'Mã cũ';
    return part.keep > 0 ? 'Dùng lại ' + part.keep + ' mã cũ, +' + part.add + ' mã mới' : '+' + part.add + ' mã mới';
};
const emitPrefill = (map) => window.dispatchEvent(new CustomEvent('sales-order-prefill', { detail: map }));
const prefillOfGroups = (groups) => ({
    printed: groups.reduce((s, g) => s + groupQty(g), 0),
    printed_groups: groups.map((g) => ({ weight: Number(g.weight), qty: groupQty(g) })),
});
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
        let partnerTs = null;
        let defaultTemplateId = '';
        let prefilling = false;

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
            batchPartners: {},
            batchInfo: {},
            partnerProducts: [],
            partnerVendorId: '',
            loadingPartners: false,
            sourceOrigin: null,
            form: { groups: [], partnerProductId: '', mfg: '', exp: '', supplierManual: false, supplierText: '', vendorId: '', batchId: '', batchCode: '', template: '' },

            get canSubmit() {
                return !this.submitting
                    && this.groupsValid
                    && this.labelCount <= 200
                    && !!this.form.exp
                    && !this.quarantineBlocked
                    && !this.datesMissing;
            },

            get quarantineBlocked() {
                return !!this.item?.requires_quarantine
                    && !(this.form.batchId && this.batchInfo[this.form.batchId]?.has_quarantine);
            },

            get quarantineMessage() {
                return this.form.batchId
                    ? 'Lô hàng chưa có Phiếu kiểm dịch Thú y. Bắt buộc bổ sung hồ sơ trước khi in tem xuất kho.'
                    : 'Mặt hàng thuộc nhóm Thịt / Thủy sản: bắt buộc chọn Lô nhập kho đã có Phiếu kiểm dịch Thú y trước khi in tem xuất kho.';
            },

            get datesMissing() {
                return !!this.item?.requires_dates && (!this.form.mfg || !this.form.exp);
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

            get appendCount() {
                return appendCountOf(this.printedGroups, this.form.groups);
            },

            get reuseCount() {
                return reuseCountOf(this.printedGroups, this.form.groups);
            },

            get printedSummary() {
                return splitSummary(this.printedGroups, this.item?.unit);
            },

            groupNote(group) {
                return groupNoteOf(this.printedGroups, this.form.groups, group);
            },

            applyPrefill(map) {
                const pf = this.item ? map?.[this.item.id] : null;
                if (pf) this.item.print_prefill = { ...(this.item.print_prefill ?? {}), ...pf };
            },

            autoSplit() {
                this.form.groups = [{ uid: ++this._uid, weight: this.requiredTotal > 0 ? this.requiredTotal : '', qty: 1 }];
            },

            addGroup() {
                this.form.groups.push({ uid: ++this._uid, weight: '', qty: 1 });
            },

            removeGroup(index) {
                this.form.groups.splice(index, 1);
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
                                if (!prefilling) this.sourceOrigin = null;
                                this.loadPartners();
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
                                this.applyBatchDates(value);
                                const vendorId = this.batchVendors[value];
                                if (vendorId && supplierTs?.options[vendorId]) {
                                    this.form.supplierManual = false;
                                    if (this.batchPartners[value]) this.form.partnerProductId = this.batchPartners[value];
                                    supplierTs.setValue(vendorId);
                                    this.syncPartner();
                                }
                            },
                        });
                    }

                    const partnerEl = document.getElementById('ts-partner');
                    if (partnerEl && !partnerEl.tomselect) {
                        partnerTs = createTs(partnerEl, {
                            placeholder: '— Chọn nguồn cung trước —',
                            maxOptions: null,
                            dropdownParent: 'body',
                            onChange: (value) => {
                                this.form.partnerProductId = value || '';
                                if (!prefilling) this.sourceOrigin = null;
                            },
                        });
                        partnerTs?.disable();
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
                if (!this.form.mfg || this.item?.requires_dates) return;
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

            applyBatchDates(batchId) {
                const info = this.batchInfo[batchId];
                if (!this.item?.requires_dates || !info) return;
                this.form.mfg = info.mfg_date || '';
                this.form.exp = info.exp_date || '';
                if (this.form.mfg) mfgPicker?.setDate(this.form.mfg, false); else mfgPicker?.clear(false);
                if (this.form.exp) expPicker?.setDate(this.form.exp, false); else expPicker?.clear(false);
                this.recalcBatchCode();
            },

            // Bật/tắt "Khác / Nhập tay": đổi chế độ thì xoá lựa chọn của chế độ kia để tránh lẫn dữ liệu.
            onSupplierManualToggle() {
                supplierTs?.clear(true);
                this.form.supplierText = '';
                this.form.vendorId = '';
                this.sourceOrigin = null;
                this.loadPartners();
            },

            syncPartner() {
                if (!partnerTs) return;
                const vendorId = this.form.supplierManual ? '' : this.form.vendorId;
                partnerTs.clear(true);
                partnerTs.clearOptions();
                partnerTs.addOptions(this.partnerProducts);
                partnerTs.settings.placeholder = !vendorId ? '— Chọn nguồn cung trước —'
                    : this.loadingPartners ? 'Đang tải…'
                    : this.partnerProducts.length === 0 ? 'NCC chưa kê khai hàng hóa này' : '— Không chọn —';
                partnerTs.control_input.setAttribute('placeholder', partnerTs.settings.placeholder);
                partnerTs.setValue(this.form.partnerProductId || '', true);
                (!vendorId || this.loadingPartners || this.partnerProducts.length === 0) ? partnerTs.disable() : partnerTs.enable();
            },

            async loadPartners() {
                const vendorId = this.form.supplierManual ? '' : this.form.vendorId;
                if (!vendorId || !this.item?.partner_products_url) {
                    this.partnerProducts = [];
                    this.partnerVendorId = '';
                    this.form.partnerProductId = '';
                    return this.syncPartner();
                }
                if (this.partnerVendorId === vendorId) return this.syncPartner();

                const itemId = this.item.id;
                this.partnerProducts = [];
                this.loadingPartners = true;
                this.syncPartner();
                try {
                    const res = await fetch(this.item.partner_products_url + '?vendor_id=' + encodeURIComponent(vendorId), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (this.item?.id !== itemId || (this.form.supplierManual ? '' : this.form.vendorId) !== vendorId) return;
                    this.partnerProducts = data.data ?? [];
                    this.partnerVendorId = vendorId;
                    if (this.form.partnerProductId && !this.partnerProducts.some((p) => p.value === this.form.partnerProductId)) this.form.partnerProductId = '';
                    if (!this.form.partnerProductId && this.partnerProducts.length === 1) this.form.partnerProductId = this.partnerProducts[0].value;
                } catch (e) {
                    console.error('[print-label] load partner products failed', e);
                } finally {
                    this.loadingPartners = false;
                    this.syncPartner();
                }
            },

            sourcePrefill() {
                const vendorId = this.form.supplierManual ? '' : this.form.vendorId;
                return {
                    origin: 'history',
                    vendor_id: vendorId || null,
                    vendor_name: vendorId ? (supplierTs?.options[vendorId]?.text ?? null) : null,
                    partner_product_id: vendorId ? (this.form.partnerProductId || null) : null,
                    supplier_name: vendorId ? null : (this.form.supplierText.trim() || null),
                };
            },

            applySourcePrefill(row) {
                const pf = row.print_prefill ?? {};
                this.sourceOrigin = null;
                if (pf.vendor_id) {
                    prefilling = true;
                    this.form.supplierManual = false;
                    this.form.partnerProductId = pf.partner_product_id ?? '';
                    if (!supplierTs?.options[pf.vendor_id]) supplierTs?.addOption({ value: pf.vendor_id, text: pf.vendor_name || pf.vendor_id });
                    supplierTs?.setValue(pf.vendor_id);
                    prefilling = false;
                    this.sourceOrigin = pf.origin ?? null;
                } else if (pf.supplier_name) {
                    this.form.supplierManual = true;
                    this.form.supplierText = pf.supplier_name;
                    this.sourceOrigin = pf.origin ?? null;
                }
            },

            async loadBatches(row) {
                batchTs?.clear(true);
                batchTs?.clearOptions();
                this.batchVendors = {};
                this.batchPartners = {};
                this.batchInfo = {};
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
                        this.batchPartners[b.value] = b.partner_product_id ?? '';
                        this.batchInfo[b.value] = { has_quarantine: !!b.has_quarantine, mfg_date: b.mfg_date, exp_date: b.exp_date };
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
                    mfg: row.requires_dates ? '' : toYmd(new Date()),
                    exp: '',
                    supplierManual: false,
                    supplierText: '',
                    vendorId: '',
                    batchId: '',
                    batchCode: '',
                    partnerProductId: '',
                    // Ưu tiên mẫu tem gán riêng cho sản phẩm; nếu chưa có thì dùng mẫu mặc định của đơn.
                    template: row.label_template_id || defaultTemplateId || '',
                };
                templateTs?.setValue(this.form.template, true);
                supplierTs?.clear(true);
                this.partnerProducts = [];
                this.partnerVendorId = '';
                this.syncPartner();
                this.applySourcePrefill(row);
                if (this.form.mfg) mfgPicker?.setDate(this.form.mfg, false); else mfgPicker?.clear(false);
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
                            mfg_date: this.form.mfg || null,
                            exp_date: this.form.exp,
                            supplier_name: this.form.supplierText.trim() || null,
                            vendor_id: this.form.supplierManual ? null : (this.form.vendorId || null),
                            partner_product_id: !this.form.supplierManual && this.form.vendorId ? (this.form.partnerProductId || null) : null,
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
                        if (data.print_prefill) {
                            emitPrefill(data.print_prefill);
                            this.message = data.message;
                        }
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
                        emitPrefill({ [this.item.id]: prefillOfGroups(mergeSplitOf(this.printedGroups, this.form.groups)) });
                    }
                    if (this.item) emitPrefill({ [this.item.id]: this.sourcePrefill() });

                    if (data.toast) window.Toast?.success?.(data.toast, { duration: 6000 });

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

document.addEventListener('alpine:init', () => {
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
                    groups: row.groups.map((g) => ({ weight: g.weight, qty: g.qty })), total: row.total, printedKey: splitKey(row.printedGroups),
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
            notice: '',

            get selectedItems() {
                return this.items.filter((row) => row.selected);
            },

            get sendItems() {
                return this.selectedItems.filter((row) => row.groups.length > 0);
            },

            get allSelected() {
                return this.items.length > 0 && this.items.every((row) => row.selected);
            },

            toggleAll(value) {
                this.items.forEach((row) => { row.selected = value; });
            },

            selectNeeded() {
                this.items.forEach((row) => { row.selected = this.needsPrint(row); });
            },

            needsPrint(row) {
                return row.printed === 0 || this.sourceChanged(row);
            },

            rowSig(row) {
                return [splitKey(row.groups), row.batchId || '', row.supplierManual ? 'm:' + (row.supplierText || '') : (row.vendorId || ''), row.partnerProductId || ''].join('|');
            },

            get selectedLabels() {
                return this.sendItems.reduce((sum, row) => sum + this.rowPrintCount(row), 0);
            },

            rowPrintCount(row) {
                if (row.printed === 0) return this.rowLabelCount(row);
                return this.appendCount(row) > 0 ? this.appendCount(row) : this.reuseCount(row);
            },

            fmtKg,
            ...cascade,

            get introText() {
                return `Khối lượng của ${this.preview.total} mặt hàng được điền sẵn theo đơn; nguồn cung điền theo lần in trước của mặt hàng, hoặc lần in gần nhất cùng khách hàng ở đơn trước (đổi được). Bấm biểu tượng bút để tự chia số tem và khối lượng mỗi tem — tem trùng khối lượng với tem đã in sẽ dùng lại mã cũ, phần không trùng tạo mã mới.`;
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
                return this.selectedLabels;
            },

            // Nguồn của dòng đúng như trên màn hình: lô, NCC hoặc tên nhập tay — server không tự gán mặc định; thiếu nguồn vẫn in được (chỉ cảnh báo).
            rowHasSource(row) {
                return !!row.batchId || (row.supplierManual ? !!row.supplierText.trim() : !!row.vendorId);
            },

            get missingSourceCount() {
                return this.selectedItems.filter((row) => !this.rowHasSource(row)).length;
            },

            get canRun() {
                const rows = this.sendItems;
                return !this.submitting && rows.length > 0 && !!this.form.exp && !!this.form.template
                    && this.totalLabels <= 2000
                    && rows.every((row) => row.groups.every((g) => this.groupValid(g)));
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
                row.groups = [{ uid: ++this._uid, weight: row.total, qty: 1 }];
            },

            appendCount(row) {
                return appendCountOf(row.printedGroups, row.groups);
            },

            reuseCount(row) {
                return reuseCountOf(row.printedGroups, row.groups);
            },

            printedSummary(row) {
                return splitSummary(row.printedGroups, row.unit);
            },

            appendDetail(row) {
                return matchParts(row.printedGroups, row.groups).filter((p) => p.add > 0)
                    .map((p) => fmtKg(p.weight) + ' ' + row.unit + ' × ' + p.add).join(', ');
            },

            groupNote(row, group) {
                return groupNoteOf(row.printedGroups, row.groups, group);
            },

            rowLabelCount(row) {
                return row.groups.reduce((s, g) => s + groupQty(g), 0);
            },

            addGroup(row) {
                row.groups.push({ uid: ++this._uid, weight: '', qty: 1 });
            },

            removeGroup(row, index) {
                row.groups.splice(index, 1);
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
                    const pfNow = r.print_prefill ?? {};
                    if (old && old.total === total && old.printed === (pfNow.printed ?? 0)
                        && splitKey(old.printedGroups) === splitKey(pfNow.printed_groups ?? [])) {
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
                    if (d?.groups?.length && d.total === total && (d.printedKey ?? '') === splitKey(row.printedGroups)) {
                        row.groups = d.groups.map((g) => ({ uid: ++this._uid, weight: g.weight, qty: g.qty }));
                    } else {
                        this.autoSplit(row);
                    }
                    row.blocked = '';
                    row.selected = this.needsPrint(row);
                    row.lastSig = this.rowSig(row);
                    return row;
                }).filter((row) => row.total > 0);
                this.preview = { total: this.items.length };
            },

            get alertClass() { return this.ok ? 'alert-success' : 'alert-warning'; },

            init() {
                this.$watch('items', () => {
                    this.items.forEach((row) => {
                        const sig = this.rowSig(row);
                        if (sig === row.lastSig) return;
                        row.lastSig = sig;
                        row.blocked = '';
                        row.selected = true;
                    });
                    saveDraft(this.items);
                }, { deep: true });

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
                this.notice = '';
                const sent = this.sendItems;

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
                            items: sent.map((row) => ({
                                order_item_id: row.id,
                                ...printSourcePayload(row),
                                label_groups: row.groups.map((g) => ({ weight_per_label: Number(g.weight), label_count: Number(g.qty) })),
                            })),
                        }),
                    });
                    const data = await res.json().catch(() => ({}));

                    if (res.status === 422) {
                        win?.close();
                        if (data.print_prefill) {
                            this.items.forEach((row) => {
                                const pf = data.print_prefill[row.id];
                                if (!pf) return;
                                row.printed = pf.printed;
                                row.printedGroups = pf.printed_groups;
                                row.editing = true;
                            });
                            emitPrefill(data.print_prefill);
                        }
                        Object.entries(data.errors ?? {}).forEach(([k, v]) => {
                            const field = k.startsWith('items') ? 'items' : k;
                            this.errors[field] ??= v[0];
                        });
                        return;
                    }
                    if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);

                    const skipped = data.skipped ?? {};
                    const changed = {};
                    sent.forEach((row) => {
                        if (skipped[row.id]) {
                            row.blocked = skipped[row.id];
                            row.selected = false;
                            return;
                        }
                        row.selected = false;
                        if (row.printed === 0 || this.appendCount(row) > 0) {
                            row.printedGroups = mergeSplitOf(row.printedGroups, row.groups);
                            row.printed = row.printedGroups.reduce((s, g) => s + g.qty, 0);
                            changed[row.id] = { printed: row.printed, printed_groups: row.printedGroups };
                        }
                        row.printedSource = { batchId: row.batchId || '', vendorId: row.supplierManual ? '' : row.vendorId, partnerProductId: row.supplierManual || !row.vendorId ? '' : (row.partnerProductId || ''), supplierText: row.supplierManual ? row.supplierText.trim() : '' };
                        row.origin = 'history';
                        changed[row.id] = { ...(changed[row.id] ?? {}), origin: 'history', batch_id: row.batchId || null, batch_text: row.batchText || null, ...printSourcePayload(row), vendor_name: row.supplierManual ? null : (row.vendorName || null) };
                        row.dirty = false;
                        row.viaGlobal = false;
                        row.manual = false;
                        row.lastSig = this.rowSig(row);
                    });
                    this.globalVendorId = '';
                    globalTs?.clear(true);
                    clearDraft();
                    emitPrefill(changed);

                    const skippedCount = Object.keys(skipped).length;
                    this.confirming = skippedCount > 0;
                    this.ok = skippedCount === 0;
                    this.message = data.message;
                    this.notice = skippedCount > 0 ? data.message : '';

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

    window.addEventListener('sales-order-prefill', (e) => {
        Object.entries(e.detail ?? {}).forEach(([id, pf]) => {
            const row = rowsById.get(String(id));
            if (!row) return;
            row.print_prefill = { ...(row.print_prefill ?? {}), ...pf };
            window.salesOrderItemsTable?.updateData([{ id: row.id, print_prefill: row.print_prefill }]).catch(() => {});
        });
    });

    el.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action][data-item-id]');
        if (!btn || !el.contains(btn)) return;
        const row = rowsById.get(btn.dataset.itemId);
        if (!row) return;
        e.preventDefault();
        e.stopPropagation();
        if (btn.dataset.action === 'print' && row.print_url) window.dispatchEvent(new CustomEvent('open-print-label', { detail: row }));
        if (btn.dataset.action === 'qc' && row.qc_url) openItemQcModal(row);
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
