@extends('layouts.backend')
@section('title', 'Chỉnh sửa nhân viên')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $employee->full_name }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $employee->job_title ?: 'Chưa có vai trò công việc' }}</p>
    </div>
    <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

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

<div class="grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-6 items-start">

    <form method="POST" action="{{ route('backend.employees.update', $employee) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body space-y-4">
                <h2 class="card-title text-base">Thông tin cá nhân</h2>

                <div class="flex items-center gap-4">
                    @if($avatarUrl = $employee->getMediaUrl('avatar', 'thumb'))
                    <img src="{{ $avatarUrl }}" alt="{{ $employee->full_name }}" class="w-16 h-16 rounded-full object-cover border border-base-200">
                    @else
                    <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center text-base-content/30 text-xl font-semibold">
                        {{ mb_substr($employee->full_name, 0, 1) }}
                    </div>
                    @endif
                    <div class="form-control flex-1">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ảnh đại diện</span></label>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                               class="file-input file-input-bordered file-input-sm w-full @error('avatar') file-input-error @enderror">
                        @error('avatar')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span></label>
                        <input type="text" name="full_name" value="{{ old('full_name', $employee->full_name) }}"
                               class="input input-bordered input-sm w-full @error('full_name') input-error @enderror">
                        @error('full_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Vai trò công việc</span></label>
                        <input type="text" name="job_title" value="{{ old('job_title', $employee->job_title) }}"
                               class="input input-bordered input-sm w-full @error('job_title') input-error @enderror">
                        @error('job_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Email</span></label>
                        <input type="email" name="email" value="{{ old('email', $employee->email) }}"
                               class="input input-bordered input-sm w-full @error('email') input-error @enderror">
                        @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Điện thoại</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}"
                               class="input input-bordered input-sm w-full @error('phone') input-error @enderror">
                        @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Facebook <span class="text-base-content/40 font-normal">(tùy chọn)</span></span></label>
                    <input type="url" name="facebook_url" value="{{ old('facebook_url', $employee->facebook_url) }}"
                           class="input input-bordered input-sm w-full @error('facebook_url') input-error @enderror">
                    @error('facebook_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Thuộc phòng ban</span></label>
                    @php($employeeDeptIds = $employee->departments->pluck('id')->all())
                    <select id="department_ids" name="department_ids[]" multiple
                            class="select select-bordered select-sm w-full @error('department_ids') select-error @enderror">
                        @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(collect(old('department_ids', $employeeDeptIds))->contains($department->id))>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_ids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
            <div class="card-body pt-0 flex-row justify-end gap-2 border-t border-base-200">
                <button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button>
            </div>
        </div>
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body space-y-4">
            <h2 class="card-title text-base">Hồ sơ Y tế & ATTP</h2>

            <div class="flex gap-2">
                @php($healthStatus = $employee->healthCheckStatus())
                @php($attpStatus = $employee->attpTrainingStatus())
                <span class="badge {{ $healthStatus->badgeClass() }} badge-sm gap-1">Khám SK: {{ $healthStatus->label() }}</span>
                <span class="badge {{ $attpStatus->badgeClass() }} badge-sm gap-1">ATTP: {{ $attpStatus->label() }}</span>
            </div>

            @if($employee->healthRecords->isEmpty())
            <p class="text-xs text-base-content/50">Chưa có hồ sơ y tế/ATTP nào.</p>
            @else
            <div class="overflow-x-auto -mx-2">
                <table class="table table-xs">
                    <thead>
                        <tr>
                            <th>Loại</th>
                            <th>Ngày cấp</th>
                            <th>Hết hạn</th>
                            <th>Chi tiết</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employee->healthRecords as $record)
                        <tr>
                            <td class="whitespace-nowrap">{{ $record->record_type->label() }}</td>
                            <td class="whitespace-nowrap">{{ $record->issue_date->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap">
                                @if($record->expiry_date)
                                    <span class="{{ $record->isExpired() ? 'text-error' : ($record->isExpiringWithinDays(30) ? 'text-warning' : '') }}">
                                        {{ $record->expiry_date->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-base-content/30">—</span>
                                @endif
                            </td>
                            <td class="text-xs text-base-content/60">
                                {{ $record->result ?: $record->certificate_number ?: '—' }}
                                @if($fileUrl = $record->getMediaUrl('attachments_private'))
                                    · <a href="{{ $fileUrl }}" target="_blank" class="link link-primary">File</a>
                                @endif
                            </td>
                            <td>
                                @can('update', $employee)
                                <form method="POST" action="{{ route('backend.employees.health-records.destroy', [$employee, $record]) }}" onsubmit="return confirm('Xóa hồ sơ này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @can('update', $employee)
            <form method="POST" action="{{ route('backend.employees.health-records.store', $employee) }}" enctype="multipart/form-data" novalidate class="space-y-3 pt-3 border-t border-base-200">
                @csrf
                <p class="text-xs font-medium text-base-content/60">Thêm hồ sơ mới</p>

                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại hồ sơ</span></label>
                    <select id="record_type" name="record_type" class="select select-bordered select-sm w-full">
                        <option value="health_check">Giấy khám sức khỏe</option>
                        <option value="attp_training">Giấy xác nhận tập huấn kiến thức ATTP</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày khám / ngày cấp</span></label>
                        <input type="date" name="issue_date" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày hết hạn <span class="text-base-content/40 font-normal">(để trống = tự tính)</span></span></label>
                        <input type="date" name="expiry_date" class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div id="field-health-check" class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Kết luận</span></label>
                    <select name="result" class="select select-bordered select-sm w-full">
                        <option value="">— Chọn —</option>
                        <option value="qualified">Đủ điều kiện</option>
                        <option value="not_qualified">Không đủ điều kiện</option>
                    </select>
                </div>

                <div id="field-attp-training" class="hidden space-y-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số giấy xác nhận</span></label>
                        <input type="text" name="certificate_number" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Cơ quan cấp</span></label>
                        <input type="text" name="issued_by" class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">File đính kèm (PDF/Ảnh)</span></label>
                    <input type="file" name="file" accept="application/pdf,image/jpeg,image/png" class="file-input file-input-bordered file-input-sm w-full">
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-full">Thêm hồ sơ</button>
            </form>
            @endcan

        </div>
    </div>

</div>
@endsection

@push('scripts')
@vite(['resources/js/modules/tom-select.js'], 'build/backend')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.initTomSelect('#department_ids', { plugins: ['remove_button'], placeholder: 'Chọn phòng ban...' });

    var typeSelect  = document.getElementById('record_type');
    var healthField = document.getElementById('field-health-check');
    var attpField   = document.getElementById('field-attp-training');

    function toggleFields() {
        var isHealthCheck = typeSelect.value === 'health_check';
        healthField.classList.toggle('hidden', !isHealthCheck);
        attpField.classList.toggle('hidden', isHealthCheck);
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', toggleFields);
        toggleFields();
    }
});
</script>
@endpush
