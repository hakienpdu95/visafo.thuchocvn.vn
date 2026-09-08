import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-inbound-receipt-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    window.initAllDatePickers?.(form);
    initAllTomSelects(form);
});
