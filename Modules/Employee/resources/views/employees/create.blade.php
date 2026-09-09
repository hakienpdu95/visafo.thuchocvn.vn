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
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.employees.store') }}" enctype="multipart/form-data" novalidate>
    @csrf

    <div class="card bg-base-100 shadow-sm border border-base-200 max-w-2xl">
        <div class="card-body space-y-4">
            <h2 class="card-title text-base">Thông tin cá nhân</h2>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ảnh đại diện</span></label>
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                       class="file-input file-input-bordered file-input-sm w-full max-w-xs @error('avatar') file-input-error @enderror">
                @error('avatar')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span></label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}"
                           class="input input-bordered input-sm w-full @error('full_name') input-error @enderror">
                    @error('full_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Vai trò công việc</span></label>
                    <input type="text" name="job_title" value="{{ old('job_title') }}" placeholder="VD: Đầu bếp, Phụ bếp, Nhân viên kho, Lái xe"
                           class="input input-bordered input-sm w-full @error('job_title') input-error @enderror">
                    @error('job_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Email</span></label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="input input-bordered input-sm w-full @error('email') input-error @enderror">
                    @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Điện thoại</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="input input-bordered input-sm w-full @error('phone') input-error @enderror">
                    @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Facebook <span class="text-base-content/40 font-normal">(tùy chọn)</span></span></label>
                <input type="url" name="facebook_url" value="{{ old('facebook_url') }}" placeholder="https://facebook.com/..."
                       class="input input-bordered input-sm w-full @error('facebook_url') input-error @enderror">
                @error('facebook_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Thuộc phòng ban</span></label>
                <select id="department_ids" name="department_ids[]" multiple
                        class="select select-bordered select-sm w-full @error('department_ids') select-error @enderror">
                    @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected(collect(old('department_ids', []))->contains($department->id))>{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_ids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                @error('department_ids.*')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

        </div>
        <div class="card-body pt-0 flex-row justify-end gap-2 border-t border-base-200">
            <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm">Lưu nhân viên</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
@vite(['resources/js/modules/tom-select.js'], 'build/backend')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.initTomSelect('#department_ids', { plugins: ['remove_button'], placeholder: 'Chọn phòng ban...' });
});
</script>
@endpush
