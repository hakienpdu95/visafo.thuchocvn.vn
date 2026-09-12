import { registerDocumentUploadFormAlpine, createDocumentUploadModal } from '@shared/document-upload-modal.js';
import { initComplianceDocumentsTable } from '@shared/compliance-documents-table.js';

registerDocumentUploadFormAlpine();

const documentModal = createDocumentUploadModal({
    modalId:           'addDocumentModal',
    selectSel:         '#ts-document_master_type_id',
    issueFieldId:      'fp-issue_date',
    expirationFieldId: 'fp-expiration_date',
});

function _escHtml(v) {
    if (v == null) return '';
    return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Modal dùng chung cho Khối 1 (Công ty) và mọi cơ sở ở Khối 2 — mỗi lần mở
// cần trỏ lại đúng facility (store/update URL + hidden field cho redisplay lỗi).
window.openAddDocumentModal = function (facilityId) {
    const modal = document.getElementById('addDocumentModal');
    if (!modal) return;
    const form = modal.querySelector('form');
    form.dataset.createUrl = form.dataset.storeUrlTemplate.replace('__FID__', facilityId);
    const targetField = form.querySelector('[name="_target_facility_id"]');
    if (targetField) targetField.value = facilityId;
    documentModal.openCreate('');
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

const TABLES = {};

function _initDocTable(tableId) {
    if (!tableId || !document.getElementById(tableId)) return;
    if (TABLES[tableId]) {
        TABLES[tableId].redraw(true);
        return;
    }
    TABLES[tableId] = initComplianceDocumentsTable(tableId, window.openEditDocumentModal, { showGroup: true });
}

window.onFacilityAccordionOpen = function (tableId) {
    _initDocTable(tableId);
};

function _initEmployeeRiskTable() {
    const el = document.getElementById('employee-risk-table');
    if (!el || !window.initTabulator) return;

    const rows = JSON.parse(el.dataset.rows || '[]');

    const columns = [
        {
            title: 'Nhân viên', field: 'full_name', minWidth: 180, sorter: 'string',
            formatter: (cell) => {
                const d = cell.getRow().getData();
                const name = _escHtml(d.full_name);
                return d.edit_url ? '<a href="' + _escHtml(d.edit_url) + '" class="link link-primary">' + name + '</a>' : name;
            },
        },
        {
            title: 'Vị trí', field: 'job_title', minWidth: 140,
            formatter: (cell) => _escHtml(cell.getValue() || '—'),
        },
        {
            title: 'Giấy khám sức khỏe', field: 'health_status_label', width: 160, headerSort: false,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                return '<span class="badge ' + _escHtml(d.health_status_badge) + ' badge-sm">' + _escHtml(d.health_status_label) + '</span>';
            },
        },
        {
            title: 'Chứng nhận ATTP', field: 'attp_status_label', width: 160, headerSort: false,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                return '<span class="badge ' + _escHtml(d.attp_status_badge) + ' badge-sm">' + _escHtml(d.attp_status_label) + '</span>';
            },
        },
    ];

    window.initTabulator('#employee-risk-table', columns, rows, {
        pagination:             true,
        paginationSize:         10,
        paginationSizeSelector: [10, 25, 50],
        paginationCounter:      'rows',
        placeholder: '<div class="py-6 text-center text-sm text-base-content/40">Không có nhân sự nào cần chú ý.</div>',
    });
}

document.addEventListener('DOMContentLoaded', () => {
    _initDocTable('hq-doc-table');
    _initEmployeeRiskTable();

    // Nếu redirect kèm #facility-{id} (sau khi thêm/sửa/xóa hồ sơ) hoặc lỗi
    // validation redisplay — mở sẵn đúng bảng của cơ sở đó.
    const hash = (window.location.hash || '').replace('#facility-', '');
    if (hash) _initDocTable('facility-doc-table-' + hash);
});
