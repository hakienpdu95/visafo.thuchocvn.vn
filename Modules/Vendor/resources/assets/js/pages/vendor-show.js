import { registerDocumentUploadFormAlpine, createDocumentUploadModal } from '@shared/document-upload-modal.js';
import { initComplianceDocumentsTable } from '@shared/compliance-documents-table.js';

registerDocumentUploadFormAlpine();

const documentModal = createDocumentUploadModal({
    modalId:           'addDocumentModal',
    selectSel:         '#ts-document_master_type_id',
    issueFieldId:      'fp-issue_date',
    expirationFieldId: 'fp-expiration_date',
});

window.openAddDocumentModal = () => documentModal.openCreate();
window.openEditDocumentModal = (doc) => documentModal.openEdit(doc);

function _escHtml(v) {
    if (v == null) return '';
    return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function _emptyOr(value, html) {
    return value ? html : '<span class="text-base-content/25 text-xs">—</span>';
}

let documentsTable = null;
let partnerProductsTable = null;

function _initPartnerProductsTable() {
    const el = document.getElementById('vendor-partner-products-table');
    if (!el || !window.initTabulator || partnerProductsTable) return;

    const rows = JSON.parse(el.dataset.rows || '[]');

    const columns = [
        { title: 'Tên hàng (NCC kê khai)', field: 'name', minWidth: 180, sorter: 'string' },
        {
            title: 'Mã hàng NCC', field: 'vendor_sku', width: 130,
            formatter: (cell) => '<span class="font-mono">' + (_escHtml(cell.getValue()) || '—') + '</span>',
        },
        {
            title: 'Ánh xạ danh mục chuẩn Visafo', field: 'product_name', minWidth: 200, headerSort: false,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                if (!d.product_name) return _emptyOr(null);
                const sku = d.product_sku ? ' <span class="text-xs text-base-content/40 font-mono">(' + _escHtml(d.product_sku) + ')</span>' : '';
                return _escHtml(d.product_name) + sku;
            },
        },
        {
            title: 'Nhà sản xuất / Nguồn gốc', field: 'manufacturer_name', minWidth: 160,
            formatter: (cell) => _emptyOr(cell.getValue(), _escHtml(cell.getValue())),
        },
        {
            title: 'Trạng thái', field: 'status_label', width: 130, hozAlign: 'center', headerSort: false,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                return '<span class="badge ' + _escHtml(d.status_badge) + ' badge-xs">' + _escHtml(d.status_label) + '</span>';
            },
        },
        {
            title: '', field: 'show_url', width: 100, hozAlign: 'center', headerSort: false,
            formatter: (cell) => {
                const url = cell.getValue();
                return url ? '<a href="' + _escHtml(url) + '" class="link link-primary text-xs">Xem chi tiết</a>' : '';
            },
        },
    ];

    partnerProductsTable = window.initTabulator('#vendor-partner-products-table', columns, rows, {
        pagination:             true,
        paginationSize:         10,
        paginationSizeSelector: [10, 25, 50],
        paginationCounter:      'rows',
        placeholder: '<div class="py-10 text-center text-sm text-base-content/40">Chưa có hàng hóa nào được nhà cung cấp này kê khai.</div>',
    });
}

window.onVendorTabShown = function (tab) {
    if (tab === 'documents') {
        if (!documentsTable) documentsTable = initComplianceDocumentsTable('vendor-documents-table', window.openEditDocumentModal);
        else documentsTable.redraw(true);
    }
    if (tab === 'products') {
        if (!partnerProductsTable) _initPartnerProductsTable();
        else partnerProductsTable.redraw(true);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const initialTab = document.querySelector('[data-initial-tab]')?.dataset.initialTab;
    if (initialTab === 'documents') documentsTable = initComplianceDocumentsTable('vendor-documents-table', window.openEditDocumentModal);
    if (initialTab === 'products') _initPartnerProductsTable();
});
