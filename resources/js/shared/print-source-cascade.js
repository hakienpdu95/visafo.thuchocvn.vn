import { createTs } from '@shared/tom-select-factory.js';

export function printSourceState(overrides = {}) {
    return {
        id: '',
        batchesUrl: '',
        partnerProductsUrl: '',
        hasBatches: false,
        batches: [],
        batchesLoaded: false,
        loadingBatches: false,
        batchId: '',
        batchText: '',
        batchPartnerId: '',
        vendorId: '',
        vendorName: '',
        partnerProductId: '',
        partnerProducts: [],
        partnerProductsVendorId: '',
        loadingPartners: false,
        supplierManual: false,
        supplierText: '',
        ...overrides,
    };
}

export function printSourcePayload(row) {
    const vendorId = row.supplierManual ? null : (row.vendorId || null);
    return {
        product_batch_id: row.batchId || null,
        vendor_id: vendorId,
        partner_product_id: vendorId ? (row.partnerProductId || null) : null,
        supplier_name: row.supplierManual ? (row.supplierText.trim() || null) : null,
    };
}

export function printSourceCascade({ vendors = [], onUserChange = () => {}, logTag = 'print-source', dropdownParent = null } = {}) {
    const rowSupplierTs = new Map();
    const rowPartnerTs = new Map();
    const rowBatchTs = new Map();

    return {
        supplierOf(row) {
            if (row.supplierManual) return row.supplierText.trim();
            return row.vendorName || (row.vendorId ? (vendors.find((v) => v.value === row.vendorId)?.text ?? '') : '');
        },

        batchOptions(row) {
            if (!row.batchId || row.batches.some((b) => b.value === row.batchId)) return row.batches;
            return [{ value: row.batchId, text: row.batchText }, ...row.batches];
        },

        syncRowBatch(row) {
            const ts = rowBatchTs.get(row.id);
            if (!ts) return;
            ts.clearOptions();
            ts.addOption({ value: '', text: '— Không chọn lô —' });
            ts.addOptions(this.batchOptions(row).map((b) => ({ value: b.value, text: b.text + (b.in_stock === false ? ' (hết tồn)' : '') })));
            ts.setValue(row.batchId || '', true);
        },

        initRowBatch(row, el) {
            rowBatchTs.get(row.id)?.destroy();
            const ts = createTs(el, {
                ...(dropdownParent ? { dropdownParent } : {}),
                placeholder: '— Không chọn lô —',
                maxOptions: null,
                allowEmptyOption: true,
                onChange: (value) => {
                    row.batchId = value || '';
                    this.onRowBatchChange(row);
                },
            });
            if (!ts) return;
            rowBatchTs.set(row.id, ts);
            this.syncRowBatch(row);
        },

        // Đồng bộ ô Tom Select "Hàng hóa NCC" theo state của dòng (danh sách, giá trị, khóa/mở).
        syncRowPartner(row) {
            const ts = rowPartnerTs.get(row.id);
            if (!ts) return;
            const options = [...row.partnerProducts];
            if (row.partnerProductId && !options.some((p) => p.value === row.partnerProductId)) {
                options.unshift({ value: row.partnerProductId, text: 'Hàng hóa NCC đã chọn trước đó' });
            }
            ts.clearOptions();
            ts.addOptions(options);
            ts.settings.placeholder = !row.vendorId ? '— Chọn nguồn cung trước —'
                : row.loadingPartners ? 'Đang tải…'
                : options.length === 0 ? 'NCC chưa kê khai hàng hóa này' : '— Không chọn —';
            ts.control_input.setAttribute('placeholder', ts.settings.placeholder);
            ts.setValue(row.partnerProductId || '', true);
            // Có lô: hàng hóa NCC theo lô (lô canh tác) hoặc chỉ có 1 lựa chọn → khóa; nhiều lựa chọn thì vẫn cho chọn.
            const lockedByBatch = !!row.batchId && (!!row.batchPartnerId || options.length <= 1);
            const disabled = !row.vendorId || row.supplierManual || row.loadingPartners || options.length === 0 || lockedByBatch;
            disabled ? ts.disable() : ts.enable();
        },

        initRowPartner(row, el) {
            rowPartnerTs.get(row.id)?.destroy();
            const ts = createTs(el, {
                ...(dropdownParent ? { dropdownParent } : {}),
                placeholder: '— Chọn nguồn cung trước —',
                maxOptions: null,
                onChange: (value) => {
                    row.partnerProductId = value || '';
                    onUserChange(row, 'partner');
                },
            });
            if (!ts) return;
            rowPartnerTs.set(row.id, ts);
            this.syncRowPartner(row);
        },

        // Cascade Nguồn cung → Hàng hóa NCC: tải hàng hóa của NCC đang chọn ánh xạ đúng sản phẩm chuẩn của dòng; chỉ 1 kết quả thì tự chọn.
        async loadRowPartners(row) {
            const vendorId = row.supplierManual ? '' : row.vendorId;
            if (!vendorId || !row.partnerProductsUrl) {
                row.partnerProducts = [];
                row.partnerProductsVendorId = '';
                row.partnerProductId = '';
                this.syncRowPartner(row);
                return;
            }
            if (row.partnerProductsVendorId === vendorId) return this.syncRowPartner(row);

            row.loadingPartners = true;
            this.syncRowPartner(row);
            try {
                const res = await fetch(row.partnerProductsUrl + '?vendor_id=' + encodeURIComponent(vendorId), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if ((row.supplierManual ? '' : row.vendorId) !== vendorId) return; // NCC đã đổi trong lúc chờ
                row.partnerProducts = data.data ?? [];
                row.partnerProductsVendorId = vendorId;
                if (row.partnerProductId && !row.partnerProducts.some((p) => p.value === row.partnerProductId)) row.partnerProductId = '';
                if (!row.partnerProductId && row.partnerProducts.length === 1) row.partnerProductId = row.partnerProducts[0].value;
            } catch (e) {
                console.error(`[${logTag}] load partner products failed`, e);
            } finally {
                row.loadingPartners = false;
                this.syncRowPartner(row);
            }
        },

        onRowVendorChange(row, prevVendorId = null, presetPartnerId = '') {
            if (prevVendorId !== null && prevVendorId === (row.supplierManual ? '' : row.vendorId)) {
                if (presetPartnerId) row.partnerProductId = presetPartnerId;
                return this.loadRowPartners(row);
            }
            row.partnerProductId = presetPartnerId;
            row.partnerProducts = [];
            row.partnerProductsVendorId = '';
            return this.loadRowPartners(row);
        },

        async loadRowBatches(row) {
            if (row.batchesLoaded || row.loadingBatches || !row.batchesUrl || !row.hasBatches) return;
            row.loadingBatches = true;
            try {
                const res = await fetch(row.batchesUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                row.batches = data.data ?? [];
                row.batchesLoaded = true;
                row.batchPartnerId = row.batches.find((b) => b.value === row.batchId)?.partner_product_id ?? '';
                this.syncRowBatch(row);
                this.syncRowPartner(row);
            } catch (e) {
                console.error(`[${logTag}] load batches failed`, e);
            } finally {
                row.loadingBatches = false;
            }
        },

        // Toàn bộ NCC đang hoạt động; NCC đang gán (lần in trước / nguồn cung chung) vẫn giữ để không mất giá trị.
        rowVendorOptions(row) {
            return vendors
                .concat(row.vendorId && !vendors.some((v) => v.value === row.vendorId) ? [{ value: row.vendorId, text: row.vendorName || row.vendorId }] : [])
                .map((v) => ({ value: v.value, text: v.text }));
        },

        syncRowSupplier(row) {
            const ts = rowSupplierTs.get(row.id);
            if (!ts) return;
            const value = row.supplierManual ? '' : row.vendorId;
            if (value && !ts.options[value]) ts.addOptions(this.rowVendorOptions(row));
            ts.setValue(value, true);
        },

        initRowSupplier(row, el) {
            rowSupplierTs.get(row.id)?.destroy();
            const ts = createTs(el, {
                ...(dropdownParent ? { dropdownParent } : {}),
                placeholder: '— Chọn nhà cung cấp —',
                maxOptions: null,
                options: this.rowVendorOptions(row),
                items: row.vendorId && !row.supplierManual ? [row.vendorId] : [],
                onChange: (value) => {
                    row.vendorId = value || '';
                    row.vendorName = value ? (vendors.find((v) => v.value === value)?.text ?? '') : '';
                    onUserChange(row, 'vendor');
                    this.onRowVendorChange(row);
                },
            });
            if (ts) rowSupplierTs.set(row.id, ts);
        },

        onRowBatchChange(row) {
            const batch = row.batches.find((b) => b.value === row.batchId);
            const prevVendorId = row.supplierManual ? '' : row.vendorId;
            onUserChange(row, 'batch');
            row.batchText = batch?.text ?? '';
            row.vendorId = batch?.vendor_id ?? '';
            row.vendorName = batch?.vendor_name ?? '';
            row.supplierManual = !batch?.vendor_id && !!batch?.supplier_name;
            row.supplierText = row.supplierManual ? batch.supplier_name : '';
            row.batchPartnerId = batch?.partner_product_id ?? '';
            this.syncRowSupplier(row);
            this.onRowVendorChange(row, prevVendorId, row.batchPartnerId);
        },

        onRowSupplierTextInput(row) {
            onUserChange(row, 'text');
        },

        onRowSupplierManualToggle(row) {
            rowSupplierTs.get(row.id)?.clear(true);
            row.vendorId = '';
            row.vendorName = '';
            row.supplierText = '';
            onUserChange(row, 'manual');
            this.onRowVendorChange(row);
        },
    };
}
