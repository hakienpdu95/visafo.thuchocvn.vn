function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';
const text = (v) => (v == null || v === '' ? EMPTY : esc(v));
const mono = (v) => (v == null || v === '' ? EMPTY : '<span class="font-mono">' + esc(v) + '</span>');
const badge = (label, cls) => '<span class="badge badge-sm badge-soft ' + esc(cls) + '">' + esc(label) + '</span>';

// Cờ pháp lý: true → Có (xanh), false → Không (đỏ), null → không áp dụng.
function flag(v) {
    if (v === null || v === undefined) return EMPTY;
    return v ? badge('Có', 'badge-success') : badge('Không', 'badge-error');
}

const col = {
    stt:     { title: 'STT', field: 'stt', width: 70, hozAlign: 'center', headerSort: false },
    product: {
        title: 'Tên thực phẩm', field: 'product_name', minWidth: 200, sorter: 'string', frozen: true,
        formatter: (cell) => '<span class="font-semibold text-sm">' + esc(cell.getValue()) + '</span>',
    },
    receivedAt: { title: 'Thời gian nhập', field: 'received_at', width: 150, sorter: 'string', formatter: (c) => text(c.getValue()) },
    quantity:   { title: 'Khối lượng', field: 'quantity', width: 110, hozAlign: 'right', headerSort: false, formatter: (c) => mono(c.getValue()) },
    unit:       { title: 'ĐVT', field: 'unit', width: 80, headerSort: false, formatter: (c) => text(c.getValue()) },
    vendor:     { title: 'Cơ sở cung cấp', field: 'vendor_name', minWidth: 200, sorter: 'string', formatter: (c) => text(c.getValue()) },
    supplier:   { title: 'Địa chỉ, điện thoại', field: 'supplier_address_phone', minWidth: 220, headerSort: false, formatter: (c) => text(c.getValue()) },
    deliverer:  { title: 'Người giao hàng', field: 'deliverer_name', minWidth: 150, sorter: 'string', formatter: (c) => text(c.getValue()) },
    invoice:    { title: 'Hóa đơn', field: 'has_invoice', width: 100, hozAlign: 'center', headerSort: false, formatter: (c) => flag(c.getValue()) },
    sensory: {
        title: 'Cảm quan', field: 'sensory_label', width: 120, hozAlign: 'center', headerSort: false,
        formatter(cell) { const d = cell.getRow().getData(); return badge(d.sensory_label, d.sensory_badge); },
    },
    handling: {
        title: 'Biện pháp xử lý', field: 'handling_measure', minWidth: 220, headerSort: false,
        formatter: (c) => text(c.getValue()),
    },
};

const COLUMNS = {
    fresh: [
        col.stt, col.product, col.receivedAt, col.quantity, col.unit, col.vendor, col.supplier, col.deliverer, col.invoice,
        { title: 'VS thú y', field: 'has_vet_cert', width: 100, hozAlign: 'center', headerSort: false, formatter: (c) => flag(c.getValue()) },
        { title: 'Kiểm dịch', field: 'has_quarantine_cert', width: 100, hozAlign: 'center', headerSort: false, formatter: (c) => flag(c.getValue()) },
        col.sensory,
        {
            title: 'Test nhanh', field: 'quick_label', width: 120, hozAlign: 'center', headerSort: false,
            formatter(cell) { const d = cell.getRow().getData(); return badge(d.quick_label, d.quick_badge); },
        },
        col.handling,
    ],
    dry: [
        col.stt, col.product,
        {
            title: 'Nhà sản xuất', field: 'manufacturer_name', minWidth: 220, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.manufacturer_name && !d.manufacturer_address) return EMPTY;
                return '<div><p class="text-sm">' + esc(d.manufacturer_name || '—') + '</p>'
                    + (d.manufacturer_address ? '<p class="text-xs text-base-content/50">' + esc(d.manufacturer_address) + '</p>' : '') + '</div>';
            },
        },
        col.receivedAt, col.quantity, col.unit, col.vendor, col.supplier, col.deliverer,
        { title: 'Hạn sử dụng', field: 'expiry_date', width: 130, sorter: 'string', formatter: (c) => text(c.getValue()) },
        { title: 'Bảo quản', field: 'storage_condition', width: 140, headerSort: false, formatter: (c) => text(c.getValue()) },
        { ...col.invoice, title: 'Chứng từ' },
        col.sensory, col.handling,
    ],
};

document.addEventListener('DOMContentLoaded', () => {
    if (!window.initTabulator) return;

    document.querySelectorAll('[data-fi-table]').forEach((el) => {
        const rows = JSON.parse(el.dataset.rows || '[]');

        window.initTabulator(el, COLUMNS[el.dataset.group] ?? [], rows, {
            pagination: true,
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100],
            paginationCounter: 'rows',
            layout: 'fitColumns',
            movableColumns: true,
            rowFormatter(row) {
                if (row.getData().failed) row.getElement().classList.add('bg-error/5');
            },
            placeholder: '<div class="py-10 text-center text-sm text-base-content/40">Không có dòng nào.</div>',
        });
    });
});
