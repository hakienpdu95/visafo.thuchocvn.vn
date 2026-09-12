function _escHtml(v) {
    if (v == null) return '';
    return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function _emptyOr(value, html) {
    return value ? html : '<span class="text-base-content/25 text-xs">—</span>';
}

function _confirmDeleteDocument(doc, urlTemplate, table) {
    if (!confirm('Xóa hồ sơ "' + (doc.type_name || '') + '"?')) return;

    const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';

    fetch(urlTemplate.replace('__ID__', doc.id), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN':     csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept':           'application/json',
            'Content-Type':     'application/x-www-form-urlencoded',
        },
        body: '_method=DELETE',
    }).then((res) => {
        if (res.ok) table.deleteRow(doc.id);
        else alert('Xóa thất bại. Vui lòng thử lại.');
    }).catch(() => alert('Lỗi kết nối. Vui lòng thử lại.'));
}

export function initComplianceDocumentsTable(tableId, onEdit, opts = {}) {
    const { showGroup = false } = opts;

    const el = document.getElementById(tableId);
    if (!el || !window.initTabulator) return null;

    // Hết hạn / sắp hết hạn phải nổi lên đầu bảng mặc định — QC cần thấy ngay
    // không phải tự sắp xếp lại. Field ẩn `_risk` chỉ phục vụ initialSort.
    const rows = JSON.parse(el.dataset.rows || '[]').map((row) => ({
        ...row,
        _risk: row.is_expired ? 2 : (row.is_expiring_soon ? 1 : 0),
    }));
    const canManage = el.dataset.canManage === '1';
    const deleteUrlTemplate = el.dataset.deleteUrlTemplate;

    const columns = [
        { field: '_risk', visible: false, sorter: 'number' },
        { field: 'expiration_date', visible: false, sorter: 'string' },
        { title: 'Loại giấy tờ', field: 'type_name', minWidth: 180, sorter: 'string' },
        ...(showGroup ? [{
            title: 'Nhóm', field: 'group_label', width: 150, headerSort: false,
            formatter: (cell) => _emptyOr(cell.getValue(), '<span class="badge badge-ghost badge-xs">' + _escHtml(cell.getValue()) + '</span>'),
        }] : []),
        {
            title: 'Số hiệu', field: 'document_number', width: 130,
            formatter: (cell) => _emptyOr(cell.getValue(), _escHtml(cell.getValue())),
        },
        {
            title: 'Ngày cấp', field: 'issue_date_display', width: 100,
            formatter: (cell) => _emptyOr(cell.getValue(), _escHtml(cell.getValue())),
        },
        {
            title: 'Hạn dùng', field: 'expiration_date_display', width: 150,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                let html = _emptyOr(d.expiration_date_display, _escHtml(d.expiration_date_display));
                if (d.is_expired) html += '<span class="badge badge-error badge-xs ml-1">Hết hạn</span>';
                else if (d.is_expiring_soon) html += '<span class="badge badge-warning badge-xs ml-1">Sắp hết hạn</span>';
                return html;
            },
        },
        {
            title: 'Trạng thái', field: 'status_label', width: 130, hozAlign: 'center', headerSort: false,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                return '<span class="badge ' + _escHtml(d.status_badge) + ' badge-xs">' + _escHtml(d.status_label) + '</span>';
            },
        },
        {
            title: 'File', field: 'file_url', width: 90, hozAlign: 'center', headerSort: false,
            formatter: (cell) => {
                const url = cell.getValue();
                return url ? '<a href="' + _escHtml(url) + '" target="_blank" class="link link-primary text-xs">Xem file</a>' : _emptyOr(null);
            },
        },
    ];

    if (canManage) {
        columns.push({
            title: '', field: 'id', width: 100, hozAlign: 'center', headerSort: false,
            formatter: () => '<div class="flex items-center justify-center gap-1">'
                + '<button type="button" class="btn btn-ghost btn-xs" data-action="edit">Sửa</button>'
                + '<button type="button" class="btn btn-ghost btn-xs text-error" data-action="delete">Xóa</button>'
                + '</div>',
            cellClick: (e, cell) => {
                const action = e.target.closest('[data-action]')?.dataset.action;
                if (!action) return;
                const data = cell.getRow().getData();
                if (action === 'edit') onEdit?.(data);
                if (action === 'delete') _confirmDeleteDocument(data, deleteUrlTemplate, cell.getTable());
            },
        });
    }

    return window.initTabulator('#' + tableId, columns, rows, {
        pagination:             true,
        paginationSize:         10,
        paginationSizeSelector: [10, 25, 50],
        paginationCounter:      'rows',
        initialSort: [
            { column: '_risk', dir: 'desc' },
            { column: 'expiration_date', dir: 'asc' },
        ],
        placeholder: '<div class="py-10 text-center text-sm text-base-content/40">Chưa có hồ sơ nào được ghi nhận.</div>',
    });
}
