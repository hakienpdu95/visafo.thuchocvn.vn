import { reportBase, esc, fmtQty } from './report-filters.js';

const COLUMNS = [
    { title: 'Mặt hàng', field: 'product_name', minWidth: 220, formatter: (c) => '<span class="font-medium">' + esc(c.getValue()) + '</span>' },
    { title: 'Mã hàng', field: 'sku', width: 120, formatter: (c) => '<span class="font-mono text-xs">' + esc(c.getValue()) + '</span>' },
    { title: 'ĐVT', field: 'unit', width: 80, headerSort: false },
    {
        title: 'Tổng cần chuẩn bị', field: 'total_qty', width: 160, hozAlign: 'right', sorter: 'number',
        formatter: (c) => '<span class="font-mono font-semibold text-base">' + fmtQty(c.getValue()) + '</span>',
    },
    {
        title: 'Chi tiết theo khách hàng', field: 'customers', minWidth: 320, headerSort: false, variableHeight: true,
        formatter(c) {
            return '<div class="flex flex-wrap gap-1 py-1 whitespace-normal">' + (c.getValue() ?? []).map((x) =>
                '<span class="badge badge-sm badge-outline h-auto py-0.5">' + esc(x.customer_name)
                + ': <span class="font-mono font-semibold ml-1">' + fmtQty(x.qty) + '</span></span>').join('') + '</div>';
        },
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('pickingReport', ({ apiUrl, defaults }) => {
        let table = null;

        return {
            ...reportBase({ apiUrl, defaults, filterKeys: ['date', 'customer'] }),
            rows: [],
            customerCount: 0,

            init() { this.initBase(); },

            setupView() {
                table = window.initTabulator('#rp-picking-table', COLUMNS, [], {
                    pagination: false,
                    height: '65vh',
                    placeholder: '<div class="py-16 text-center text-sm text-base-content/40">Không có đơn hàng nào giao vào ngày này</div>',
                });
            },

            render(data) {
                this.rows = data.rows ?? [];
                this.customerCount = data.customers ?? 0;
                table?.setData(this.rows);
            },
        };
    });
});
