import { reportBase, esc, fmtQty } from './report-filters.js';

const SERIES = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7'];
const INK = '#52514e';
const GRID = '#e5e7eb';

const qtyCol = (field, title) => ({
    title, field, width: 130, hozAlign: 'right', sorter: 'number',
    formatter: (c) => '<span class="font-mono font-semibold">' + fmtQty(c.getValue()) + '</span>',
});

const CUSTOMER_COLUMNS = [
    { title: 'Khách hàng', field: 'customer_name', minWidth: 220, formatter: (c) => esc(c.getValue()) },
    { title: 'ĐVT', field: 'unit', width: 80, headerSort: false },
    qtyCol('qty', 'Sản lượng'),
    { title: 'Số đơn', field: 'orders', width: 90, hozAlign: 'right', sorter: 'number' },
    { title: 'Số mặt hàng', field: 'products', width: 110, hozAlign: 'right', sorter: 'number' },
];

const PRODUCT_COLUMNS = [
    { title: 'Mặt hàng', field: 'product_name', minWidth: 200, formatter: (c) => esc(c.getValue()) },
    { title: 'Mã hàng', field: 'sku', width: 110, formatter: (c) => '<span class="font-mono text-xs">' + esc(c.getValue()) + '</span>' },
    { title: 'ĐVT', field: 'unit', width: 80, headerSort: false },
    qtyCol('qty', 'Sản lượng'),
    { title: 'Số KH', field: 'customers', width: 80, hozAlign: 'right', sorter: 'number' },
];

const whenECharts = (cb) => {
    if (window.ECharts) return cb(window.ECharts);
    document.addEventListener('echarts:ready', () => cb(window.ECharts), { once: true });
};

const sameUnit = (a, b) => String(a ?? '').trim().toLowerCase() === String(b ?? '').trim().toLowerCase();

const tooltipBase = {
    backgroundColor: '#ffffff',
    borderColor: GRID,
    textStyle: { color: '#0b0b0b', fontSize: 12 },
};

document.addEventListener('alpine:init', () => {
    Alpine.data('volumeReport', ({ apiUrl, defaults }) => {
        let customerTable = null;
        let productTable = null;
        let barChart = null;
        let lineChart = null;

        return {
            ...reportBase({ apiUrl, defaults, filterKeys: ['date_from', 'date_to', 'customer', 'unit'] }),
            hasChartData: false,

            init() { this.initBase(); },

            setupView() {
                const opts = { height: '50vh', paginationSize: 25 };
                customerTable = window.initTabulator('#rp-customers-table', CUSTOMER_COLUMNS, [], {
                    ...opts, placeholder: '<div class="py-10 text-center text-sm text-base-content/40">Không có dữ liệu</div>',
                });
                productTable = window.initTabulator('#rp-products-table', PRODUCT_COLUMNS, [], {
                    ...opts, placeholder: '<div class="py-10 text-center text-sm text-base-content/40">Không có dữ liệu</div>',
                });

                whenECharts((echarts) => {
                    barChart = echarts.init(document.getElementById('rp-top-products-chart'));
                    lineChart = echarts.init(document.getElementById('rp-trend-chart'));
                    window.addEventListener('resize', () => { barChart?.resize(); lineChart?.resize(); });
                    if (this._pending) this.drawCharts(this._pending);
                });
            },

            render(data) {
                customerTable?.setData(data.customers ?? []);
                productTable?.setData(data.products ?? []);
                this._pending = data;
                this.drawCharts(data);
            },

            drawCharts(data) {
                if (!barChart || !lineChart) return;
                const top = (data.products ?? []).slice(0, 10).reverse();
                const trend = data.trend ?? [];
                this.hasChartData = top.length > 0;

                const units = [];
                trend.forEach((t) => { if (!units.some((u) => sameUnit(u, t.unit))) units.push(t.unit); });
                const days = [...new Set(trend.map((t) => t.day))];

                const axisLabel = { color: INK, fontSize: 11 };
                const valueAxis = {
                    type: 'value',
                    axisLabel: { ...axisLabel, formatter: (v) => fmtQty(v) },
                    splitLine: { lineStyle: { color: GRID } },
                };

                barChart.setOption({
                    grid: { left: 8, right: 72, top: 8, bottom: 8, containLabel: true },
                    tooltip: {
                        ...tooltipBase, trigger: 'item',
                        formatter: (p) => esc(p.name) + ': <b>' + fmtQty(p.value) + '</b> ' + esc(p.data.unit),
                    },
                    xAxis: valueAxis,
                    yAxis: {
                        type: 'category',
                        data: top.map((p) => p.product_name),
                        axisLabel: { ...axisLabel, width: 140, overflow: 'truncate' },
                        axisTick: { show: false },
                        axisLine: { lineStyle: { color: GRID } },
                    },
                    series: [{
                        type: 'bar',
                        data: top.map((p) => ({ value: p.qty, unit: p.unit })),
                        barMaxWidth: 24,
                        itemStyle: { color: SERIES[0], borderRadius: [0, 4, 4, 0] },
                        label: {
                            show: true, position: 'right', color: INK, fontSize: 11,
                            formatter: (p) => fmtQty(p.value) + ' ' + (p.data.unit ?? ''),
                        },
                    }],
                }, true);

                lineChart.setOption({
                    grid: { left: 8, right: 24, top: units.length > 1 ? 36 : 16, bottom: 8, containLabel: true },
                    legend: { show: units.length > 1, top: 0, textStyle: { color: INK, fontSize: 11 }, icon: 'roundRect', itemWidth: 12, itemHeight: 4 },
                    tooltip: {
                        ...tooltipBase, trigger: 'axis', axisPointer: { type: 'line', lineStyle: { color: INK } },
                        formatter: (ps) => esc(ps[0].axisValue) + ps
                            .filter((p) => p.value != null)
                            .map((p) => '<br>' + p.marker + '<b>' + fmtQty(p.value) + '</b> ' + esc(p.seriesName))
                            .join(''),
                    },
                    xAxis: {
                        type: 'category',
                        data: days,
                        boundaryGap: false,
                        axisLabel,
                        axisLine: { lineStyle: { color: GRID } },
                    },
                    yAxis: valueAxis,
                    series: units.slice(0, SERIES.length).map((unit, i) => ({
                        name: unit,
                        type: 'line',
                        data: days.map((day) => trend.find((t) => t.day === day && sameUnit(t.unit, unit))?.qty ?? null),
                        connectNulls: false,
                        lineStyle: { width: 2, color: SERIES[i] },
                        itemStyle: { color: SERIES[i], borderColor: '#ffffff', borderWidth: 2 },
                        symbol: 'circle',
                        symbolSize: 8,
                        showSymbol: days.length <= 31,
                    })),
                }, true);
            },
        };
    });
});
