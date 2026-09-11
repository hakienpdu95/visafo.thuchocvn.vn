import { createTs } from './tom-select-factory.js';

export function registerDocumentUploadFormAlpine() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('documentUploadForm', (types, initialId) => ({
            types,
            selectedId: initialId,
            get selected() {
                return this.types.find((t) => t.id === this.selectedId) ?? { has_expiration_date: true, has_issue_place: true };
            },
        }));
    });
}

function _pinFixed(panel, anchor, extra = {}) {
    const rect = anchor.getBoundingClientRect();
    Object.assign(panel.style, {
        position: 'fixed',
        top:      (rect.bottom + 4) + 'px',
        left:     rect.left + 'px',
        right:    'auto',
        margin:   0,
        ...extra,
    });
}

export function createDocumentUploadModal({ modalId, selectSel, issueFieldId, expirationFieldId }) {
    const dateFieldIds = [issueFieldId, expirationFieldId];

    function _initDocumentTypeSelect(modal) {
        const el = modal.querySelector(selectSel);
        if (!el || el.tomselect) return;

        const ts = createTs(el, {
            dropdownParent: '#' + modalId,
            placeholder:    '— Chọn loại giấy tờ —',
        });
        if (!ts) return;

        ts.on('dropdown_open', () => {
            _pinFixed(ts.dropdown, ts.control, { width: ts.control.getBoundingClientRect().width + 'px' });
        });
    }

    function _selectedValidityMonths(modal) {
        const form = modal.querySelector('form[x-data]');
        if (!form || !window.Alpine) return null;
        const data = window.Alpine.$data(form);
        const type = data?.types.find((t) => t.id === data.selectedId);
        return type?.default_validity_months ?? null;
    }

    function _recalcExpiration(modal, issueEl, expirationEl) {
        if (expirationEl._expirationLocked || expirationEl._suppressRecalc) return;
        const selected = issueEl._flatpickr?.selectedDates ?? [];
        if (!selected.length) return;

        const months = _selectedValidityMonths(modal);
        if (!months) return;

        const target = new Date(selected[0]);
        target.setMonth(target.getMonth() + months);
        expirationEl._flatpickr.setDate(target, false);
    }

    function _initDateFields(modal) {
        if (!window.initDatePicker) return;

        const issueEl = modal.querySelector('#' + dateFieldIds[0]);
        const expirationEl = modal.querySelector('#' + dateFieldIds[1]);
        if (!issueEl || !expirationEl) return;

        const positionOpts = {
            dateFormat:    'Y-m-d',
            altInput:      true,
            altFormat:     'd/m/Y',
            allowInput:    false,
            disableMobile: true,
            appendTo:      modal,
            position: (instance) => {
                _pinFixed(instance.calendarContainer, instance.altInput || instance.input);
            },
        };

        expirationEl._expirationLocked = !!expirationEl.value;

        if (!expirationEl._flatpickr) {
            window.initDatePicker(expirationEl, {
                ...positionOpts,
                onChange: () => { expirationEl._expirationLocked = true; },
            });
        }

        if (!issueEl._flatpickr) {
            window.initDatePicker(issueEl, {
                ...positionOpts,
                onChange: () => _recalcExpiration(modal, issueEl, expirationEl),
            });
        }

        const select = modal.querySelector(selectSel);
        if (select && !select._expirationRecalcBound) {
            select._expirationRecalcBound = true;
            select.addEventListener('change', () => _recalcExpiration(modal, issueEl, expirationEl));
        }
    }

    function _initModalWidgets(modal) {
        document.activeElement?.blur();
        _initDocumentTypeSelect(modal);
        _initDateFields(modal);
    }

    function _setModalMode(modal, doc) {
        const form = modal.querySelector('form');
        const methodEl = form.querySelector('input[name="_method"]');
        const idEl = form.querySelector('input[name="_document_id"]');
        const titleEl = modal.querySelector('#documentModalTitle');
        const submitEl = modal.querySelector('#documentModalSubmit');

        if (doc) {
            form.action = form.dataset.updateUrlTemplate.replace('__ID__', doc.id);
            if (methodEl) methodEl.value = 'PUT';
            if (idEl) idEl.value = doc.id;
            if (titleEl) titleEl.textContent = 'Sửa hồ sơ';
            if (submitEl) submitEl.textContent = 'Lưu thay đổi';
        } else {
            form.action = form.dataset.createUrl;
            if (methodEl) methodEl.value = '';
            if (idEl) idEl.value = '';
            if (titleEl) titleEl.textContent = 'Tải lên hồ sơ mới';
            if (submitEl) submitEl.textContent = 'Lưu hồ sơ';
        }
    }

    function _setCurrentFileInfo(modal, fileUrl) {
        const infoEl = modal.querySelector('#documentCurrentFileInfo');
        const linkEl = modal.querySelector('#documentCurrentFileLink');
        if (!infoEl || !linkEl) return;

        if (fileUrl) {
            linkEl.href = fileUrl;
            infoEl.hidden = false;
        } else {
            linkEl.href = '#';
            infoEl.hidden = true;
        }
    }

    function _resetDocumentForm(modal, preselectId) {
        modal.querySelector('[name="document_number"]').value = '';
        modal.querySelector('[name="issued_by"]').value = '';

        const issueEl = modal.querySelector('#' + dateFieldIds[0]);
        const expirationEl = modal.querySelector('#' + dateFieldIds[1]);
        issueEl._flatpickr?.clear();
        expirationEl._flatpickr?.clear();
        expirationEl._expirationLocked = false;

        const fileEl = modal.querySelector('input[type="file"][name="file"]');
        if (fileEl) fileEl.value = '';
        _setCurrentFileInfo(modal, null);

        const select = modal.querySelector(selectSel);
        if (preselectId && select?.tomselect) select.tomselect.setValue(preselectId, false);
        else select?.tomselect?.clear(false);
    }

    function _fillDocumentForm(modal, doc) {
        const issueEl = modal.querySelector('#' + dateFieldIds[0]);
        const expirationEl = modal.querySelector('#' + dateFieldIds[1]);

        expirationEl._suppressRecalc = true;

        issueEl._flatpickr?.setDate(doc.issue_date ?? null, false);
        expirationEl._flatpickr?.setDate(doc.expiration_date ?? null, false);

        modal.querySelector('[name="document_number"]').value = doc.document_number ?? '';
        modal.querySelector('[name="issued_by"]').value = doc.issued_by ?? '';
        _setCurrentFileInfo(modal, doc.file_url);

        const select = modal.querySelector(selectSel);
        if (select?.tomselect) select.tomselect.setValue(doc.document_master_type_id, false);
        else if (select) select.value = doc.document_master_type_id;

        expirationEl._expirationLocked = false;
        expirationEl._suppressRecalc = false;
    }

    function openCreate(preselectId = '') {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        _setModalMode(modal, null);
        modal.showModal();
        document.activeElement?.blur();
        requestAnimationFrame(() => {
            _initModalWidgets(modal);
            _resetDocumentForm(modal, preselectId);
        });
    }

    function openEdit(doc) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        _setModalMode(modal, doc);
        modal.showModal();
        document.activeElement?.blur();
        requestAnimationFrame(() => {
            _initModalWidgets(modal);
            _fillDocumentForm(modal, doc);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        if (modal.dataset.autoopen === '1') {
            modal.showModal();
            document.activeElement?.blur();
        }

        if (modal.open) requestAnimationFrame(() => _initModalWidgets(modal));
    });

    return { openCreate, openEdit };
}
