import { registerDocumentUploadFormAlpine, createDocumentUploadModal } from '@shared/document-upload-modal.js';
import { initComplianceDocumentsTable } from '@shared/compliance-documents-table.js';

registerDocumentUploadFormAlpine();

const documentModal = createDocumentUploadModal({
    modalId:           'addDocumentModal',
    selectSel:         '#ts-document_master_type_id',
    issueFieldId:      'fp-issue_date',
    expirationFieldId: 'fp-expiration_date',
});

window.openAddDocumentModal = (preselectId) => documentModal.openCreate(preselectId);
window.openEditDocumentModal = (doc) => documentModal.openEdit(doc);

document.addEventListener('DOMContentLoaded', () => {
    initComplianceDocumentsTable('partner-product-documents-table', window.openEditDocumentModal);
});
