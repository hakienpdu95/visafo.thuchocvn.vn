@extends('layouts.backend')
@section('title', 'Chỉnh sửa nhà cung cấp')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa nhà cung cấp</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $vendor->name }}</p>
    </div>
    <a href="{{ route('backend.vendors.show', $vendor) }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.vendors.update', $vendor) }}" novalidate data-vendor-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h18v4H3V3zm2 4h14v14H5V7zm3 4h8m-8 4h5"/>
                    </svg>
                    Thông tin pháp nhân
                </h2>

                <div class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên nhà cung cấp <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $vendor->name) }}"
                               data-req="Vui lòng nhập tên nhà cung cấp"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: Công ty TNHH ABC" autofocus>
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã nhà cung cấp</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                            </label>
                            <input type="text" name="vendor_code" value="{{ old('vendor_code', $vendor->vendor_code) }}"
                                   data-val-maxlength="50"
                                   class="input input-bordered input-sm w-full font-mono uppercase @error('vendor_code') input-error @enderror"
                                   placeholder="VD: NCC-001" maxlength="50">
                            <p class="mt-1 text-xs text-base-content/40">Chỉ chữ, số và dấu <code class="bg-base-200 px-1 rounded">-</code></p>
                            @error('vendor_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã số thuế <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="tax_code" value="{{ old('tax_code', $vendor->tax_code) }}"
                                   data-req="Vui lòng nhập mã số thuế"
                                   data-val-maxlength="50"
                                   class="input input-bordered input-sm w-full font-mono @error('tax_code') input-error @enderror"
                                   placeholder="0123456789" maxlength="50">
                            <p class="mt-1 text-xs text-base-content/40">10 hoặc 13 chữ số</p>
                            @error('tax_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Địa chỉ</span>
                            <span class="label-text-alt text-xs text-base-content/40">Trụ sở chính</span>
                        </label>
                        <input type="text" name="address" value="{{ old('address', $vendor->address) }}"
                               data-val-maxlength="500"
                               class="input input-bordered input-sm w-full @error('address') input-error @enderror"
                               placeholder="VD: 123 Nguyễn Trãi, Phường Bến Thành, Quận 1">
                        @error('address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

                <div class="divider my-4 text-xs text-base-content/30">Liên hệ</div>

                <div class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Điện thoại</span>
                            </label>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $vendor->phone_number) }}"
                                   data-val-maxlength="20"
                                   class="input input-bordered input-sm w-full @error('phone_number') input-error @enderror"
                                   placeholder="028 1234 5678">
                            @error('phone_number')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email', $vendor->email) }}"
                                   data-val-email="Email không đúng định dạng"
                                   class="input input-bordered input-sm w-full @error('email') input-error @enderror"
                                   placeholder="contact@company.com">
                            @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Người đại diện theo pháp luật</span>
                        </label>
                        <input type="text" name="representative_name" value="{{ old('representative_name', $vendor->representative_name) }}"
                               data-val-maxlength="100"
                               class="input input-bordered input-sm w-full @error('representative_name') input-error @enderror"
                               placeholder="VD: Nguyễn Văn A">
                        @error('representative_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

            </div>
        </div>

        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            @foreach(\Modules\Vendor\Enums\VendorStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $vendor->status->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $vendor->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $vendor->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.vendors.show', $vendor) }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu thay đổi
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
    @vite(['Modules/Vendor/resources/assets/sass/vendor.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Vendor/resources/assets/js/vendor.js',
    ], 'build/backend')
@endpush
