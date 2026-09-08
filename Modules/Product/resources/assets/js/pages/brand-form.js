const FORM_SEL = '[data-brand-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupLogoUpload(form);
});

function _setupLogoUpload(form) {
    const pondEl = form.querySelector('[data-logo-pond]');
    if (!pondEl || !window.initFilePondUpload) return;

    const contextId = pondEl.dataset.logoContextId;
    const uuidInput = form.querySelector('[data-logo-uuid]');

    window.initFilePondUpload(pondEl, {
        collection: 'logo',
        ...(contextId ? { contextType: 'brand', contextId } : {}),
        ...(uuidInput ? { bindTo: uuidInput } : {}),
        labelIdle: 'Kéo thả logo vào đây hoặc <span class="filepond--label-action">Duyệt file</span>',
    });
}
