function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns(canManage, csrfToken) {
    return [
        {
            title: 'Số Serial', field: 'serial', minWidth: 160, sorter: 'string', frozen: true,
            formatter(cell) {
                return '<span class="font-mono">' + esc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Mã QR', field: 'qr_code', minWidth: 200, headerSort: false,
            formatter(cell) {
                return '<span class="font-mono">' + esc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'status_value', width: 180, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge ' + esc(d.status_badge) + ' badge-xs">' + esc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Ngày bán', field: 'sold_at', width: 160, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: '', field: 'id', minWidth: 220, hozAlign: 'right', headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!canManage || !d.can_unbind_void) return '';

                let html = '<div class="flex items-center justify-end gap-1">';

                html += '<form method="POST" action="' + esc(d.unbind_url) + '" class="inline"'
                    + ' onsubmit="return confirm(\'Gỡ gắn kết tem này? Tem sẽ quay về kho tiền định danh, có thể gán cho lô khác.\');">'
                    + '<input type="hidden" name="_token" value="' + esc(csrfToken) + '">'
                    + '<button type="submit" class="btn btn-ghost btn-xs">Gỡ gắn kết</button>'
                    + '</form>';

                html += '<form method="POST" action="' + esc(d.void_url) + '" class="inline"'
                    + ' onsubmit="return confirm(\'Báo hỏng tem này? Tem sẽ bị loại khỏi vòng đời luân chuyển, không thể hoàn tác.\');">'
                    + '<input type="hidden" name="_token" value="' + esc(csrfToken) + '">'
                    + '<button type="submit" class="btn btn-ghost btn-xs text-error">Báo hỏng</button>'
                    + '</form>';

                html += '</div>';
                return html;
            },
        },
    ];
}

document.addEventListener('alpine:init', () => {

    Alpine.data('retailItemTagListPage', (serverData = {}) => {
        const {
            apiUrl    = '',
            canManage = false,
            csrfToken = '',
        } = serverData;

        const COLUMNS = buildColumns(canManage, csrfToken);

        let tableInst = null;

        return {
            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                tableInst = new window.Tabulator('#retail-item-tag-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[retail-item-tag] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         50,
                    paginationSizeSelector: [25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'serial', dir: 'asc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,
                    height:           '68vh',

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Dòng/trang', page_title: 'Trang',
                                first: '«', last: '»', prev: '‹', next: '›',
                                first_title: 'Trang đầu', last_title: 'Trang cuối',
                                prev_title: 'Trang trước', next_title: 'Trang sau',
                                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                            },
                        },
                    },

                    columns: COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5.586a1 1 0 01.707.293l6.414 6.414a1 1 0 010 1.414l-6.586 6.586a1 1 0 01-1.414 0L5.293 11.293A1 1 0 015 10.586V5a2 2 0 012-2z"/></svg>'
                        + '<p class="text-sm">Chưa có tem nào</p></div>',
                });

                window.retailItemTagTable = tableInst;
            },
        };
    });
});
