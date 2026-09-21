import { createTs } from '@shared/tom-select-factory.js';

const NOW_FORMAT = { datetime: { dateFormat: 'Y-m-d H:i:S', altFormat: 'd/m/Y H:i', time_24hr: true }, date: { dateFormat: 'Y-m-d', altFormat: 'd/m/Y' } };

// ── Alpine: TomSelect cho <select> trong hàng lưới (x-ts="<biểu thức giá trị>") ──────
// ── Alpine: Flatpickr cho <input> trong hàng lưới (x-fp / x-fp.datetime="<biểu thức giá trị>") ──
document.addEventListener('alpine:init', () => {
    Alpine.directive('ts', (el, { expression }, { evaluateLater, effect, cleanup }) => {
        const ts = createTs(el, {
            plugins: [],
            onChange() { el.dispatchEvent(new Event('change', { bubbles: true })); },
        });
        if (!ts) return;
        const read = evaluateLater(expression);
        effect(() => read((v) => ts.setValue(v ?? '', true)));
        cleanup(() => ts.destroy());
    });

    Alpine.directive('fp', (el, { expression, modifiers }, { evaluateLater, effect, cleanup }) => {
        const datetime = modifiers.includes('datetime');
        const init = datetime ? window.initDateTimePicker : window.initDatePicker;
        if (!init) return;

        const inst = init(el, {
            ...NOW_FORMAT[datetime ? 'datetime' : 'date'],
            altInput: true, allowInput: false, disableMobile: true,
            onChange: (_s, str) => { el.value = str; el.dispatchEvent(new Event('input', { bubbles: true })); },
        });
        const fp = Array.isArray(inst) ? inst[0] : inst;
        const read = evaluateLater(expression);
        effect(() => read((v) => fp.setDate(v || null, false)));
        cleanup(() => fp.destroy());
    });
});
