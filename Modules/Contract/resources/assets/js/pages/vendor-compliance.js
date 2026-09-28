function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

const STATE_BADGE = {
    ok:       'badge-success',
    missing:  'badge-error',
    expiring: 'badge-warning',
    expired:  'badge-neutral',
};

function itemTitle(item) {
    const parts = [item.label];
    if (item.ref)   parts.push('Số: ' + item.ref);
    if (item.until) parts.push('Hạn: ' + item.until);
    if (!item.mandatory) parts.push('(khuyến nghị)');
    return parts.join(' · ');
}

function itemBadge(item) {
    let text = esc(item.label);
    if (item.state === 'expiring' && item.days_left != null) text += ' · còn ' + item.days_left + ' ngày';
    if (item.state === 'expired' && item.until) text += ' · ' + esc(item.until);

    const cls   = 'badge badge-sm badge-soft whitespace-nowrap ' + STATE_BADGE[item.state] + (item.mandatory ? '' : ' badge-dash');
    const title = ' title="' + esc(itemTitle(item)) + '"';

    return item.url
        ? '<a href="' + esc(item.url) + '" class="' + cls + ' hover:underline"' + title + '>' + text + '</a>'
        : '<span class="' + cls + '"' + title + '>' + text + '</span>';
}

function badgesFor(states) {
    return (cell) => {
        const items = (cell.getRow().getData().items || []).filter((i) => states.includes(i.state));
        return items.length
            ? '<div class="flex flex-wrap gap-1 py-0.5">' + items.map(itemBadge).join('') + '</div>'
            : EMPTY;
    };
}

function buildColumns() {
    return [
        {
            title: 'Nhà cung cấp', field: 'name', minWidth: 240, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const meta = [d.vendor_code, d.source_group_label].filter(Boolean).map(esc).join(' · ');
                const inactive = d.vendor_status !== 'active'
                    ? ' <span class="badge badge-neutral badge-xs">' + esc(d.vendor_status_label) + '</span>'
                    : '';
                return '<div>'
                    + '<a href="' + esc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + esc(d.name) + '</a>' + inactive
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + meta + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Tiến độ', field: 'progress', width: 130, sorter: 'number',
            formatter(cell) {
                const d = cell.getRow().getData();
                const c = d.counts;
                const cls = c.missing + c.expired === 0 ? 'progress-success' : (c.missing > 0 ? 'progress-error' : 'progress-warning');
                return '<div class="flex flex-col gap-1">'
                    + '<progress class="progress ' + cls + ' w-full" value="' + d.progress + '" max="100"></progress>'
                    + '<span class="text-xs text-base-content/50 tabular-nums">' + (c.ok + c.expiring) + '/' + c.total + ' yêu cầu</span>'
                    + '</div>';
            },
        },
        { title: 'Đã có', field: 'ok', minWidth: 200, headerSort: false, formatter: badgesFor(['ok']) },
        { title: 'Còn thiếu', field: 'missing', minWidth: 200, sorter: 'number', formatter: badgesFor(['missing']) },
        { title: 'Sắp / Đã hết hạn', field: 'expiring', minWidth: 220, sorter: 'number', formatter: badgesFor(['expiring', 'expired']) },
    ];
}

const DEFAULT_FILTERS = { search: '', sourceGroup: '', vendorStatus: 'active', days: 30, state: '' };

document.addEventListener('alpine:init', () => {

    Alpine.data('vendorCompliancePage', (serverData = {}) => {
        const { apiUrl = '', sourceGroups = [], vendorStatuses = [] } = serverData;

        let tableInst = null;

        return {
            sourceGroups,
            vendorStatuses,
            filters: { ...DEFAULT_FILTERS },
            summary: {},

            tiles: [
                { state: 'complete', label: 'Đủ hồ sơ',      textClass: 'text-success',          activeClass: 'border-success', chipClass: 'badge-success' },
                { state: 'missing',  label: 'Đang thiếu',    textClass: 'text-error',            activeClass: 'border-error',   chipClass: 'badge-error' },
                { state: 'expiring', label: 'Sắp hết hạn',   textClass: 'text-warning',          activeClass: 'border-warning', chipClass: 'badge-warning' },
                { state: 'expired',  label: 'Đã hết hạn',    textClass: 'text-base-content/60',  activeClass: 'border-neutral', chipClass: 'badge-neutral' },
            ],

            get hasFilters() {
                const f = this.filters, d = DEFAULT_FILTERS;
                return f.search !== d.search || f.sourceGroup !== d.sourceGroup || f.vendorStatus !== d.vendorStatus
                    || Number(f.days) !== d.days || f.state !== d.state;
            },

            init() {
                if (location.hash === '#compliance') this.$nextTick(() => this.activate());
            },

            activate() {
                if (tableInst) {
                    this.$nextTick(() => tableInst.redraw(true));
                    return;
                }
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#vendor-compliance-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)       p.search        = f.search;
                        if (f.sourceGroup)  p.source_group  = f.sourceGroup;
                        if (f.vendorStatus) p.vendor_status = f.vendorStatus;
                        if (f.state)        p.state         = f.state;
                        p.days = f.days;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => {
                        self.summary = res.summary || {};
                        return res;
                    },
                    ajaxError: (error) => console.error('[vendor-compliance] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'missing', dir: 'desc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    height:           '64vh',

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Dòng/trang', page_title: 'Trang',
                                first: '«', last: '»', prev: '‹', next: '›',
                                first_title: 'Trang đầu', last_title: 'Trang cuối',
                                prev_title: 'Trang trước', next_title: 'Trang sau',
                                counter: { showing: '', of: 'trong', rows: 'NCC', pages: 'trang' },
                            },
                        },
                    },

                    columns: buildColumns(),
                    placeholder: '<div class="py-16 text-center text-sm opacity-40">Không có nhà cung cấp nào phù hợp</div>',
                });
            },

            refresh()          { tableInst?.setPage(1); },
            setState(state)    { this.filters.state = state; this.refresh(); },
            toggleState(state) { this.setState(this.filters.state === state ? '' : state); },

            reset() {
                this.filters = { ...DEFAULT_FILTERS };
                this.refresh();
            },
        };
    });
});
