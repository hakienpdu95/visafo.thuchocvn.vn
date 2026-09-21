import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';
import './_row-directives.js';

const FORM_SEL = '[data-step3-form]';
const nowHm = () => new Date().toTimeString().slice(0, 5);
const asBool = (v, d = true) => (v === undefined || v === null || v === '') ? d : (v === true || v === '1' || v === 1);

let locationTs = null; // combobox "Địa điểm" — gợi ý theo cơ sở đang chọn, cho phép gõ tên mới

function refreshLocations(cfg, customerId, keep = '') {
    if (!locationTs) return;
    locationTs.clear(true);
    locationTs.clearOptions();
    (cfg.locations[customerId] ?? []).forEach((name) => locationTs.addOption({ value: name, text: name }));
    if (keep) { locationTs.addOption({ value: keep, text: keep }); locationTs.setValue(keep, true); }
}

// ── Alpine: form Sổ kiểm thực Bước 3 ──────────────────────────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.data('step3Form', (cfg) => {
        let seq = Math.max(0, ...cfg.rows.map((r) => r.n));
        const make = (src = {}) => ({
            n: src.n ?? ++seq,
            id: src.id ?? '',
            step2_detail_id: src.step2_detail_id ?? '',
            menu_dish_id: src.menu_dish_id ?? '',
            source: src.source ?? '',
            meal_time: src.meal_time ?? cfg.defaultMeal,
            dish_name: src.dish_name ?? '',
            quantity: src.quantity ?? '',
            portion_time: src.portion_time ?? '',
            eat_time: src.eat_time ?? '',
            equipment_used: src.equipment_used ?? '',
            sensory_eval: asBool(src.sensory_eval),
            action_taken: src.action_taken ?? '',
        });

        return {
            meals: cfg.meals,
            equipment: cfg.equipment,
            rows: cfg.rows.map((r) => make(r)),
            customerId: cfg.customerId,
            syncing: false,
            syncMessage: '',
            syncOk: true,
            submitting: false,

            get failedCount() { return this.rows.filter((r) => this.isFailed(r)).length; },
            get totalServings() { return this.rows.reduce((sum, r) => sum + (Number(r.quantity) || 0), 0); },

            mealLabel(v) { return this.meals.find((m) => m.value === v)?.text ?? v; },
            // Dòng kế thừa từ Bước 2 / Thực đơn: tên món, ca/bữa, số suất chỉ đọc.
            locked(r) { return r.step2_detail_id !== '' || r.menu_dish_id !== ''; },
            isFailed(r) { return !r.sensory_eval; },
            onCheckChange(r) { if (!this.isFailed(r)) r.action_taken = ''; },

            // Giờ bắt đầu ăn không được trước giờ chia xong ("HH:MM" so sánh chuỗi được).
            timeError(r) { return !!(r.portion_time && r.eat_time && r.eat_time < r.portion_time); },

            setNow(r, field) { r[field] = nowHm(); },

            // Sao chép giá trị của dòng i xuống mọi dòng bên dưới (mô phỏng dấu " / chữ "ut" trên sổ giấy).
            copyDown(i, field) {
                const value = this.rows[i]?.[field] ?? '';
                for (let k = i + 1; k < this.rows.length; k++) this.rows[k][field] = value;
            },
            // Áp dụng giá trị của dòng 1 cho tất cả các dòng.
            applyAll(field) {
                if (!this.rows.length) return;
                if (!this.rows[0][field]) { window.Toast?.warning('Hãy nhập giá trị ở dòng 1 trước khi áp dụng cho tất cả.', { duration: 3500 }); return; }
                this.copyDown(0, field);
            },

            addRow() { this.rows.push(make()); },
            removeRow(r) { this.rows = this.rows.filter((x) => x !== r); },

            // Đổi khách hàng: gợi ý địa điểm theo cơ sở mới, bỏ các dòng đã kế thừa của cơ sở cũ.
            onCustomerChange(id) {
                if (id === this.customerId) return;
                this.customerId = id;
                this.rows = this.rows.filter((r) => !this.locked(r));
                this.syncMessage = '';
                refreshLocations(cfg, id);
            },

            async syncSource() {
                const form = this.$root.closest('form') ?? this.$root;
                const date = form.querySelector('#fp-inspection_date')?.value;
                const meal = form.querySelector('#ts-sync_meal')?.value;
                this.syncOk = false;

                if (!this.customerId) { this.syncMessage = 'Vui lòng chọn khách hàng trước khi đồng bộ.'; return; }
                if (!date) { this.syncMessage = 'Vui lòng chọn ngày kiểm tra.'; return; }
                if (!meal) { this.syncMessage = 'Vui lòng chọn bữa ăn cần đồng bộ.'; return; }

                this.syncing = true;
                this.syncMessage = '';
                try {
                    const qs = new URLSearchParams({ customer_id: this.customerId, date, meal_time: meal });
                    const res = await fetch(cfg.syncUrl + '?' + qs, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();

                    if (!data.found) {
                        this.syncMessage = 'Chưa có món nào cho khách hàng, ngày và bữa ăn này — cần có Sổ Bước 2 (món đạt) hoặc Thực đơn tương ứng.';
                        return;
                    }

                    // Thay các dòng đã kế thừa của cùng bữa ăn; giữ dòng nhập tay và các bữa khác.
                    this.rows = this.rows.filter((r) => !(this.locked(r) && r.meal_time === meal));
                    data.data.forEach((d) => this.rows.push(make({
                        step2_detail_id: d.step2_detail_id, menu_dish_id: d.menu_dish_id, source: d.source,
                        meal_time: d.meal_time, dish_name: d.dish_name, quantity: d.quantity,
                    })));
                    this.syncOk = true;
                    this.syncMessage = `Đã đồng bộ ${data.data.length} món (${data.from_step2} từ Bước 2, ${data.from_menu} từ Thực đơn).`;
                } catch (e) {
                    console.error('[step3] sync source failed', e);
                    this.syncMessage = 'Không đồng bộ được dữ liệu. Vui lòng thử lại.';
                } finally {
                    this.syncing = false;
                }
            },
        };
    });
});

// ── Entry point ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);        // global từ app.js
    window.initAllDatePickers?.(form);   // input.fp-init
    initAllTomSelects(form);             // select.ts-init (cơ sở, bữa ăn đồng bộ)
    _initCustomerAndLocation(form);
    _setupRowsGuard(form);
    _setupTimeOrderGuard(form);
    _setupVisibleErrorToast(form);
    _setupSubmitLoading(form);
});

function _initCustomerAndLocation(form) {
    const cfg = JSON.parse(form.dataset.step3Config ?? '{}');
    const root = form.querySelector('[x-data]');
    const customerEl = form.querySelector('#ts-customer_id');
    const locationEl = form.querySelector('#ts-location_name');

    if (locationEl && !locationEl.tomselect) {
        locationTs = createTs(locationEl, {
            placeholder: 'Chọn hoặc gõ tên bếp ăn / địa điểm',
            create: (input) => ({ value: input.trim(), text: input.trim() }),
            createFilter: (input) => input.trim().length > 1,
            persist: false,
            render: { option_create: (data, escape) => '<div class="create">Thêm mới: <strong>' + escape(data.input) + '</strong>&hellip;</div>' },
        });
        refreshLocations(cfg, customerEl?.value ?? '', locationEl.dataset.initial ?? '');
    }

    customerEl?.tomselect?.on('change', (value) => {
        const data = root && window.Alpine?.$data(root);
        data?.onCustomerChange(value || '');
    });
}

// Chưa có món nào → chặn submit + Toast (conditional required, spec §17.6).
function _setupRowsGuard(form) {
    form.addEventListener('submit', (e) => {
        if (form.querySelector('[name^="details["]')) return;
        e.preventDefault();
        window.Toast?.warning('Cần ít nhất một món ăn để kiểm thực.', { duration: 4000 });
    }, true);
}

// Giờ bắt đầu ăn trước giờ chia xong → chặn submit + Toast nêu số dòng (đúng theo Mẫu số 3).
function _setupTimeOrderGuard(form) {
    form.addEventListener('submit', (e) => {
        const root = form.querySelector('[x-data]');
        const data = root && window.Alpine?.$data(root);
        const idx = data ? data.rows.findIndex((r) => data.timeError(r)) : -1;
        if (idx < 0) return;

        e.preventDefault();
        window.Toast?.warning(`Giờ bắt đầu ăn không được trước giờ chia xong (dòng ${idx + 1}).`, { duration: 5000 });
        const el = form.querySelector(`[name="details[${data.rows[idx].n}][eat_time]"]`);
        el?.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
        el?.focus?.({ preventScroll: true });
    }, true);
}

// initFormValidation (bubble) đã tô đỏ ô lỗi; bổ sung Toast (kèm số dòng) + focus ô lỗi đầu tiên.
function _setupVisibleErrorToast(form) {
    form.addEventListener('submit', (e) => {
        if (!e.defaultPrevented) return;
        requestAnimationFrame(() => {
            const bad = Array.from(form.querySelectorAll('.input-error, .select-error, .textarea-error'));
            if (!bad.length) return;
            const labels = [...new Set(bad.map((el) => el.dataset.label
                ?? el.closest('.form-control')?.querySelector('.label-text')?.textContent.replace(/\s*\*\s*$/, '').trim()
                ?? el.name))];
            window.Toast?.warning(`Vui lòng nhập đầy đủ:\n${labels.slice(0, 6).join('\n')}`, { duration: 5000 });
            bad[0].scrollIntoView?.({ behavior: 'smooth', block: 'center' });
            bad[0].focus?.({ preventScroll: true });
        });
    });
}

function _setupSubmitLoading(form) {
    form.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        const root = form.querySelector('[x-data]');
        const data = root && window.Alpine?.$data(root);
        if (data) data.submitting = true;
    });
}
