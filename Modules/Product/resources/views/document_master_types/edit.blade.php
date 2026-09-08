@extends('layouts.backend')
@section('title', 'Chỉnh sửa loại giấy tờ')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa loại giấy tờ</h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">{{ $documentMasterType->code }}</p>
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
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.document-master-types.update', $documentMasterType) }}" novalidate>
    @csrf
    @method('PUT')

    <div class="card bg-base-100 shadow-sm border border-base-200 max-w-2xl">
        <div class="card-body space-y-4">

            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mã loại giấy tờ <span class="text-error">*</span></span></label>
                    <input type="text" name="code" value="{{ old('code', $documentMasterType->code) }}"
                           class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror">
                    @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngành hàng áp dụng <span class="text-error">*</span></span></label>
                    <select name="applicable_category" class="select select-bordered select-sm w-full @error('applicable_category') select-error @enderror">
                        @foreach($categoryTypes as $category)
                        <option value="{{ $category->value }}" @selected(old('applicable_category', $documentMasterType->applicable_category->value) === $category->value)>{{ $category->label() }}</option>
                        @endforeach
                    </select>
                    @error('applicable_category')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tên loại giấy tờ <span class="text-error">*</span></span></label>
                <input type="text" name="name" value="{{ old('name', $documentMasterType->name) }}"
                       class="input input-bordered input-sm w-full @error('name') input-error @enderror">
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-2 py-0">
                        <input type="checkbox" name="is_required_issue_date" value="1" class="checkbox checkbox-sm" @checked(old('is_required_issue_date', $documentMasterType->is_required_issue_date))>
                        <span class="label-text text-sm">Bắt buộc ngày cấp</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-2 py-0">
                        <input type="checkbox" name="is_required_expiry_date" value="1" class="checkbox checkbox-sm" @checked(old('is_required_expiry_date', $documentMasterType->is_required_expiry_date))>
                        <span class="label-text text-sm">Bắt buộc ngày hết hạn</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Hiệu lực mặc định (tháng)</span></label>
                    <input type="number" name="default_validity_months" value="{{ old('default_validity_months', $documentMasterType->default_validity_months) }}" min="1"
                           class="input input-bordered input-sm w-full @error('default_validity_months') input-error @enderror">
                    @error('default_validity_months')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

        </div>
        <div class="card-body pt-0 flex-row justify-end gap-2 border-t border-base-200">
            <a href="{{ route('backend.document-master-types.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button>
        </div>
    </div>
</form>
@endsection
