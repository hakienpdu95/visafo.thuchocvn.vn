import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-contract-form]';

function _clearField(el) {
    if (el.tomselect) el.tomselect.clear(true);
    else el.value = '';
}

function _ensureTs(el) {
    if (el.tomselect) return;
    requestAnimationFrame(() => {
        if (!el.tomselect) createTs(el, { placeholder: el.dataset.tsPlaceholder });
    });
}

function _setupContractPartyToggle(form) {
    const typeInputs = form.querySelectorAll('[name="type"]');
    const vendorEl   = form.querySelector('#ts-vendor_id');
    const customerEl = form.querySelector('#ts-customer_id');
    if (!typeInputs.length || !vendorEl || !customerEl) return;

    function apply() {
        const checked = form.querySelector('[name="type"]:checked');
        const isInput = !checked || checked.value === 'input';

        vendorEl.required   = isInput;
        customerEl.required = !isInput;

        if (isInput) {
            _clearField(customerEl);
            _ensureTs(vendorEl);
        } else {
            _clearField(vendorEl);
            _ensureTs(customerEl);
        }
    }

    typeInputs.forEach((el) => el.addEventListener('change', apply));
    apply();
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    window.initFormValidation?.(FORM_SEL);
    window.initAllDatePickers?.(form);
    initAllTomSelects(form);
    _setupContractPartyToggle(form);
});
