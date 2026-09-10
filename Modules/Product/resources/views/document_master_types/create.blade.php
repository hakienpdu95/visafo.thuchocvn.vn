@extends('layouts.backend')
@section('title', 'Thêm loại giấy tờ')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm loại giấy tờ mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Định nghĩa một loại hồ sơ pháp lý mới cho hệ thống</p>
    </div>
    <a href="{{ route('backend.document-master-types.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.document-master-types.store') }}" novalidate data-document-master-type-form>
    @csrf

    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Thông tin loại giấy tờ</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mã loại giấy tờ</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code') }}"
                               data-val-maxlength="60"
                               class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror"
                               placeholder="vd: facility_attp (Để trống hệ thống sẽ tự tạo LGT-000001)">
                        <p class="mt-1 text-xs text-base-content/40">Tự động gợi ý theo tên khi gõ — sửa tay để khoá, hoặc xoá trống để hệ thống tự sinh mã khi lưu.</p>
                        @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhóm giấy tờ <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-document_group" name="document_group"
                                data-req="Vui lòng chọn nhóm giấy tờ"
                                data-ts-placeholder="— Chọn nhóm —"
                                class="select select-bordered select-sm w-full ts-init @error('document_group') select-error @enderror">
                            <option value="">— Chọn nhóm —</option>
                            @foreach($documentGroups as $group)
                            <option value="{{ $group->value }}" @selected(old('document_group') === $group->value)>{{ $group->label() }}</option>
                            @endforeach
                        </select>
                        @error('document_group')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control mt-4">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên loại giấy tờ <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           data-req="Vui lòng nhập tên loại giấy tờ"
                           data-val-maxlength="255"
                           class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                           placeholder="VD: Giấy chứng nhận ATTP cơ sở">
                    @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="checkbox" name="is_required_issue_date" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               @checked(old('is_required_issue_date', true))>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Bắt buộc ngày cấp</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Yêu cầu nhập ngày cấp khi thêm hồ sơ loại giấy tờ này.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="checkbox" name="is_required_expiry_date" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               @checked(old('is_required_expiry_date'))>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Bắt buộc ngày hết hạn</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Yêu cầu nhập ngày hết hạn khi thêm hồ sơ loại giấy tờ này.</p>
                        </div>
                    </label>
                </div>

                <div class="form-control mt-4 sm:max-w-[calc(50%-0.5rem)]">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Hiệu lực mặc định (tháng)</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tùy chọn</span>
                    </label>
                    <input type="number" name="default_validity_months" value="{{ old('default_validity_months') }}" min="1"
                           data-val-pattern="^[0-9]*$"
                           class="input input-bordered input-sm w-full @error('default_validity_months') input-error @enderror"
                           placeholder="VD: 12">
                    @error('default_validity_months')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>
    </div>

    <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">Lưu loại giấy tờ</button>
        <a href="{{ route('backend.document-master-types.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    </div>

</form>
@endsection

@push('styles')
    @vite(['Modules/Product/resources/assets/sass/product.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
