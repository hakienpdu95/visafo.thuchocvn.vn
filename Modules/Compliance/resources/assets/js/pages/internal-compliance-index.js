import { registerDocumentUploadFormAlpine, createDocumentUploadModal } from '@shared/document-upload-modal.js';

registerDocumentUploadFormAlpine();

const documentModal = createDocumentUploadModal({
    modalId:           'addDocumentModal',
    selectSel:         '#ts-document_master_type_id',
    issueFieldId:      'fp-issue_date',
    expirationFieldId: 'fp-expiration_date',
});

window.openAddDocumentModal = function (facilityId, group) {
    const modal = document.getElementById('addDocumentModal');
    if (!modal) return;
    const form = modal.querySelector('form');
    form.dataset.createUrl = form.dataset.storeUrlTemplate.replace('__FID__', facilityId);
    const targetField = form.querySelector('[name="_target_facility_id"]');
    if (targetField) targetField.value = facilityId;
    documentModal.openCreate('', group);
};

window.openEditDocumentModal = function (doc) {
    const modal = document.getElementById('addDocumentModal');
    if (!modal) return;
    const form = modal.querySelector('form');
    form.dataset.updateUrlTemplate = form.dataset.updateUrlTemplateRaw.replace('__FID__', doc.facility_id);
    const targetField = form.querySelector('[name="_target_facility_id"]');
    if (targetField) targetField.value = doc.facility_id;
    documentModal.openEdit(doc);
};

window.openViewFilesModal = function (doc) {
    const modal = document.getElementById('viewFilesModal');
    const content = document.getElementById('viewFilesModalContent');
    if (!modal || !content || !window.Alpine) return;

    const data = window.Alpine.$data(content);
    data.docName = doc.type_name;
    data.media = doc.media ?? [];
    modal.showModal();
};

let pendingDeleteUrl = null;

window.internalComplianceDeleteConfirm = function (url, name) {
    pendingDeleteUrl = url;
    const nameEl = document.getElementById('deleteItemName');
    if (nameEl) nameEl.textContent = name;
    document.getElementById('deleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                    'Content-Type':     'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });

            if (res.ok) {
                window.location.reload();
            } else {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
                this.disabled    = false;
                this.textContent = 'Xóa';
            }
        } catch (e) {
            alert('Lỗi kết nối. Vui lòng thử lại.');
            this.disabled    = false;
            this.textContent = 'Xóa';
        }
    });
});
