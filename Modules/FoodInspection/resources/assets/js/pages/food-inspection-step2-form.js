import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';
import './_row-directives.js';

const FORM_SEL = '[data-step2-form]';
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

// ── Alpine: form Sổ kiểm thực Bước 2 ──────────────────────────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.data('step2Form', (cfg) => {
        let seq = Math.max(0, ...cfg.rows.map((r) => r.n));
        const make = (src = {}) => ({
            n: src.n ?? ++seq,
            id: src.id ?? '',
            menu_dish_id: src.menu_dish_id ?? '',
            meal_time: src.meal_time ?? cfg.defaultMeal,
            dish_name: src.dish_name ?? '',
            main_ingredients: src.main_ingredients ?? '',
            quantity: src.quantity ?? '',
            prep_time: src.prep_time ?? '',
            cook_time: src.cook_time ?? '',
            hygiene_personnel: asBool(src.hygiene_personnel),
            hygiene_equipment: asBool(src.hygiene_equipment),
            hygiene_area: asBool(src.hygiene_area),
            sensory_eval: asBool(src.sensory_eval),
            action_taken: src.action_taken ?? '',
        });

        return {
            meals: cfg.meals,
            rows: cfg.rows.map((r) => make(r)),
            customerId: cfg.customerId,
            syncing: false,
            syncMessage: '',
            syncOk: true,
            submitting: false,

            get failedCount() { return this.rows.filter((r) => this.isFailed(r)).length; },
            get totalServings() { return this.rows.reduce((sum, r) => sum + (Number(r.quantity) || 0), 0); },

            mealLabel(v) { return this.meals.find((m) => m.value === v)?.text ?? v; },
            isFailed(r) { return !(r.hygiene_personnel && r.hygiene_equipment && r.hygiene_area && r.sensory_eval); },
            onCheckChange(r) { if (!this.isFailed(r)) r.action_taken = ''; },

            // Bấm icon đồng hồ (hoặc nhấp đúp ô giờ) → điền giờ:phút hiện tại.
            setNow(r, field) { r[field] = nowHm(); },

            addRow() { this.rows.push(make()); },
            removeRow(r) { this.rows = this.rows.filter((x) => x !== r); },

            // Đổi cơ sở: gợi ý địa điểm theo cơ sở mới và bỏ các dòng đã đồng bộ từ thực đơn của cơ sở cũ.
            onCustomerChange(id) {
                if (id === this.customerId) return;
                this.customerId = id;
                this.rows = this.rows.filter((r) => r.menu_dish_id === '');
                this.syncMessage = '';
                refreshLocations(cfg, id);
            },

            async syncMenu() {
                const form = this.$root.closest('form') ?? this.$root;
                const date = form.querySelector('#fp-inspection_date')?.value;
                const meal = form.querySelector('#ts-sync_meal')?.value;
                this.syncOk = false;

                if (!this.customerId) { this.syncMessage = 'Vui lòng chọn cơ sở trước khi đồng bộ thực đơn.'; return; }
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
                        this.syncMessage = 'Chưa có thực đơn của cơ sở này cho ngày và bữa ăn đã chọn.';
                        return;
                    }

                    // Thay các dòng đã đồng bộ của cùng bữa ăn; giữ nguyên dòng nhập tay và các bữa khác.
                    this.rows = this.rows.filter((r) => !(r.menu_dish_id !== '' && r.meal_time === meal));
                    data.data.forEach((d) => this.rows.push(make({
                        menu_dish_id: d.menu_dish_id, meal_time: d.meal_time, dish_name: d.dish_name,
                        main_ingredients: d.main_ingredients, quantity: d.servings,
                    })));
                    this.syncOk = true;
                    this.syncMessage = `Đã đồng bộ ${data.data.length} món của ${this.mealLabel(meal).toLowerCase()}.`;
                } catch (e) {
                    console.error('[step2] sync menu failed', e);
                    this.syncMessage = 'Không đồng bộ được thực đơn. Vui lòng thử lại.';
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
    _setupVisibleErrorToast(form);
    _setupSubmitLoading(form);
});

function _initCustomerAndLocation(form) {
    const cfg = JSON.parse(form.dataset.step2Config ?? '{}');
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
