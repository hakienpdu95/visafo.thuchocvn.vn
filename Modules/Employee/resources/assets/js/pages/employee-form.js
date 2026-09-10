import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-employee-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _initDepartmentSelect(form);
    _setupHealthRecordForm(document);
});

function _initDepartmentSelect(form) {
    const el = form.querySelector('#ts-department_ids');
    if (!el) return;
    createTs(el, { plugins: ['remove_button'], placeholder: 'Chọn phòng ban...' });
}

function _setupHealthRecordForm(root) {
    const typeEl = root.querySelector('#ts-record_type');
    if (!typeEl) return;

    const healthField = root.querySelector('#field-health-check');
    const attpField    = root.querySelector('#field-attp-training');

    const toggle = (val) => {
        const isHealthCheck = val === 'health_check';
        healthField?.classList.toggle('hidden', !isHealthCheck);
        attpField?.classList.toggle('hidden', isHealthCheck);
    };

    initAllTomSelects(typeEl.closest('form') ?? root);
    toggle(typeEl.value);

    if (typeEl.tomselect) {
        typeEl.tomselect.on('change', toggle);
    } else {
        typeEl.addEventListener('change', () => toggle(typeEl.value));
    }

    const healthForm = typeEl.closest('form');
    window.initAllDatePickers?.(healthForm);
}
