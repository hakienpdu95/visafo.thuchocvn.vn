function _escHtml(v) {
    if (v == null) return '';
    return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function _emptyOr(value, html) {
    return value ? html : '<span class="text-base-content/25 text-xs">—</span>';
}

const _icons = {
    view:     '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
    edit:     '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
    download: '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>',
};

export function initPartyContractsTable(elId, emptyText = 'Chưa có hợp đồng nào.') {
    const el = document.getElementById(elId);
    if (!el || !window.initTabulator) return null;

    const rows = JSON.parse(el.dataset.rows || '[]');

    const columns = [
        {
            title: 'Hợp đồng', field: 'name', minWidth: 240, sorter: 'string', frozen: true,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                return '<div>'
                    + '<a href="' + _escHtml(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + _escHtml(d.name) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + _escHtml(d.contract_number) + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Loại hợp đồng', field: 'contract_type', minWidth: 180, sorter: 'string',
            formatter: (cell) => _emptyOr(cell.getValue(), _escHtml(cell.getValue())),
        },
        {
            title: 'Giá trị', field: 'total_value', width: 140, hozAlign: 'right', sorter: 'number',
            formatter: (cell) => {
                const v = cell.getValue();
                return _emptyOr(v != null, '<span class="font-mono text-xs">' + Number(v).toLocaleString('vi-VN') + '</span>');
            },
        },
        {
            title: 'Hiệu lực', field: 'end_date', minWidth: 190, headerSort: false,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                let html = '<span class="text-xs">' + _escHtml(d.start_date) + ' → ' + _escHtml(d.end_date || 'Vô thời hạn') + '</span>';
                if (d.is_auto_renew) html += '<span class="badge badge-info badge-xs ml-1">Auto</span>';
                return html;
            },
        },
        {
            title: 'Trạng thái', field: 'status_label', width: 130, hozAlign: 'center', sorter: 'string',
            formatter: (cell) => {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + _escHtml(d.status_badge) + '">' + _escHtml(d.status_label) + '</span>';
            },
        },
        {
            title: 'File đính kèm', field: 'files', minWidth: 200, headerSort: false,
            formatter: (cell) => {
                const files = cell.getValue() || [];
                return _emptyOr(files.length, '<div class="flex flex-col gap-0.5 py-0.5">' + files.map((f) =>
                    '<a href="' + _escHtml(f.url) + '" target="_blank" download class="flex items-center gap-1 text-xs link link-hover" title="' + _escHtml(f.name) + '">'
                    + _icons.download + '<span class="truncate">' + _escHtml(f.name) + '</span></a>'
                ).join('') + '</div>');
            },
        },
        {
            title: 'Thao tác', field: 'show_url', width: 90, hozAlign: 'center', headerSort: false, frozen: true,
            formatter: (cell) => {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">'
                    + '<a href="' + _escHtml(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">' + _icons.view + '</a>';
                if (d.edit_url) {
                    html += '<a href="' + _escHtml(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">' + _icons.edit + '</a>';
                }
                return html + '</div>';
            },
        },
    ];

    return window.initTabulator('#' + elId, columns, rows, {
        pagination:             true,
        paginationSize:         10,
        paginationSizeSelector: [10, 25, 50],
        paginationCounter:      'rows',
        placeholder: '<div class="py-10 text-center text-sm text-base-content/40">' + _escHtml(emptyText) + '</div>',
    });
}
