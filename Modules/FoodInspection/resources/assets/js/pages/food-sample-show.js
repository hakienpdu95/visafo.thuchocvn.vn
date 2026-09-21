function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';
const text = (v) => (v == null || v === '' ? EMPTY : esc(v));
const mono = (v) => (v == null || v === '' ? EMPTY : '<span class="font-mono">' + esc(v) + '</span>');

function hoursMinutes(total) {
    const h = Math.floor(total / 60), m = total % 60;
    return (h ? h + ' giờ ' : '') + m + ' phút';
}

const COLUMNS = [
    { title: 'STT', field: 'stt', width: 70, hozAlign: 'center', headerSort: false },
    { title: 'Bữa ăn', field: 'meal_label', width: 140, headerSort: false, formatter: (c) => text(c.getValue()) },
    {
        title: 'Tên mẫu thức ăn', field: 'dish_name', minWidth: 200, sorter: 'string', frozen: true,
        formatter: (cell) => '<span class="font-semibold text-sm">' + esc(cell.getValue()) + '</span>',
    },
    { title: 'Số suất', field: 'portion_qty', width: 100, hozAlign: 'right', headerSort: false, formatter: (c) => mono(c.getValue()) },
    { title: 'Khối lượng', field: 'sample_volume', width: 110, hozAlign: 'center', headerSort: false, formatter: (c) => mono(c.getValue()) },
    { title: 'Dụng cụ', field: 'container_type', minWidth: 150, headerSort: false, formatter: (c) => text(c.getValue()) },
    {
        title: 'Nhiệt độ', field: 'storage_temp', width: 100, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            if (v == null) return EMPTY;
            const out = Number(v) < 2 || Number(v) > 8;
            return '<span class="font-mono ' + (out ? 'text-warning font-semibold' : '') + '">' + esc(v) + '°C</span>';
        },
    },
    { title: 'Lấy mẫu lúc', field: 'sampled_at', width: 150, hozAlign: 'center', headerSort: false, formatter: (c) => mono(c.getValue()) },
    { title: 'Người lấy mẫu', field: 'sampler_name', minWidth: 150, headerSort: false, formatter: (c) => text(c.getValue()) },
    { title: 'Được hủy từ', field: 'destroyable_from', width: 150, hozAlign: 'center', headerSort: false, formatter: (c) => mono(c.getValue()) },
    {
        title: 'Trạng thái', field: 'state', width: 190, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (d.state === 'destroyed') return '<span class="badge badge-sm badge-soft badge-neutral">Đã hủy</span>';
            if (d.state === 'pending') return '<span class="badge badge-sm badge-soft badge-warning">Chờ hủy mẫu</span>';
            return '<span class="badge badge-sm badge-soft badge-info">Còn ' + esc(hoursMinutes(d.remaining)) + '</span>';
        },
    },
    { title: 'Hủy lúc', field: 'destroyed_at', width: 150, hozAlign: 'center', headerSort: false, formatter: (c) => mono(c.getValue()) },
    { title: 'Người hủy', field: 'destroyer_name', minWidth: 140, headerSort: false, formatter: (c) => text(c.getValue()) },
    { title: 'Chất lượng khi hủy', field: 'quality_note', minWidth: 150, headerSort: false, formatter: (c) => text(c.getValue()) },
    {
        title: 'Tem', field: 'label_url', width: 90, hozAlign: 'center', headerSort: false, frozen: true,
        formatter: (cell) => '<a href="' + esc(cell.getValue()) + '" target="_blank" rel="noopener" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-primary" title="In tem lưu mẫu">'
            + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg></a>',
    },
];

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('food-sample-detail-table');
    if (!el || !window.initTabulator) return;

    window.initTabulator(el, COLUMNS, JSON.parse(el.dataset.rows || '[]'), {
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100],
        paginationCounter: 'rows',
        layout: 'fitColumns',
        movableColumns: true,
        rowFormatter(row) {
            if (row.getData().state === 'pending') row.getElement().classList.add('bg-warning/10');
        },
        placeholder: '<div class="py-10 text-center text-sm text-base-content/40">Không có mẫu nào.</div>',
    });
});
