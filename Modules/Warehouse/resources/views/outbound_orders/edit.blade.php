@extends('layouts.backend')
@section('title', 'Chỉnh sửa đơn xuất buôn')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2 font-mono">
            {{ $order->order_number }}
            <span class="badge {{ $order->status->badgeClass() }} badge-sm">{{ $order->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">Chỉnh sửa thông tin đơn xuất buôn</p>
    </div>
    <a href="{{ route('backend.outbound-orders.show', $order) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.outbound-orders.update', $order) }}" novalidate data-outbound-order-form>
    @csrf
    @method('PUT')

    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Thông tin đơn xuất buôn
                </h2>

                <div class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã đơn xuất buôn <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="order_number" value="{{ old('order_number', $order->order_number) }}"
                                   data-req="Vui lòng nhập mã đơn xuất buôn"
                                   data-val-maxlength="100"
                                   class="input input-bordered input-sm w-full font-mono uppercase @error('order_number') input-error @enderror"
                                   placeholder="VD: DXB-260907-001" maxlength="100" autofocus>
                            @error('order_number')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày lập đơn <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="ordered_at" id="fp-ordered-at"
                                   value="{{ old('ordered_at', $order->ordered_at->format('Y-m-d')) }}"
                                   data-req="Vui lòng chọn ngày lập đơn"
                                   class="input input-bordered input-sm w-full fp-init @error('ordered_at') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('ordered_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên đại lý <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="dealer_name" value="{{ old('dealer_name', $order->dealer_name) }}"
                               data-req="Vui lòng nhập tên đại lý"
                               data-val-maxlength="255"
                               class="input input-bordered input-sm w-full @error('dealer_name') input-error @enderror"
                               placeholder="VD: Đại lý Minh Anh" maxlength="255">
                        @error('dealer_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Điện thoại đại lý</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                            </label>
                            <input type="text" name="dealer_phone" value="{{ old('dealer_phone', $order->dealer_phone) }}"
                                   data-val-maxlength="20"
                                   class="input input-bordered input-sm w-full @error('dealer_phone') input-error @enderror"
                                   placeholder="028 1234 5678" maxlength="20">
                            @error('dealer_phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Địa chỉ đại lý</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                            </label>
                            <input type="text" name="dealer_address" value="{{ old('dealer_address', $order->dealer_address) }}"
                                   data-val-maxlength="500"
                                   class="input input-bordered input-sm w-full @error('dealer_address') input-error @enderror"
                                   placeholder="VD: 123 Nguyễn Trãi, Quận 1" maxlength="500">
                            @error('dealer_address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ghi chú</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                        </label>
                        <textarea name="notes" rows="3"
                                  class="textarea textarea-bordered textarea-sm w-full @error('notes') textarea-error @enderror"
                                  placeholder="Ghi chú về đơn hàng...">{{ old('notes', $order->notes) }}</textarea>
                        @error('notes')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">Lưu thay đổi</button>
        <a href="{{ route('backend.outbound-orders.show', $order) }}" class="btn btn-ghost btn-sm">Hủy</a>
    </div>

</form>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',
        'Modules/Warehouse/resources/assets/js/warehouse.js',
    ], 'build/backend')
@endpush
