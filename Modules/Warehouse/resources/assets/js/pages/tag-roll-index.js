function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildColumns() {
    return [
        {
            title: 'Prefix', field: 'prefix', width: 130, sorter: 'string', frozen: true,
            formatter(cell) {
                const v = cell.getValue();
                return '<span class="font-mono">' + (v ? esc(v) : '—') + '</span>';
            },
        },
        {
            title: 'Dải visual_sequence', field: 'from_sequence', minWidth: 200, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="font-mono">' + esc(d.from_sequence) + '–' + esc(d.to_sequence) + '</span>';
            },
        },
        {
            title: 'Tổng số', field: 'count', width: 120, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                return Number(cell.getValue()).toLocaleString('vi-VN');
            },
        },
        {
            title: 'Chưa gắn kết', field: 'provisioned', width: 140, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return '<span class="badge badge-ghost badge-sm">' + Number(cell.getValue()).toLocaleString('vi-VN') + '</span>';
            },
        },
        {
            title: 'Đã gắn kết', field: 'bound', width: 140, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return '<span class="badge badge-success badge-sm">' + Number(cell.getValue()).toLocaleString('vi-VN') + '</span>';
            },
        },
        {
            title: 'Ngày in', field: 'created_at', width: 150, hozAlign: 'center', sorter: 'string',
        },
        {
            title: 'Người in', field: 'creator_name', minWidth: 150, headerSort: false,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: '', field: 'id', width: 150, hozAlign: 'right', headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<a href="' + esc(d.show_url) + '" class="link link-primary text-xs">Xem chi tiết cuộn</a>';
            },
        },
    ];
}

document.addEventListener('alpine:init', () => {

    Alpine.data('tagRollListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        const COLUMNS = buildColumns();

        let tableInst = null;

        return {
            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                tableInst = new window.Tabulator('#tag-roll-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[tag-roll] API error', error),

                    rowClick(_e, row) {
                        const url = row.getData().show_url;
                        if (url) window.location = url;
                    },

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         15,
                    paginationSizeSelector: [10, 15, 25, 50],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'created_at', dir: 'desc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,
                    height:           '60vh',

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
                        + '<p class="text-sm">Chưa in cuộn tem nào</p></div>',
                });

                window.tagRollTable = tableInst;
            },
        };
    });
});
