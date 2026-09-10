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

<form method="POST" action="{{ route('backend.departments.update', $department) }}" novalidate data-department-form>
    @csrf
    @method('PUT')

    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Thông tin phòng ban</h2>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên phòng ban <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $department->name) }}"
                           data-req="Vui lòng nhập tên phòng ban"
                           data-val-maxlength="150"
                           class="input input-bordered input-sm w-full @error('name') input-error @enderror">
                    @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control mt-4">
                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="checkbox" name="is_food_contact" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               @checked(old('is_food_contact', $department->is_food_contact))>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Trực tiếp tiếp xúc thực phẩm</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Nhân viên thuộc phòng ban này sẽ bị kiểm soát gắt gao hơn về hồ sơ y tế/ATTP.</p>
                        </div>
                    </label>
                </div>

            </div>
        </div>
    </div>

    <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">Lưu thay đổi</button>
        <a href="{{ route('backend.departments.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    </div>

</form>
@endsection

@push('styles')
    @vite(['Modules/Employee/resources/assets/sass/employee.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite(['Modules/Employee/resources/assets/js/employee.js'], 'build/backend')
@endpush
