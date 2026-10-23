import { createTs, createTsRemote, destroyTs } from '@shared/tom-select-factory.js';

const uid = () => 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);

const esc = (v) => String(v ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const blankRow = () => ({ uid: uid(), product: null, requested_qty: '', orig_product_id: null, printed: 0, qc: false, raw_name: null });

document.addEventListener('alpine:init', () => {
    Alpine.data('salesOrderForm', ({ fields = {}, customer = null, rows = [], errors = {}, urls = {} }) => {
        const productSelects = new Map();
        const printedOriginals = rows.filter((r) => r.orig_product_id && r.printed > 0)
            .map((r) => ({ productId: String(r.orig_product_id), printed: r.printed }));
        let customerTs = null;
        let pointTs = null;

        return {
            fields: { ...fields, customer_name: fields.customer_name ?? '', delivery_address: fields.delivery_address ?? '' },
            rows: rows.length ? rows.map((r) => ({ ...blankRow(), ...r })) : [blankRow()],
            errors,
            points: [],
            pointsLoading: false,
            notice: '',
            submitting: false,

            get filledCount() {
                return this.rows.filter((r) => r.product).length;
            },

            get totalQty() {
                return this.rows.reduce((sum, r) => sum + (parseFloat(r.requested_qty) || 0), 0);
            },

            init() {
                this.$nextTick(() => this._initCustomerSelects(customer));
            },

            destroy() {
                productSelects.forEach(destroyTs);
                destroyTs(customerTs);
                destroyTs(pointTs);
            },

            err(key) {
                return this.errors[key]?.[0] ?? '';
            },

            productChanged(row) {
                return !!row.orig_product_id && String(row.product?.id ?? '') !== String(row.orig_product_id);
            },

            labelsToRevoke() {
                const present = new Set(this.rows.filter((r) => r.product).map((r) => String(r.product.id)));
                return printedOriginals.filter((o) => !present.has(o.productId)).reduce((sum, o) => sum + o.printed, 0);
            },

            confirmRemoveRow(row) {
                if (row.printed > 0 && !this.productChanged(row)
                    && !confirm('Gỡ dòng "' + (row.product?.text ?? '') + '" sẽ hủy ' + row.printed + ' tem đã in khi lưu (quét QR sẽ báo mã đã bị hủy). Tiếp tục?')) return;
                this.removeRow(row.uid);
            },

            qtyStep(row) {
                return row.product && !row.product.decimal ? '1' : '0.01';
            },

            blockQtyKeys(event, row) {
                if (['-', '+', 'e', 'E'].includes(event.key)) event.preventDefault();
                if (row.product && !row.product.decimal && ['.', ','].includes(event.key)) event.preventDefault();
            },

            formatQty(value) {
                return Number(value).toLocaleString('vi-VN', { maximumFractionDigits: 3 });
            },

            _initCustomerSelects(initial) {
                customerTs = createTsRemote(this.$refs.customerSelect, {
                    url: urls.customers,
                    searchField: ['text', 'code'],
                    placeholder: '— Tìm khách hàng theo tên / mã —',
                    preload: 'focus',
                    options: initial ? [initial] : [],
                    items: initial ? [initial.id] : [],
                    render: {
                        option: (d) => `<div class="py-0.5"><div class="text-sm font-medium">${esc(d.text)}</div>`
                            + (d.code ? `<div class="text-xs opacity-50 font-mono">${esc(d.code)}</div>` : '') + '</div>',
                        item: (d) => `<div>${esc(d.text)}</div>`,
                    },
                    onChange: (value) => this.onCustomerChange(value),
                });

                pointTs = createTs(this.$refs.pointSelect, {
                    valueField: 'id',
                    labelField: 'text',
                    searchField: ['text', 'address'],
                    placeholder: '— Chọn điểm giao —',
                    render: {
                        option: (d) => `<div class="py-0.5"><div class="text-sm font-medium">${esc(d.text)}</div>`
                            + (d.address && d.address !== d.text ? `<div class="text-xs opacity-50">${esc(d.address)}</div>` : '') + '</div>',
                    },
                    onChange: (value) => this.onPointChange(value),
                });
                pointTs?.disable();

                if (initial) this._loadPoints(initial.id);
            },

            onCustomerChange(value) {
                this.fields.customer_id = value || '';
                this.points = [];
                pointTs?.clear(true);
                pointTs?.clearOptions();
                pointTs?.disable();
                if (!value) return;

                const c = customerTs.options[value];
                if (c) {
                    this.fields.customer_name = c.text;
                    this.fields.delivery_address = c.address || '';
                }
                this._loadPoints(value, true);
            },

            onPointChange(value) {
                const p = this.points.find((x) => String(x.id) === String(value));
                if (!p) return;
                this.fields.delivery_address = [p.site_name, p.address].filter(Boolean).join(' - ');
            },

            async _loadPoints(customerId, autoPick = false) {
                this.pointsLoading = true;
                try {
                    const res = await fetch(urls.deliveryPoints.replace('__ID__', encodeURIComponent(customerId)), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                    });
                    if (!res.ok) throw new Error(res.status);
                    if (this.fields.customer_id !== customerId) return;
                    this.points = await res.json();
                    pointTs?.addOptions(this.points);
                    if (this.points.length) pointTs?.enable();
                    if (autoPick && this.points.length === 1) pointTs?.setValue(this.points[0].id);
                } catch (_) {
                    this.points = [];
                } finally {
                    this.pointsLoading = false;
                }
            },

            initProductSelect(el, row) {
                const ts = createTsRemote(el, {
                    url: urls.products,
                    searchField: ['text', 'sku'],
                    placeholder: '— Tìm sản phẩm theo tên / mã —',
                    preload: 'focus',
                    options: row.product ? [row.product] : [],
                    items: row.product ? [row.product.id] : [],
                    render: {
                        option: (d) => `<div class="py-0.5 flex items-baseline gap-2"><span class="text-xs opacity-50 font-mono">${esc(d.sku)}</span>`
                            + `<span class="text-sm">${esc(d.text)}</span></div>`,
                        item: (d) => `<div><span class="text-xs opacity-50 font-mono mr-1">${esc(d.sku)}</span>${esc(d.text)}</div>`,
                    },
                    onChange: (value) => this.onProductChange(row.uid, value),
                });
                if (ts) productSelects.set(row.uid, ts);
                if (ts && row.qc) ts.disable();
            },

            onProductChange(rowUid, value) {
                const row = this.rows.find((r) => r.uid === rowUid);
                if (!row) return;
                this._clearRowErrors(rowUid);

                if (!value) {
                    row.product = null;
                    return;
                }

                const dup = this.rows.find((r) => r.uid !== rowUid && r.product && String(r.product.id) === String(value));
                if (dup && dup.qc) {
                    this.notice = `"${dup.product.text}" đã có ở dòng ${this.rows.indexOf(dup) + 1} và đã khóa do có biên bản QC — không gộp thêm được.`;
                    setTimeout(() => productSelects.get(rowUid)?.clear());
                    return;
                }
                if (dup) {
                    const merged = (parseFloat(dup.requested_qty) || 0) + (parseFloat(row.requested_qty) || 0);
                    dup.requested_qty = merged ? String(Math.round(merged * 100) / 100) : dup.requested_qty;
                    const line = this.rows.indexOf(dup) + 1;
                    this.notice = `"${dup.product.text}" đã có ở dòng ${line} — đã gộp số lượng vào dòng đó.`;
                    setTimeout(() => this.removeRow(rowUid, true));
                    return;
                }

                this.notice = '';
                row.product = { ...productSelects.get(rowUid).options[value] };
                if (!row.product.decimal && row.requested_qty !== '' && !Number.isInteger(Number(row.requested_qty))) {
                    this.errors = { ...this.errors, ['items.' + rowUid + '.requested_qty']: ['Đơn vị "' + (row.product.unit || '—') + '" chỉ nhập số nguyên.'] };
                }
            },

            addRow() {
                this.rows.push(blankRow());
                this.$nextTick(() => productSelects.get(this.rows[this.rows.length - 1].uid)?.focus());
            },

            removeRow(rowUid, keepNotice = false) {
                if (!keepNotice) this.notice = '';
                destroyTs(productSelects.get(rowUid));
                productSelects.delete(rowUid);
                this.rows = this.rows.filter((r) => r.uid !== rowUid);
                if (!this.rows.length) this.addRow();
            },

            _clearRowErrors(rowUid) {
                const prefix = 'items.' + rowUid + '.';
                this.errors = Object.fromEntries(Object.entries(this.errors).filter(([k]) => !k.startsWith(prefix) && k !== 'items'));
            },

            onSubmit(event) {
                if (!this.rows.some((r) => r.product)) {
                    event.preventDefault();
                    this.errors = { ...this.errors, items: ['Vui lòng thêm ít nhất 1 dòng hàng hóa.'] };
                    return;
                }
                const revoke = this.labelsToRevoke();
                if (revoke > 0 && !confirm('Lưu thay đổi sẽ hủy ' + revoke + ' tem đã in của các dòng bị gỡ hoặc đổi sản phẩm. Quét QR các tem này sẽ báo "Mã truy xuất đã bị hủy". Tiếp tục?')) {
                    event.preventDefault();
                    return;
                }
                this.submitting = true;
            },
        };
    });
});
