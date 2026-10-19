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

<div class="grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-6 items-start">

    <form method="POST" action="{{ route('backend.employees.update', $employee) }}" enctype="multipart/form-data" novalidate data-employee-form>
        @csrf
        @method('PUT')

        <div class="space-y-5">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">Thông tin cá nhân</h2>

                    @include('employee::employees.partials.profile-fields', ['employee' => $employee])

                </div>
            </div>
        </div>

        <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
            <button type="submit" class="btn btn-primary btn-sm gap-1.5">Lưu thay đổi</button>
            <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
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
            <form method="POST" action="{{ route('backend.employees.health-records.store', $employee) }}" enctype="multipart/form-data" novalidate class="space-y-3 pt-3 border-t border-base-200" data-health-record-form>
                @csrf
                <p class="text-xs font-medium text-base-content/60">Thêm hồ sơ mới</p>

                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại hồ sơ</span></label>
                    <select id="ts-record_type" name="record_type"
                            class="select select-bordered select-sm w-full ts-init">
                        <option value="health_check">Giấy khám sức khỏe</option>
                        <option value="attp_training">Giấy xác nhận tập huấn kiến thức ATTP</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày khám / ngày cấp</span></label>
                        <input type="text" name="issue_date" id="fp-issue-date"
                               data-req="Vui lòng chọn ngày khám/ngày cấp"
                               class="input input-bordered input-sm w-full fp-init"
                               placeholder="DD/MM/YYYY">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày hết hạn <span class="text-base-content/40 font-normal">(để trống = tự tính)</span></span></label>
                        <input type="text" name="expiry_date" id="fp-expiry-date"
                               class="input input-bordered input-sm w-full fp-init"
                               placeholder="DD/MM/YYYY">
                    </div>
                </div>

                <div id="field-health-check" class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Kết luận</span></label>
                    <select id="ts-result" name="result"
                            data-ts-placeholder="— Chọn —"
                            class="select select-bordered select-sm w-full ts-init">
                        <option value="">— Chọn —</option>
                        <option value="qualified">Đủ điều kiện</option>
                        <option value="not_qualified">Không đủ điều kiện</option>
                    </select>
                </div>

                <div id="field-attp-training" class="hidden space-y-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số giấy xác nhận</span></label>
                        <input type="text" name="certificate_number" data-val-maxlength="100"
                               class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Cơ quan cấp</span></label>
                        <input type="text" name="issued_by" data-val-maxlength="255"
                               class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">File đính kèm (PDF/Ảnh)</span><span class="label-text-alt text-xs text-base-content/40">tối đa 100MB</span></label>
                    <input type="file" name="file" accept="application/pdf,image/jpeg,image/png" class="file-input file-input-bordered file-input-sm w-full">
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-full">Thêm hồ sơ</button>
            </form>
            @endcan

        </div>
    </div>

</div>
@endsection

@push('styles')
    @vite(['Modules/Employee/resources/assets/sass/employee.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/Employee/resources/assets/js/employee.js',
    ], 'build/backend')
@endpush
