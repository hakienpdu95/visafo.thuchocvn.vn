function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function fmtDate(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return d + '/' + m + '/' + y;
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

let mfgPicker = null;
let expPicker = null;
let shelfLifeDays = null;

function initPickers() {
    if (mfgPicker || !window.initDatePicker) return;

    const opts = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: true,
        disableMobile: true,
        static: true, // calendar nằm trong <dialog>, tránh bị top-layer che
    };
    mfgPicker = window.initDatePicker('#fp-batch-mfg', {
        ...opts,
        onChange: (selected) => {
            // HSD trống + sản phẩm có số ngày bảo quản → gợi ý HSD = NSX + shelf_life_days
            if (!shelfLifeDays || !selected[0] || expPicker?.selectedDates.length) return;
            const d = new Date(selected[0]);
            d.setDate(d.getDate() + shelfLifeDays);
            expPicker.setDate(d, false);
        },
    });
    expPicker = window.initDatePicker('#fp-batch-exp', opts);
}

window.openBatchDateModal = function (row) {
    initPickers();

    document.getElementById('batchDateForm').action = row.update_url;
    document.getElementById('batchDateModalCode').textContent = row.batch_code || '';
    shelfLifeDays = row.shelf_life_days || null;
    mfgPicker?.setDate(row.mfg_date || null, false);
    expPicker?.setDate(row.exp_date || null, false);

    const hint = document.getElementById('batchShelfLifeHint');
    if (hint) {
        hint.textContent = shelfLifeDays
            ? 'Để trống HSD: hệ thống tự tính = NSX + ' + shelfLifeDays + ' ngày (theo danh mục sản phẩm).'
            : '';
        hint.classList.toggle('hidden', !shelfLifeDays);
    }

    document.getElementById('batchDateModal')?.showModal();
};

const COLUMNS = [
    { title: 'STT', field: 'line_no', width: 70, hozAlign: 'center', headerSort: false },
    { title: 'Tên hàng', field: 'name', minWidth: 220, sorter: 'string' },
    {
        title: 'Mã hàng', field: 'sku', width: 130, sorter: 'string',
        formatter: (cell) => '<span class="font-mono text-xs">' + esc(cell.getValue()) + '</span>',
    },
    { title: 'ĐVT', field: 'unit', width: 90, headerSort: false },
    {
        title: 'SL nhập', field: 'quantity', width: 120, hozAlign: 'right', headerSort: false,
        formatter: (cell) => '<span class="font-mono">' + esc(cell.getValue()) + '</span>',
    },
    {
        title: 'Mã lô', field: 'batch_code', minWidth: 160, headerSort: false,
        formatter: (cell) => cell.getValue()
            ? '<span class="font-mono text-xs">' + esc(cell.getValue()) + '</span>' : EMPTY,
    },
    {
        title: 'NSX', field: 'mfg_date', width: 110, hozAlign: 'center', headerSort: false,
        formatter: (cell) => cell.getValue() ? esc(fmtDate(cell.getValue())) : EMPTY,
    },
    {
        title: 'HSD', field: 'exp_date', width: 110, hozAlign: 'center', headerSort: false,
        formatter: (cell) => cell.getValue() ? esc(fmtDate(cell.getValue())) : EMPTY,
    },
    {
        title: 'Thao tác', field: 'update_url', width: 90, hozAlign: 'center', headerSort: false, frozen: true,
        formatter: (cell) => cell.getValue()
            ? '<button type="button" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa NSX/HSD">'
                + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                + '</button>'
            : '',
        cellClick(_e, cell) {
            const row = cell.getRow().getData();
            if (row.update_url) window.openBatchDateModal(row);
        },
    },
];

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('goods-receipt-items-table');
    if (!el || !window.initTabulator) return;

    const rows = JSON.parse(el.dataset.rows || '[]');

    window.goodsReceiptItemsTable = window.initTabulator('#goods-receipt-items-table', COLUMNS, rows, {
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100],
        paginationCounter: 'rows',
        layout: 'fitColumns',
        movableColumns: false,
        placeholder: '<div class="py-10 text-center text-sm text-base-content/40">Phiếu này chưa có dòng hàng nào.</div>',
    });
});
