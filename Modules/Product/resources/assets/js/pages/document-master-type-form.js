import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-document-master-type-form]';

const VI_MAP = Object.freeze({
    à:'a', á:'a', ả:'a', ã:'a', ạ:'a',
    ă:'a', ằ:'a', ắ:'a', ẳ:'a', ẵ:'a', ặ:'a',
    â:'a', ầ:'a', ấ:'a', ẩ:'a', ẫ:'a', ậ:'a',
    è:'e', é:'e', ẻ:'e', ẽ:'e', ẹ:'e',
    ê:'e', ề:'e', ế:'e', ể:'e', ễ:'e', ệ:'e',
    ì:'i', í:'i', ỉ:'i', ĩ:'i', ị:'i',
    ò:'o', ó:'o', ỏ:'o', õ:'o', ọ:'o',
    ô:'o', ồ:'o', ố:'o', ổ:'o', ỗ:'o', ộ:'o',
    ơ:'o', ờ:'o', ớ:'o', ở:'o', ỡ:'o', ợ:'o',
    ù:'u', ú:'u', ủ:'u', ũ:'u', ụ:'u',
    ư:'u', ừ:'u', ứ:'u', ử:'u', ữ:'u', ự:'u',
    ỳ:'y', ý:'y', ỷ:'y', ỹ:'y', ỵ:'y',
    đ:'d',
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _setupCodeAutoFill(form);
});

function _setupCodeAutoFill(form) {
    const nameInput = form.querySelector('[name="name"]');
    const codeInput = form.querySelector('[name="code"]');
    if (!nameInput || !codeInput) return;

    // Edit: code đã có giá trị → locked = true từ đầu, không bao giờ auto-fill
    let locked = codeInput.value.trim() !== '';

    codeInput.addEventListener('input', () => { locked = codeInput.value.trim() !== ''; }, { passive: true });
    codeInput.addEventListener('change', () => { if (!codeInput.value.trim()) locked = false; }, { passive: true });
    nameInput.addEventListener('input', () => { if (!locked) codeInput.value = _toCode(nameInput.value); }, { passive: true });
}

function _toCode(str) {
    let out = '';
    for (const ch of str.toLowerCase()) out += VI_MAP[ch] ?? ch;
    return out.replace(/[^a-z0-9\s_]/g, '').trim().replace(/\s+/g, '_').replace(/_{2,}/g, '_');
}
