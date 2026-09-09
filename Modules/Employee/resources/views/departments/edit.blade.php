@extends('layouts.backend')
@section('title', 'Chỉnh sửa phòng ban')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa phòng ban</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $department->name }}</p>
    </div>
    <a href="{{ route('backend.departments.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.departments.update', $department) }}" novalidate>
    @csrf
    @method('PUT')

    <div class="card bg-base-100 shadow-sm border border-base-200 max-w-xl">
        <div class="card-body space-y-4">

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tên phòng ban <span class="text-error">*</span></span></label>
                <input type="text" name="name" value="{{ old('name', $department->name) }}"
                       class="input input-bordered input-sm w-full @error('name') input-error @enderror">
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-2 py-0">
                    <input type="checkbox" name="is_food_contact" value="1" class="checkbox checkbox-sm" @checked(old('is_food_contact', $department->is_food_contact))>
                    <span class="label-text text-sm">Trực tiếp tiếp xúc thực phẩm</span>
                </label>
                <p class="text-xs text-base-content/40 mt-1 ml-7">Nhân viên thuộc phòng ban này sẽ bị kiểm soát gắt gao hơn về hồ sơ y tế/ATTP.</p>
            </div>

        </div>
        <div class="card-body pt-0 flex-row justify-end gap-2 border-t border-base-200">
            <a href="{{ route('backend.departments.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button>
        </div>
    </div>
</form>
@endsection
