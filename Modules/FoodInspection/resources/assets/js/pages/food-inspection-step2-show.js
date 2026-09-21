function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';
const text = (v) => (v == null || v === '' ? EMPTY : esc(v));
const mono = (v) => (v == null || v === '' ? EMPTY : '<span class="font-mono">' + esc(v) + '</span>');
// Tiêu chí đạt (true) / không đạt (false).
const pass = (v) => (v
    ? '<span class="badge badge-sm badge-soft badge-success">Đạt</span>'
    : '<span class="badge badge-sm badge-soft badge-error">Không đạt</span>');
const check = (title, field, width = 110) => ({ title, field, width, hozAlign: 'center', headerSort: false, formatter: (c) => pass(c.getValue()) });

const COLUMNS = [
    { title: 'STT', field: 'stt', width: 70, hozAlign: 'center', headerSort: false },
    { title: 'Ca/bữa', field: 'meal_label', width: 150, headerSort: false, formatter: (c) => text(c.getValue()) },
    {
        title: 'Tên món ăn', field: 'dish_name', minWidth: 200, sorter: 'string', frozen: true,
        formatter: (cell) => '<span class="font-semibold text-sm">' + esc(cell.getValue()) + '</span>',
    },
    { title: 'Nguyên liệu chính', field: 'main_ingredients', minWidth: 220, headerSort: false, formatter: (c) => text(c.getValue()) },
    { title: 'Số suất', field: 'quantity', width: 100, hozAlign: 'right', headerSort: false, formatter: (c) => mono(c.getValue()) },
    { title: 'Sơ chế xong', field: 'prep_time', width: 120, hozAlign: 'center', headerSort: false, formatter: (c) => mono(c.getValue()) },
    { title: 'Chế biến xong', field: 'cook_time', width: 130, hozAlign: 'center', headerSort: false, formatter: (c) => mono(c.getValue()) },
    check('VS người', 'hygiene_personnel'),
    check('VS thiết bị', 'hygiene_equipment', 120),
    check('VS khu vực', 'hygiene_area', 120),
    check('Cảm quan', 'sensory_eval'),
    { title: 'Biện pháp xử lý', field: 'action_taken', minWidth: 220, headerSort: false, formatter: (c) => text(c.getValue()) },
];

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('step2-detail-table');
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
