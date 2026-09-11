import { registerDocumentUploadFormAlpine, createDocumentUploadModal } from '@shared/document-upload-modal.js';
import { initComplianceDocumentsTable } from '@shared/compliance-documents-table.js';

registerDocumentUploadFormAlpine();

const documentModal = createDocumentUploadModal({
    modalId:           'addDocumentModal',
    selectSel:         '#ts-document_master_type_id',
    issueFieldId:      'fp-issue_date',
    expirationFieldId: 'fp-expiration_date',
});

window.openAddDocumentModal  = () => documentModal.openCreate();
window.openEditDocumentModal = (doc) => documentModal.openEdit(doc);

const TABLES = {
    'nl-pl-table':   null,
    'nl-attp-table': null,
    'nl-ns-table':   null,
};

function _initTable(tableId) {
    if (!(tableId in TABLES)) return;
    if (TABLES[tableId]) {
        TABLES[tableId].redraw(true);
        return;
    }
    TABLES[tableId] = initComplianceDocumentsTable(tableId, window.openEditDocumentModal);
}

window.onInternalComplianceTabShown = function (tableId) {
    _initTable(tableId);
};

document.addEventListener('DOMContentLoaded', () => {
    const initialTable = document.querySelector('[data-initial-tab]') ? 'nl-pl-table' : null;
    if (initialTable) _initTable(initialTable);
});
