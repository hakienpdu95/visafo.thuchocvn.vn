@extends('layouts.backend')
@section('title', 'Tổng hợp nhặt hàng')

@section('content')
@include('report::partials.header', ['subtitle' => 'Tổng khối lượng từng mặt hàng cần chuẩn bị cho một ngày giao, kèm chi tiết theo khách hàng'])

<div x-data="pickingReport({{ Js::from([
    'apiUrl'   => route('backend.api.reports.picking'),
    'defaults' => $defaults,
]) }})" class="flex flex-col gap-4">

    <div class="card bg-base-100 border border-base-200">
        <div class="card-body py-3 px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Ngày giao</span></label>
                    <input id="rp-date" type="text" placeholder="dd/mm/yyyy" autocomplete="off" class="input input-sm input-bordered w-full"/>
                </div>
                @include('report::partials.customer-filter')
                <div class="lg:col-span-2 text-sm text-base-content/60 lg:text-right">
                    <span class="font-mono font-semibold" x-text="rows.length"></span> mặt hàng ·
                    <span class="font-mono font-semibold" x-text="customerCount"></span> khách hàng
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="rp-picking-table"></div>
        </div>
    </div>
</div>
@endsection

@include('report::partials.assets')
