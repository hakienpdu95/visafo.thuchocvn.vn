@extends('layouts.backend')
@section('title', 'Báo cáo sản lượng')

@section('content')
@include('report::partials.header', ['subtitle' => 'Sản lượng đặt hàng theo khách hàng và mặt hàng, tính riêng theo từng đơn vị tính'])

<div x-data="volumeReport({{ Js::from([
    'apiUrl'   => route('backend.api.reports.volume'),
    'defaults' => $defaults,
    'units'    => $units,
]) }})" class="flex flex-col gap-4">

    <div class="card bg-base-100 border border-base-200">
        <div class="card-body py-3 px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                @include('report::partials.date-range-filter')
                @include('report::partials.customer-filter')
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Đơn vị tính</span></label>
                    <select id="rp-unit" data-ts-placeholder="Tất cả ĐVT" class="select select-sm select-bordered w-full">
                        <option value="">Tất cả</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit['value'] }}">{{ $unit['text'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="card bg-base-100 border border-base-200">
            <div class="card-body p-4">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="font-semibold text-sm">Top 10 mặt hàng xuất nhiều nhất</h2>
                    <span class="badge badge-sm badge-ghost" x-text="'ĐVT: ' + (filters.unit || 'Tất cả')"></span>
                </div>
                <div id="rp-top-products-chart" class="h-80 w-full"></div>
                <p class="text-sm text-base-content/40 text-center" x-show="!loading && !hasChartData" x-cloak>Không có dữ liệu</p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200">
            <div class="card-body p-4">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="font-semibold text-sm">Sản lượng theo ngày giao</h2>
                    <span class="badge badge-sm badge-ghost" x-text="'ĐVT: ' + (filters.unit || 'Tất cả')"></span>
                </div>
                <div id="rp-trend-chart" class="h-80 w-full"></div>
                <p class="text-sm text-base-content/40 text-center" x-show="!loading && !hasChartData" x-cloak>Không có dữ liệu</p>
            </div>
        </div>
    </div>
    <p class="text-xs text-base-content/50 -mt-2" x-show="!filters.unit" x-cloak>
        Khi không chọn ĐVT, biểu đồ sẽ so sánh dựa trên số lượng tuyệt đối. Vui lòng chọn một ĐVT cụ thể ở bộ lọc để xem tỷ lệ chính xác.
    </p>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="card bg-base-100 border border-base-200">
            <div class="card-body p-0 overflow-hidden tabulator-daisy">
                <h2 class="font-semibold text-sm px-4 pt-4 pb-2">Theo khách hàng</h2>
                <div id="rp-customers-table"></div>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200">
            <div class="card-body p-0 overflow-hidden tabulator-daisy">
                <h2 class="font-semibold text-sm px-4 pt-4 pb-2">Theo mặt hàng</h2>
                <div id="rp-products-table"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@include('report::partials.assets', ['withCharts' => true])
