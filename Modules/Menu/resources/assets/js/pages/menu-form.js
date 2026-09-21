import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-menu-form]';

// ── Alpine: lưới món ăn của thực đơn ────────────────────────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.data('menuDishes', (cfg) => {
        let seq = Math.max(0, ...cfg.dishes.map((d) => d.n));
        const make = (src = {}) => ({
            n: src.n ?? ++seq,
            id: src.id ?? '',
            dish_name: src.dish_name ?? '',
            main_ingredients: src.main_ingredients ?? '',
            servings: src.servings ?? '',
        });

        return {
            dishes: cfg.dishes.map((d) => make(d)),
            submitting: false,
            get totalServings() { return this.dishes.reduce((sum, d) => sum + (Number(d.servings) || 0), 0); },
            addDish() { this.dishes.push(make()); },
            removeDish(d) { this.dishes = this.dishes.filter((x) => x !== d); },
        };
    });
});

// ── Entry point ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);        // global từ app.js
    window.initAllDatePickers?.(form);   // input.fp-init
    initAllTomSelects(form);             // select.ts-init
    _setupDishesGuard(form);
    _setupVisibleErrorToast(form);
    _setupSubmitLoading(form);
});

// Chưa có món nào → chặn submit + Toast (conditional required, spec §17.6).
function _setupDishesGuard(form) {
    form.addEventListener('submit', (e) => {
        if (form.querySelector('[name^="dishes["]')) return;
        e.preventDefault();
        window.Toast?.warning('Thực đơn cần ít nhất một món ăn.', { duration: 4000 });
    }, true);
}

// initFormValidation (bubble) đã tô đỏ ô lỗi; bổ sung Toast + focus ô lỗi đầu tiên.
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
        const root = form.querySelector('[x-data]') ?? form.closest('[x-data]');
        const data = root && window.Alpine?.$data(root);
        if (data) data.submitting = true;
    });
}
