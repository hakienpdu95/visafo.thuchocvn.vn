function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';
const text = (v) => (v == null || v === '' ? EMPTY : esc(v));
const mono = (v) => (v == null || v === '' ? EMPTY : '<span class="font-mono">' + esc(v) + '</span>');
const pass = (v) => (v
    ? '<span class="badge badge-sm badge-soft badge-success">Đạt</span>'
    : '<span class="badge badge-sm badge-soft badge-error">Không đạt</span>');
const source = (v) => (v === 'step2'
    ? '<span class="badge badge-sm badge-soft badge-primary">Từ Bước 2</span>'
    : v === 'menu' ? '<span class="badge badge-sm badge-soft badge-info">Từ Thực đơn</span>' : EMPTY);

const COLUMNS = [
    { title: 'STT', field: 'stt', width: 70, hozAlign: 'center', headerSort: false },
    { title: 'Ca/bữa', field: 'meal_label', width: 150, headerSort: false, formatter: (c) => text(c.getValue()) },
    {
        title: 'Tên món ăn', field: 'dish_name', minWidth: 220, sorter: 'string', frozen: true,
        formatter: (cell) => '<span class="font-semibold text-sm">' + esc(cell.getValue()) + '</span>',
    },
    { title: 'Nguồn', field: 'source', width: 130, hozAlign: 'center', headerSort: false, formatter: (c) => source(c.getValue()) },
    { title: 'Số suất', field: 'quantity', width: 100, hozAlign: 'right', headerSort: false, formatter: (c) => mono(c.getValue()) },
    { title: 'Chia xong', field: 'portion_time', width: 110, hozAlign: 'center', headerSort: false, formatter: (c) => mono(c.getValue()) },
    { title: 'Bắt đầu ăn', field: 'eat_time', width: 110, hozAlign: 'center', headerSort: false, formatter: (c) => mono(c.getValue()) },
    { title: 'Thời gian chờ', field: 'wait_minutes', width: 130, hozAlign: 'center', headerSort: false,
      formatter: (c) => (c.getValue() == null ? EMPTY : '<span class="font-mono">' + esc(c.getValue()) + ' phút</span>') },
    { title: 'Dụng cụ chứa đựng', field: 'equipment_used', minWidth: 200, headerSort: false, formatter: (c) => text(c.getValue()) },
    { title: 'Cảm quan', field: 'sensory_eval', width: 120, hozAlign: 'center', headerSort: false, formatter: (c) => pass(c.getValue()) },
    { title: 'Biện pháp xử lý', field: 'action_taken', minWidth: 220, headerSort: false, formatter: (c) => text(c.getValue()) },
];

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('step3-detail-table');
    if (!el || !window.initTabulator) return;

    window.initTabulator(el, COLUMNS, JSON.parse(el.dataset.rows || '[]'), {
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
