import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-farming-log-form]';

window.initAllTomSelects = initAllTomSelects;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    window.initFormValidation?.(FORM_SEL);
    window.initAllDatePickers?.(form);
    initAllTomSelects(form);

    const dateInput = form.querySelector('#fp-activity_date');
    form.querySelector('#activity-date-now-btn')?.addEventListener('click', () => {
        dateInput?._flatpickr?.setDate(new Date(), true);
    });
});
