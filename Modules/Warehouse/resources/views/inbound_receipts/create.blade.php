@extends('layouts.backend')
@section('title', 'Tạo phiếu nhập kho')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo phiếu nhập kho</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Ghi nhận một chuyến hàng nhận từ nhà cung cấp</p>
    </div>
    <a href="{{ route('backend.inbound-receipts.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.inbound-receipts.store') }}" novalidate data-inbound-receipt-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Thông tin phiếu nhập
                </h2>

                <div class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã phiếu nhập <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="receipt_number" value="{{ old('receipt_number') }}"
                                   data-req="Vui lòng nhập mã phiếu nhập"
                                   data-val-maxlength="100"
                                   class="input input-bordered input-sm w-full font-mono uppercase @error('receipt_number') input-error @enderror"
                                   placeholder="VD: PNK-260907-001" maxlength="100" autofocus>
                            @error('receipt_number')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày nhận hàng <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="received_date" id="fp-received-date"
                                   value="{{ old('received_date') }}"
                                   data-req="Vui lòng chọn ngày nhận hàng"
                                   class="input input-bordered input-sm w-full fp-init @error('received_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('received_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhà cung cấp <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-vendor_id" name="vendor_id"
                                class="select select-bordered select-sm w-full ts-init @error('vendor_id') select-error @enderror"
                                data-ts-placeholder="— Chọn nhà cung cấp —"
                                data-req="Vui lòng chọn nhà cung cấp">
                            <option value="">— Chọn nhà cung cấp —</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(old('vendor_id') === $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ghi chú</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                        </label>
                        <textarea name="notes" rows="3"
                                  class="textarea textarea-bordered textarea-sm w-full @error('notes') textarea-error @enderror"
                                  placeholder="Ghi chú về chuyến hàng, tình trạng giao nhận...">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

            </div>
        </div>

        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            @foreach(\Modules\Warehouse\Enums\InboundReceiptStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', 'draft') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.inbound-receipts.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo mới
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>
        </div>

    </div>
</form>
@endsection

@push('styles')
    @vite(['Modules/Warehouse/resources/assets/sass/warehouse.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/Warehouse/resources/assets/js/warehouse.js',
    ], 'build/backend')
@endpush
