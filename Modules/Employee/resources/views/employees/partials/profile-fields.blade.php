@php
    $employee = $employee ?? null;
    $dateValue = fn (string $field) => old($field, $employee?->{$field}?->format('Y-m-d') ?? '');
@endphp

<div class="flex items-center gap-4 mb-5">
    @if($avatarUrl = $employee?->getMediaUrl('avatar', 'thumb'))
    <img src="{{ $avatarUrl }}" alt="{{ $employee->full_name }}" class="w-16 h-16 rounded-full object-cover border border-base-200">
    @else
    <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center text-base-content/30 text-xl font-semibold shrink-0">
        {{ $employee ? mb_substr($employee->full_name, 0, 1) : '?' }}
    </div>
    @endif
    <div class="form-control flex-1">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ảnh đại diện</span></label>
        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
               class="file-input file-input-bordered file-input-sm w-full @error('avatar') file-input-error @enderror">
        @error('avatar')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="form-control sm:col-span-2">
        <label class="label py-0 pb-1.5">
            <span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span>
        </label>
        <input type="text" name="full_name" value="{{ old('full_name', $employee?->full_name) }}"
               data-req="Vui lòng nhập họ và tên"
               data-val-maxlength="150"
               class="input input-bordered input-sm w-full @error('full_name') input-error @enderror"
               placeholder="VD: Nguyễn Văn A">
        @error('full_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Giới tính</span></label>
        <select id="ts-gender" name="gender" data-ts-placeholder="— Chọn giới tính —"
                class="select select-bordered select-sm w-full ts-init @error('gender') select-error @enderror">
            <option value="">— Chọn giới tính —</option>
            @foreach(\Modules\Employee\Enums\Gender::cases() as $gender)
            <option value="{{ $gender->value }}" @selected(old('gender', $employee?->gender?->value) === $gender->value)>{{ $gender->label() }}</option>
            @endforeach
        </select>
        @error('gender')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngày sinh</span></label>
        <input type="text" name="dob" id="fp-dob" value="{{ $dateValue('dob') }}"
               class="input input-bordered input-sm w-full fp-init @error('dob') input-error @enderror"
               placeholder="DD/MM/YYYY">
        @error('dob')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Vai trò công việc</span></label>
        <input type="text" name="job_title" value="{{ old('job_title', $employee?->job_title) }}"
               data-val-maxlength="100"
               class="input input-bordered input-sm w-full @error('job_title') input-error @enderror"
               placeholder="VD: Đầu bếp, Phụ bếp, Nhân viên kho, Lái xe">
        @error('job_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngày bắt đầu làm việc</span></label>
        <input type="text" name="start_date" id="fp-start-date" value="{{ $dateValue('start_date') }}"
               class="input input-bordered input-sm w-full fp-init @error('start_date') input-error @enderror"
               placeholder="DD/MM/YYYY">
        @error('start_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control sm:col-span-2">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Thuộc phòng ban</span></label>
        @php($employeeDeptIds = $employee?->departments->pluck('id')->all() ?? [])
        <select id="ts-department_ids" name="department_ids[]" multiple
                data-ts-placeholder="Chọn phòng ban..."
                class="select select-bordered select-sm w-full @error('department_ids') select-error @enderror">
            @foreach($departments as $department)
            <option value="{{ $department->id }}" @selected(collect(old('department_ids', $employeeDeptIds))->contains($department->id))>{{ $department->name }}</option>
            @endforeach
        </select>
        @error('department_ids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        @error('department_ids.*')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>
</div>

<div class="divider my-4 text-xs text-base-content/30">Thông tin liên hệ</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="form-control">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Điện thoại</span></label>
        <input type="text" name="phone" value="{{ old('phone', $employee?->phone) }}"
               data-val-maxlength="20"
               class="input input-bordered input-sm w-full @error('phone') input-error @enderror"
               placeholder="VD: 0912345678">
        @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Email</span></label>
        <input type="email" name="email" value="{{ old('email', $employee?->email) }}"
               data-val-email="Email không đúng định dạng"
               data-val-maxlength="150"
               class="input input-bordered input-sm w-full @error('email') input-error @enderror"
               placeholder="contact@company.com">
        @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control sm:col-span-2">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Địa chỉ</span></label>
        <input type="text" name="address" value="{{ old('address', $employee?->address) }}"
               data-val-maxlength="500"
               class="input input-bordered input-sm w-full @error('address') input-error @enderror"
               placeholder="VD: Số 12, ngõ 5, thôn Kim Nỗ, xã Thiên Lộc, Hà Nội">
        @error('address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control sm:col-span-2">
        <label class="label py-0 pb-1.5">
            <span class="label-text font-medium">Facebook</span>
            <span class="label-text-alt text-xs text-base-content/40">Tùy chọn</span>
        </label>
        <input type="url" name="facebook_url" value="{{ old('facebook_url', $employee?->facebook_url) }}"
               data-val-url="Đường dẫn Facebook không hợp lệ"
               data-val-maxlength="255"
               class="input input-bordered input-sm w-full @error('facebook_url') input-error @enderror"
               placeholder="https://facebook.com/...">
        @error('facebook_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>
</div>

<div class="divider my-4 text-xs text-base-content/30">Hồ sơ định danh (CCCD)</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="form-control">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Số CCCD</span></label>
        <input type="text" name="id_card_number" value="{{ old('id_card_number', $employee?->id_card_number) }}"
               inputmode="numeric" maxlength="12"
               data-val-pattern="^\d{12}$" data-val-pattern-msg="Số CCCD phải đủ 12 chữ số"
               class="input input-bordered input-sm w-full font-mono tracking-wider @error('id_card_number') input-error @enderror"
               placeholder="VD: 001090012345">
        @error('id_card_number')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngày cấp</span></label>
        <input type="text" name="id_card_issue_date" id="fp-id-card-issue-date" value="{{ $dateValue('id_card_issue_date') }}"
               class="input input-bordered input-sm w-full fp-init @error('id_card_issue_date') input-error @enderror"
               placeholder="DD/MM/YYYY">
        @error('id_card_issue_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control sm:col-span-2">
        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Nơi cấp</span></label>
        <input type="text" name="id_card_issue_place" value="{{ old('id_card_issue_place', $employee?->id_card_issue_place) }}"
               data-val-maxlength="255"
               class="input input-bordered input-sm w-full @error('id_card_issue_place') input-error @enderror"
               placeholder="VD: Cục Cảnh sát QLHC về TTXH">
        @error('id_card_issue_place')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    @include('employee::employees.partials.id-card-upload', ['field' => 'id_card_front', 'label' => 'CCCD Mặt trước'])
    @include('employee::employees.partials.id-card-upload', ['field' => 'id_card_back', 'label' => 'CCCD Mặt sau'])
</div>
<p class="mt-2 text-xs text-base-content/40">🔒 Ảnh CCCD được lưu riêng tư, chỉ người có quyền quản lý nhân sự xem được qua đường dẫn tạm thời.</p>
