function _escHtml(v) {
    if (v == null) return '';
    return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function _emptyOr(value, html) {
    return value ? html : '<span class="text-base-content/25 text-xs">—</span>';
}

function _detailHtml(d) {
    const parts = [];

    if (d.notes) {
        parts.push('<div class="text-xs font-medium text-base-content leading-snug">' + _escHtml(d.notes) + '</div>');
    }

    const chips = [];
    if (d.material) chips.push('<span class="badge badge-info badge-sm h-auto py-0.5 whitespace-normal text-left">Vật tư: ' + _escHtml(d.material) + '</span>');
    if (d.dosage) chips.push('<span>Liều lượng: <strong>' + _escHtml(d.dosage) + '</strong></span>');
    if (d.isolation_days) chips.push('<span>Cách ly: <strong>' + _escHtml(d.isolation_days) + ' ngày</strong></span>');
    if (d.target) chips.push('<span>Đối tượng: ' + _escHtml(d.target) + '</span>');
    if (d.is_harvest) {
        chips.push(d.harvest_yield
            ? '<span class="badge badge-success badge-sm">Sản lượng: ' + _escHtml(d.harvest_yield) + '</span>'
            : '<span class="badge badge-ghost badge-sm">Chưa ghi sản lượng</span>');
        if (d.buyer_name) chips.push('<span>Cơ sở tiêu thụ: <strong>' + _escHtml(d.buyer_name) + '</strong></span>');
    }
    if (chips.length) {
        parts.push('<div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-md bg-base-200 px-2 py-1 text-xs">'
            + chips.join('<span class="text-base-content/30">|</span>') + '</div>');
    }

    if (d.quarantine_text) {
        parts.push('<div class="text-xs mt-1 font-medium ' + (d.in_quarantine ? 'text-error' : 'text-success') + '">'
            + _escHtml(d.quarantine_text) + '</div>');
    }

    return parts.length ? '<div class="py-1">' + parts.join('') + '</div>' : _emptyOr(null);
}

function _confirmDeleteLog(row, urlTemplate, table) {
    if (!confirm('Xóa nhật ký này? Bản ghi sẽ được ẩn nhưng vẫn giữ vết trong DB.')) return;

    const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';

    fetch(urlTemplate.replace('__ID__', row.id), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN':     csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type':     'application/x-www-form-urlencoded',
        },
        body: '_method=DELETE',
    }).then((res) => {
        if (res.ok) table.deleteRow(row.id);
        else alert('Xóa thất bại. Vui lòng thử lại.');
    }).catch(() => alert('Lỗi kết nối. Vui lòng thử lại.'));
}

function initFarmingLogTable() {
    const el = document.getElementById('farming-log-table');
    if (!el || !window.initTabulator) return null;

    const rows = JSON.parse(el.dataset.rows || '[]');
    const canManage = el.dataset.canManage === '1';
    const editUrlTemplate = el.dataset.editUrlTemplate;
    const deleteUrlTemplate = el.dataset.deleteUrlTemplate;

    const columns = [
        {
            title: 'Thời gian', field: 'activity_date_ts', width: 140, sorter: 'number',
            formatter: (cell) => '<span class="text-xs whitespace-nowrap">' + _escHtml(cell.getRow().getData().activity_date) + '</span>',
        },
        {
            title: 'Hoạt động', field: 'type_label', width: 150, headerSort: false,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                return '<span class="badge ' + _escHtml(d.type_badge) + ' badge-sm">' + _escHtml(d.type_label) + '</span>';
            },
        },
        {
            title: 'Chi tiết', field: 'notes', minWidth: 320, headerSort: false,
            formatter: (cell) => {
                cell.getElement().style.whiteSpace = 'normal';
                return _detailHtml(cell.getRow().getData());
            },
        },
        {
            title: 'Hình ảnh', field: 'image_url', width: 90, hozAlign: 'center', headerSort: false,
            formatter: (cell) => {
                const url = cell.getValue();
                if (!url) return _emptyOr(null);
                return '<img src="' + _escHtml(url) + '" data-action="preview" '
                    + 'class="w-10 h-10 object-cover rounded-md border border-base-200 cursor-pointer hover:opacity-80 transition-opacity mx-auto">';
            },
            cellClick: (e, cell) => {
                if (!e.target.closest('[data-action="preview"]')) return;
                const url = cell.getValue();
                if (!url) return;
                document.getElementById('logImageLightboxImg').src = url;
                document.getElementById('logImageLightbox')?.showModal();
            },
        },
        {
            title: 'Người thực hiện', field: 'operator', width: 150, headerSort: false,
            formatter: (cell) => _emptyOr(cell.getValue(), '<span class="text-xs">' + _escHtml(cell.getValue()) + '</span>'),
        },
    ];

    if (canManage) {
        columns.push({
            title: '', field: 'id', width: 100, hozAlign: 'center', headerSort: false,
            formatter: () => '<div class="flex items-center justify-center gap-1">'
                + '<a href="#" data-action="edit" class="btn btn-ghost btn-xs px-1.5">Sửa</a>'
                + '<button type="button" data-action="delete" class="btn btn-ghost btn-xs text-error px-1.5">Xóa</button>'
                + '</div>',
            cellClick: (e, cell) => {
                const action = e.target.closest('[data-action]')?.dataset.action;
                if (!action) return;
                e.preventDefault();
                const data = cell.getRow().getData();
                if (action === 'edit') window.location.href = editUrlTemplate.replace('__ID__', data.id);
                if (action === 'delete') _confirmDeleteLog(data, deleteUrlTemplate, cell.getTable());
            },
        });
    }

    return window.initTabulator('#farming-log-table', columns, rows, {
        index:                  'id',
        pagination:             true,
        paginationSize:         10,
        paginationSizeSelector: [10, 25, 50],
        paginationCounter:      'rows',
        initialSort:            [{ column: 'activity_date_ts', dir: 'desc' }],
        placeholder: '<div class="py-10 text-center text-sm text-base-content/40">Chưa có nhật ký nào được ghi nhận từ App Nông hộ.</div>',
    });
}

document.addEventListener('DOMContentLoaded', initFarmingLogTable);
