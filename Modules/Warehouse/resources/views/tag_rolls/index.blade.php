@extends('layouts.backend')
@section('title', 'Kho tem tiền định danh')

@section('content')
<div x-data="tagRollListPage({{ Js::from([
    'apiUrl' => route('backend.api.tag-rolls'),
]) }})">
<div class="mb-6">
    <h1 class="text-2xl font-bold text-base-content">Kho tem tiền định danh (Pre-serialized Tags)</h1>
    <p class="text-sm text-base-content/50 mt-0.5">Chỉ dùng để in tem, xem lịch sử và tải lại file. Việc gán tem cho lô hàng thực hiện tại trang chi tiết Lô hàng.</p>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 text-sm">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-4">
            <p class="text-xs text-base-content/50">Tem chưa gắn kết (provisioned) — toàn hệ thống</p>
            <p class="text-2xl font-bold">{{ number_format($stats['provisioned']) }}</p>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-4">
            <p class="text-xs text-base-content/50">Tem đã gắn kết / đã dùng — toàn hệ thống</p>
            <p class="text-2xl font-bold">{{ number_format($stats['bound']) }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-1">In một cuộn tem mới</h2>
            <p class="text-xs text-base-content/50 mb-3">Sinh tem tiền định danh chưa gắn với sản phẩm/lô nào — dùng để in sẵn cuộn tem dán lên hàng khi hàng về kho.</p>

            <form method="POST" action="{{ route('backend.tag-rolls.provision') }}" class="flex items-end gap-2">
                @csrf
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Prefix (tiền tố chữ)</span></label>
                    <input type="text" name="prefix" maxlength="10" placeholder="VD: TH26" value="{{ old('prefix') }}" class="input input-bordered input-sm w-28 font-mono uppercase">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số lượng tem</span></label>
                    <input type="number" name="count" min="1" max="100000" value="{{ old('count', 1000) }}" class="input input-bordered input-sm w-40">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('In cuộn tem mới? Thao tác này sẽ sinh tem thật trong hệ thống.');">In tem</button>
            </form>

            @if(session('provisioned_range'))
            @php($range = session('provisioned_range'))
            <div class="alert alert-success py-2.5 px-4 mt-4 text-sm flex flex-wrap items-center gap-3">
                <span>Dải vừa in: prefix <strong class="font-mono">{{ $range['prefix'] ?: '(không có)' }}</strong> · <strong class="font-mono">{{ $range['from'] }}–{{ $range['to'] }}</strong></span>
                <a href="{{ route('backend.tag-rolls.download-csv', ['prefix' => $range['prefix'], 'from' => $range['from'], 'to' => $range['to']]) }}" class="link link-primary">Tải CSV</a>
                <a href="{{ route('backend.tag-rolls.download-pdf', ['prefix' => $range['prefix'], 'from' => $range['from'], 'to' => $range['to']]) }}" class="link link-primary">Tải PDF để in</a>
            </div>
            @endif
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-1">Tải lại tem đã in trước đây</h2>
            <p class="text-xs text-base-content/50 mb-3">Chọn đúng Prefix của cuộn (in nhỏ trên mỗi con tem) và dải <span class="font-mono">visual_sequence</span> để xuất lại file.</p>
            <form method="GET" action="{{ route('backend.tag-rolls.download-pdf') }}" class="flex flex-wrap items-end gap-2">
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Prefix</span></label>
                    <input type="text" name="prefix" list="prefixList" maxlength="10" required class="input input-bordered input-sm w-28 font-mono uppercase">
                    <datalist id="prefixList">
                        @foreach($prefixes as $p)
                        <option value="{{ $p }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Từ số</span></label>
                    <input type="number" name="from" min="1" required class="input input-bordered input-sm w-32 font-mono">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Đến số</span></label>
                    <input type="number" name="to" min="1" required class="input input-bordered input-sm w-32 font-mono">
                </div>
                <button type="submit" class="btn btn-outline btn-sm">Tải PDF</button>
                <button type="submit" formaction="{{ route('backend.tag-rolls.download-csv') }}" class="btn btn-ghost btn-sm">Tải CSV</button>
            </form>
        </div>
    </div>

</div>

<div class="card bg-base-100 shadow-sm border border-base-200 mt-6">
    <div class="card-body">
        <h2 class="text-base font-semibold mb-1">Lịch sử các cuộn đã in</h2>
        <p class="text-xs text-base-content/50 mb-3">Để gán sản phẩm cho một dải tem, vào trang chi tiết Lô hàng cần dán tem.</p>
    </div>
    <div class="card-body p-0 overflow-hidden tabulator-daisy">
        <div id="tag-roll-table"></div>
    </div>
</div>
</div>
@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Warehouse/resources/assets/js/warehouse.js',
    ], 'build/backend')
@endpush
