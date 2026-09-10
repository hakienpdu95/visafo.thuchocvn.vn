@extends('layouts.backend')
@section('title', 'Chỉnh sửa khách hàng')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa khách hàng</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $customer->name }}</p>
    </div>
    <a href="{{ route('backend.customers.show', $customer) }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.customers.update', $customer) }}" novalidate data-customer-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        <div class="space-y-6">

            {{-- ── Khối 1: Thông tin pháp nhân & phân loại ─────────────── --}}
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
                                <span class="label-text font-medium">Tên khách hàng <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $customer->name) }}"
                                   data-req="Vui lòng nhập tên khách hàng"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Công ty CP Đầu tư Vinakim" autofocus>
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Mã khách hàng</span>
                                    <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                                </label>
                                <input type="text" name="customer_code" value="{{ old('customer_code', $customer->customer_code) }}"
                                       data-val-maxlength="50"
                                       class="input input-bordered input-sm w-full font-mono uppercase @error('customer_code') input-error @enderror"
                                       placeholder="VD: KH-001" maxlength="50">
                                @error('customer_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Mã số thuế</span>
                                    <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                                </label>
                                <input type="text" name="tax_code" value="{{ old('tax_code', $customer->tax_code) }}"
                                       data-val-maxlength="20"
                                       class="input input-bordered input-sm w-full font-mono @error('tax_code') input-error @enderror"
                                       placeholder="0123456789" maxlength="20">
                                @error('tax_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Nhóm khách hàng <span class="text-error">*</span></span>
                                </label>
                                <select id="ts-customer_group" name="customer_group"
                                        class="select select-bordered select-sm w-full ts-init @error('customer_group') select-error @enderror"
                                        data-ts-placeholder="— Chọn nhóm khách hàng —">
                                    <option value=""></option>
                                    @foreach(\Modules\Customer\Enums\CustomerGroup::cases() as $group)
                                    <option value="{{ $group->value }}" @selected(old('customer_group', $customer->customer_group->value) === $group->value)>{{ $group->label() }}</option>
                                    @endforeach
                                </select>
                                @error('customer_group')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Mô hình tổ chức bữa ăn <span class="text-error">*</span></span>
                                    <span class="label-text-alt text-xs text-base-content/40">Quyết định bộ hồ sơ pháp lý</span>
                                </label>
                                <select id="ts-meal_model" name="meal_model"
                                        class="select select-bordered select-sm w-full ts-init @error('meal_model') select-error @enderror"
                                        data-ts-placeholder="— Chọn mô hình bữa ăn —">
                                    <option value=""></option>
                                    @foreach(\Modules\Customer\Enums\MealModel::cases() as $model)
                                    <option value="{{ $model->value }}" @selected(old('meal_model', $customer->meal_model->value) === $model->value)>{{ $model->label() }}</option>
                                    @endforeach
                                </select>
                                @error('meal_model')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nhân viên phụ trách</span>
                                <span class="label-text-alt text-xs text-base-content/40">Sales/Account Manager</span>
                            </label>
                            <select id="ts-pic_id" name="pic_id"
                                    class="select select-bordered select-sm w-full ts-init @error('pic_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn nhân viên phụ trách —">
                                <option value=""></option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('pic_id', $customer->pic_id) === $employee->id)>{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                            @error('pic_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <x-address-picker
                            :province-value="old('province_code', $customer->province_code)"
                            :ward-value="old('ward_code', $customer->ward_code)"
                            instance-id="customer-e"
                        />

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Địa chỉ</span>
                                <span class="label-text-alt text-xs text-base-content/40">Trụ sở chính / trường</span>
                            </label>
                            <input type="text" name="address" value="{{ old('address', $customer->address) }}"
                                   data-val-maxlength="500"
                                   class="input input-bordered input-sm w-full @error('address') input-error @enderror"
                                   placeholder="VD: 123 Nguyễn Trãi, Phường Bến Thành, Quận 1">
                            @error('address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="divider my-4 text-xs text-base-content/30">Liên hệ chung</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Điện thoại</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tổng đài / hóa đơn</span>
                            </label>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $customer->phone_number) }}"
                                   data-val-maxlength="20"
                                   class="input input-bordered input-sm w-full @error('phone_number') input-error @enderror"
                                   placeholder="028 1234 5678">
                            @error('phone_number')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tổng đài / hóa đơn</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                                   data-val-email="Email không đúng định dạng"
                                   class="input input-bordered input-sm w-full @error('email') input-error @enderror"
                                   placeholder="contact@company.com">
                            @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                </div>
            </div>

            {{-- ── Khối 2: Người đại diện theo pháp luật ───────────────── --}}
            <fieldset class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Người đại diện theo pháp luật
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Họ và tên</span>
                            </label>
                            <input type="text" name="representative_name"
                                   value="{{ old('representative_name', $customer->representative_name) }}"
                                   data-val-maxlength="100"
                                   class="input input-bordered input-sm w-full @error('representative_name') input-error @enderror"
                                   placeholder="VD: Nguyễn Văn A">
                            @error('representative_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chức danh</span>
                            </label>
                            <input type="text" name="representative_title"
                                   value="{{ old('representative_title', $customer->representative_title) }}"
                                   data-val-maxlength="100"
                                   class="input input-bordered input-sm w-full @error('representative_title') input-error @enderror"
                                   placeholder="VD: Giám đốc, Chủ tịch HĐQT">
                            @error('representative_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Điện thoại</span>
                            </label>
                            <input type="text" name="representative_phone"
                                   value="{{ old('representative_phone', $customer->representative_phone) }}"
                                   data-val-maxlength="20"
                                   class="input input-bordered input-sm w-full @error('representative_phone') input-error @enderror"
                                   placeholder="09xx xxx xxx">
                            @error('representative_phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Thư điện tử (Email)</span>
                            </label>
                            <input type="email" name="representative_email"
                                   value="{{ old('representative_email', $customer->representative_email) }}"
                                   data-val-email="Email không đúng định dạng"
                                   class="input input-bordered input-sm w-full @error('representative_email') input-error @enderror"
                                   placeholder="nguyenvana@company.com">
                            @error('representative_email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <p class="text-xs text-base-content/40 mt-4">
                        Đầu mối liên hệ về công việc và địa điểm giao hàng được quản lý trên trang chi tiết khách hàng.
                    </p>

                </div>
            </fieldset>

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
                            @foreach(\Modules\Customer\Enums\CustomerStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $customer->status->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $customer->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $customer->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.customers.show', $customer) }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
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
    @vite(['Modules/Customer/resources/assets/sass/customer.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Customer/resources/assets/js/customer.js',
    ], 'build/backend')
@endpush
