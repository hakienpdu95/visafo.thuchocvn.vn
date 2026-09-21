import { createTs } from '@shared/tom-select-factory.js';
import './_row-directives.js';

// ── Constants ──────────────────────────────────────────────────────────────
const FORM_SEL     = '[data-food-inspection-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

const asBool = (v, d = true) => (v === undefined || v === null || v === '') ? d : (v === true || v === '1' || v === 1);

// Directive x-ts / x-fp dùng chung cho lưới: xem _row-directives.js
document.addEventListener('alpine:init', () => {
    // Smart Combobox NCC (chọn có sẵn hoặc gõ tên mới + Enter): x-vts="row"
    Alpine.directive('vts', (el, { expression }, { evaluateLater, effect, cleanup }) => {
        const readRow = evaluateLater(expression);
        const readVendors = evaluateLater('vendors');
        const notify = evaluateLater('onVendorSelect(' + expression + ', __v)');
        let row = null;
        let vendors = [];
        readRow((r) => { row = r; });
        readVendors((v) => { vendors = v; });

        const ts = createTs(el, {
            maxOptions: null,
            create: (input) => ({ value: input.trim(), text: input.trim() }),
            createFilter: (input) => input.trim().length > 1,
            createOnBlur: false,
            persist: false,
            valueField: 'value',
            labelField: 'text',
            searchField: ['text'],
            options: vendors.map((v) => ({ value: v.id, text: v.name })),
            placeholder: 'Chọn hoặc gõ tên cơ sở mới',
            render: {
                option_create: (data, escape) => '<div class="create">Thêm mới: <strong>' + escape(data.input) + '</strong>&hellip;</div>',
                no_results: () => '<div class="no-results" style="padding:.75rem;font-size:.875rem;color:#94a3b8;text-align:center">Không tìm thấy — gõ tên rồi nhấn Enter để thêm mới</div>',
            },
            onChange(value) { notify(() => {}, { scope: { __v: value } }); },
        });
        if (!ts) return;

        // Model → UI (dòng copy từ dòng trước, dòng nạp từ phiếu nhập kho, form sửa...)
        effect(() => readRow((r) => {
            const want = r.vendor_id || r.vendor_name || '';
            if ((ts.getValue() || '') === want) return;
            if (want && !ts.options[want]) ts.addOption({ value: want, text: r.vendor_name || want });
            ts.setValue(want, true);
        }));
        cleanup(() => ts.destroy());
    });

    // ── Alpine: lưới kiểm thực (Nhóm A tươi sống / Nhóm B khô, bao gói) ───────────
    Alpine.data('foodInspectionRows', (cfg) => {
        let seq = Math.max(0, ...Object.keys(cfg.rows).map(Number).filter(Number.isFinite));

        const makeRow = (group, src = {}, n = ++seq) => ({
            n,
            id: src.id ?? '',
            food_group: group,
            product_id: src.product_id ?? '',
            product_name: src.product_name ?? '',
            received_at: src.received_at || cfg.nowLocal,
            vendor_id: src.vendor_id ?? '',
            vendor_name: src.vendor_name ?? '',
            supplier_address: src.supplier_address ?? cfg.vendors.find((v) => v.id === src.vendor_id)?.address ?? '',
            supplier_phone: src.supplier_phone ?? cfg.vendors.find((v) => v.id === src.vendor_id)?.phone ?? '',
            deliverer_name: src.deliverer_name ?? '',
            quantity: src.quantity ?? '',
            unit: src.unit ?? '',
            has_invoice: asBool(src.has_invoice),
            has_vet_cert: asBool(src.has_vet_cert),
            has_quarantine_cert: asBool(src.has_quarantine_cert),
            sensory_result: src.sensory_result ?? 'pass',
            quick_test_result: src.quick_test_result ?? 'none',
            handling_measure: src.handling_measure ?? '',
            manufacturer_name: src.manufacturer_name ?? '',
            manufacturer_address: src.manufacturer_address ?? '',
            expiry_date: src.expiry_date ?? '',
            storage_condition: src.storage_condition ?? 'ambient',
        });

        return {
            vendors: cfg.vendors,
            rows: Object.entries(cfg.rows).map(([n, r]) => makeRow(r.food_group || 'fresh', r, Number(n))),
            submitting: false,

            get freshRows() { return this.rows.filter((r) => r.food_group === 'fresh'); },
            get dryRows() { return this.rows.filter((r) => r.food_group === 'dry'); },
            get failedCount() { return this.rows.filter((r) => this.isFailed(r)).length; },

            isFailed(r) { return r.sensory_result === 'fail' || (r.food_group === 'fresh' && r.quick_test_result === 'fail'); },
            onResultChange(r) { if (!this.isFailed(r)) r.handling_measure = ''; },

            // Lỗi server theo dòng: key dạng details.{n}.{field}
            serverErr(r, f) { return cfg.errs.includes(`details.${r.n}.${f}`); },

            // Combobox NCC: trùng tên trong danh sách thì gắn vendor_id, ngoài danh sách thì nhập tay.
            // Smart Combobox NCC: value = id NCC có sẵn, hoặc chính tên vừa gõ (NCC mới).
            // NCC có sẵn → tự điền địa chỉ/SĐT từ Master Data và khóa 2 ô; NCC mới → mở khóa để gõ tay
            // (server sẽ tạo bản ghi Vendor mới khi lưu).
            onVendorSelect(r, value) {
                const typed = String(value ?? '').trim();
                const v = this.vendors.find((x) => x.id === typed)
                    ?? this.vendors.find((x) => x.name.toLowerCase() === typed.toLowerCase());

                if (v) {
                    r.vendor_id = v.id;
                    r.vendor_name = v.name;
                    r.supplier_address = v.address ?? '';
                    r.supplier_phone = v.phone ?? '';
                    return;
                }

                if (r.vendor_id !== '') { r.supplier_address = ''; r.supplier_phone = ''; }
                r.vendor_id = '';
                r.vendor_name = typed;
            },

            // Thêm dòng: gợi ý sẵn NCC / người giao / thời gian nhập của dòng liền trước.
            addRow(group) {
                const prev = this.rows[this.rows.length - 1];
                this.rows.push(makeRow(group, prev ? {
                    vendor_id: prev.vendor_id, vendor_name: prev.vendor_name, supplier_address: prev.supplier_address, supplier_phone: prev.supplier_phone,
                    deliverer_name: prev.deliverer_name, received_at: prev.received_at,
                } : {}));
            },
            removeRow(r) { this.rows = this.rows.filter((x) => x !== r); },
        };
    });
});

// ── Entry point ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);           // global từ app.js
    window.initAllDatePickers?.(form);      // input.fp-init tĩnh ở tab "Thông tin chung"
    _initCustomerSelect(form);
    _setupTabGuard(form);                   // lỗi ở tab ẩn → chuyển tab + Toast
    _setupRowsGuard(form);                  // chưa có dòng thực phẩm nào
    _setupVisibleErrorToast(form);          // lỗi ở tab đang hiện → Toast + focus (chạy SAU initFormValidation)
    _setupSubmitLoading(form);
});

// Khách hàng / điểm phục vụ: chọn xong tự điền "Địa điểm kiểm tra" theo địa chỉ khách hàng (vẫn sửa tay được).
// Nằm trong panel x-show có thể đang ẩn → không dùng ts-init, khởi tạo thủ công (spec §22.6).
function _initCustomerSelect(form) {
    const el = form.querySelector('#ts-customer_id');
    if (!el || el.tomselect) return;

    createTs(el, {
        placeholder: '— Chọn khách hàng / điểm phục vụ —',
        maxOptions: null,
        onChange(value) {
            const location = form.querySelector('#inspection_location');
            const address = value ? el.querySelector(`option[value="${CSS.escape(value)}"]`)?.dataset.address : '';
            if (location && address) {
                location.value = address;
                location.dispatchEvent(new Event('input', { bubbles: true }));
            }
            el.dispatchEvent(new Event('change', { bubbles: true }));
        },
    });
}

// ── Tab-Aware Submit Guard (spec §17.2) ────────────────────────────────────
function _setupTabGuard(form) {
    let wrapper = null;

    form.addEventListener('submit', (e) => {
        const errors = _collectHiddenErrors(form);
        if (!errors.size) return;

        e.preventDefault();
        e.tabGuarded = true;
        wrapper ??= form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, errors.keys().next().value);
        _toastHiddenErrors(errors);
    }, true);
}

function _collectHiddenErrors(form) {
    const map = new Map();
    for (const field of form.querySelectorAll('[data-req]')) {
        if (field.value.trim()) continue;
        const panel = field.closest('[x-show]');
        if (!panel || panel.style.display !== 'none') continue;
        const tabKey = RE_TAB_XSHOW.exec(panel.getAttribute('x-show') ?? '')?.[1];
        if (!tabKey) continue;
        if (!map.has(tabKey)) map.set(tabKey, { label: panel.dataset.tabLabel ?? tabKey, fields: [] });
        map.get(tabKey).fields.push(_resolveFieldLabel(field));
    }
    return map;
}

// data-label (kèm số dòng) được ưu tiên vì ô trong lưới không có .form-control/.label-text.
function _resolveFieldLabel(field) {
    return field.dataset.label
        ?? field.closest('.form-control')?.querySelector('.label-text')?.textContent.replace(/\s*\*\s*$/, '').trim()
        ?? field.placeholder ?? field.name ?? 'Trường bắt buộc';
}

function _switchAlpineTab(wrapper, tabKey) {
    if (!wrapper) return;
    try {
        const data = window.Alpine?.$data(wrapper);
        if (data?.tab !== undefined) data.tab = tabKey;
    } catch { /* Alpine not ready */ }
}

function _toastHiddenErrors(errors) {
    if (!window.Toast) return;
    const lines = Array.from(errors.values(), ({ label, fields }) => `${label}: ${fields.join(', ')}`);
    Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, { duration: 5000 });
}

// ── Guard riêng: chưa có dòng thực phẩm (conditional required, spec §17.6) ─────
function _setupRowsGuard(form) {
    form.addEventListener('submit', (e) => {
        if (form.querySelector('[name^="details["]')) return;
        e.preventDefault();
        const wrapper = form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, 'fresh');
        window.Toast?.warning('Cần ít nhất một dòng thực phẩm để kiểm thực.', { duration: 4000 });
    }, true);
}

// initFormValidation (bubble) đã tô đỏ ô lỗi; bổ sung Toast + focus ô lỗi đầu tiên ở tab đang hiện (spec §17.5).
// Nếu Tab Guard vừa chuyển tab thì chỉ focus (Toast đã hiện) — chờ Alpine đổi tab xong mới đo vị trí.
function _setupVisibleErrorToast(form) {
    const isVisible = (el) => {
        const panel = el.closest('[x-show]');
        return !panel || panel.style.display !== 'none';
    };

    form.addEventListener('submit', (e) => {
        if (!e.defaultPrevented) return;

        requestAnimationFrame(() => {
            const bad = Array.from(form.querySelectorAll('.input-error, .select-error')).filter(isVisible);
            if (!bad.length) return;

            if (!e.tabGuarded) {
                const labels = [...new Set(bad.map(_resolveFieldLabel))];
                window.Toast?.warning(`Vui lòng nhập đầy đủ:\n${labels.slice(0, 6).join('\n')}${labels.length > 6 ? '\n…' : ''}`, { duration: 5000 });
            }
            const first = bad[0];
            first.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
            first.focus?.({ preventScroll: true });
        });
    });
}

// Chống bấm đúp: bật loading sau khi mọi guard/validation đã chạy xong mà không bị chặn.
function _setupSubmitLoading(form) {
    form.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        const root = form.querySelector('[x-data]');
        const data = root && window.Alpine?.$data(root);
        if (data) data.submitting = true;
    });
}
