import { createTsRemote, destroyTs } from '@shared/tom-select-factory.js';

const uid = () => 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);

const esc = (v) => String(v ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const blankRow = () => ({ uid: uid(), product: null, quantity: '' });

document.addEventListener('alpine:init', () => {
    Alpine.data('goodsReceiptForm', ({ fields = {}, vendor = null, rows = [], errors = {}, urls = {} }) => {
        const productSelects = new Map();
        let vendorTs = null;

        return {
            fields: { ...fields },
            rows: rows.length ? rows.map((r) => ({ ...r })) : [blankRow()],
            errors,
            notice: '',
            submitting: false,

            get filledCount() {
                return this.rows.filter((r) => r.product).length;
            },

            get totalQty() {
                return this.rows.reduce((sum, r) => sum + (parseFloat(r.quantity) || 0), 0);
            },

            init() {
                this.$nextTick(() => this._initVendorSelect(vendor));
            },

            destroy() {
                productSelects.forEach(destroyTs);
                destroyTs(vendorTs);
            },

            err(key) {
                return this.errors[key]?.[0] ?? '';
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

            _initVendorSelect(initial) {
                vendorTs = createTsRemote(this.$refs.vendorSelect, {
                    url: urls.vendors,
                    searchField: ['text', 'code'],
                    placeholder: '— Tìm nhà cung cấp theo tên / mã —',
                    preload: 'focus',
                    options: initial ? [initial] : [],
                    items: initial ? [initial.id] : [],
                    render: {
                        option: (d) => `<div class="py-0.5"><div class="text-sm font-medium">${esc(d.text)}</div>`
                            + (d.code ? `<div class="text-xs opacity-50 font-mono">${esc(d.code)}</div>` : '') + '</div>',
                        item: (d) => `<div>${esc(d.text)}</div>`,
                    },
                    onChange: (value) => {
                        this.fields.vendor_id = value || '';
                        if (value) this.errors = Object.fromEntries(Object.entries(this.errors).filter(([k]) => k !== 'vendor_id'));
                    },
                });
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
                if (dup) {
                    const merged = (parseFloat(dup.quantity) || 0) + (parseFloat(row.quantity) || 0);
                    dup.quantity = merged ? String(Math.round(merged * 100) / 100) : dup.quantity;
                    const line = this.rows.indexOf(dup) + 1;
                    this.notice = `"${dup.product.text}" đã có ở dòng ${line} — đã gộp số lượng vào dòng đó (mỗi sản phẩm một lô).`;
                    setTimeout(() => this.removeRow(rowUid, true));
                    return;
                }

                this.notice = '';
                row.product = { ...productSelects.get(rowUid).options[value] };
                if (!row.product.decimal && row.quantity !== '' && !Number.isInteger(Number(row.quantity))) {
                    this.errors = { ...this.errors, ['items.' + rowUid + '.quantity']: ['Đơn vị "' + (row.product.unit || '—') + '" chỉ nhập số nguyên.'] };
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
                const errors = {};
                if (!this.fields.vendor_id) errors.vendor_id = ['Vui lòng chọn nhà cung cấp từ danh mục.'];
                if (!this.rows.some((r) => r.product)) errors.items = ['Vui lòng thêm ít nhất 1 dòng hàng hóa.'];
                if (Object.keys(errors).length) {
                    event.preventDefault();
                    this.errors = { ...this.errors, ...errors };
                    return;
                }
                this.submitting = true;
            },
        };
    });
});
