@extends('layouts.backend')
@section('title', 'Thêm nhân viên')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm nhân viên mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Sau khi lưu, bạn có thể bổ sung hồ sơ y tế/ATTP cho nhân viên</p>
    </div>
    <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.employees.store') }}" enctype="multipart/form-data" novalidate data-employee-form>
    @csrf

    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Thông tin cá nhân</h2>

                <div class="form-control mb-4">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ảnh đại diện</span></label>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                           class="file-input file-input-bordered file-input-sm w-full max-w-xs @error('avatar') file-input-error @enderror">
                    @error('avatar')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}"
                               data-req="Vui lòng nhập họ và tên"
                               data-val-maxlength="150"
                               class="input input-bordered input-sm w-full @error('full_name') input-error @enderror"
                               placeholder="VD: Nguyễn Văn A">
                        @error('full_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Vai trò công việc</span></label>
                        <input type="text" name="job_title" value="{{ old('job_title') }}"
                               data-val-maxlength="100"
                               class="input input-bordered input-sm w-full @error('job_title') input-error @enderror"
                               placeholder="VD: Đầu bếp, Phụ bếp, Nhân viên kho, Lái xe">
                        @error('job_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Điện thoại</span></label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               data-val-maxlength="20"
                               class="input input-bordered input-sm w-full @error('phone') input-error @enderror"
                               placeholder="VD: 0912345678">
                        @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Email</span></label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               data-val-email="Email không đúng định dạng"
                               data-val-maxlength="150"
                               class="input input-bordered input-sm w-full @error('email') input-error @enderror"
                               placeholder="contact@company.com">
                        @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Facebook</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tùy chọn</span>
                        </label>
                        <input type="url" name="facebook_url" value="{{ old('facebook_url') }}"
                               data-val-url="Đường dẫn Facebook không hợp lệ"
                               data-val-maxlength="255"
                               class="input input-bordered input-sm w-full @error('facebook_url') input-error @enderror"
                               placeholder="https://facebook.com/...">
                        @error('facebook_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control mt-4">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Thuộc phòng ban</span></label>
                    <select id="ts-department_ids" name="department_ids[]" multiple
                            data-ts-placeholder="Chọn phòng ban..."
                            class="select select-bordered select-sm w-full @error('department_ids') select-error @enderror">
                        @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(collect(old('department_ids', []))->contains($department->id))>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_ids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    @error('department_ids.*')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>
    </div>

    <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">Lưu nhân viên</button>
        <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    </div>

</form>
@endsection

@push('styles')
    @vite(['Modules/Employee/resources/assets/sass/employee.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Employee/resources/assets/js/employee.js',
    ], 'build/backend')
@endpush
