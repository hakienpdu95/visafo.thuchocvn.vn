@extends('layouts.backend')
@section('title', 'Hồ sơ năng lực VISAFO')

@php
    use Modules\Compliance\Models\InternalFacility;

    $groupLabels = [
        'legal_facility' => 'Pháp lý (NL-PL)',
        'attp_quality'   => 'ATTP (NL-ATTP)',
    ];

    $buildDocRows = function (InternalFacility $facility) use ($groupLabels) {
        return $facility->documents->map(fn ($d) => [
            'id'                      => $d->id,
            'facility_id'             => $facility->id,
            'document_master_type_id' => $d->document_master_type_id,
            'type_name'               => $d->documentType->name,
            'group_label'             => $groupLabels[$d->documentType->document_group->value] ?? $d->documentType->document_group->label(),
            'document_number'         => $d->document_number,
            'issued_by'               => $d->issued_by,
            'issue_date'              => $d->issue_date?->format('Y-m-d'),
            'issue_date_display'      => $d->issue_date?->format('d/m/Y'),
            'expiration_date'         => $d->expiration_date?->format('Y-m-d'),
            'expiration_date_display' => $d->expiration_date?->format('d/m/Y'),
            'is_expired'              => $d->isExpired(),
            'is_expiring_soon'        => $d->isExpiringWithinDays(30),
            'status_label'            => $d->status->label(),
            'status_badge'            => $d->status->badgeClass(),
            'file_url'                => $d->getFirstMediaUrl('attachments_private'),
            'update_url'              => route('backend.internal-facilities.documents.update', [$facility, $d->id]),
        ])->values();
    };

    $modalIsEdit = (bool) old('_document_id');
    $modalFacilityId = old('_target_facility_id', $headquarter->id);
@endphp

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Hồ sơ năng lực VISAFO</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Hồ sơ pháp lý & chứng nhận nội bộ — QT-TXNG-01 nhóm NL</p>
    </div>
    <div class="flex items-center gap-2">
        @can('create', \Modules\Compliance\Models\InternalFacility::class)
        <button type="button" class="btn btn-ghost btn-sm gap-1.5" onclick="openFacilityModal()">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm cơ sở
        </button>
        @endcan
        <a href="{{ route('backend.internal-compliance.export') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M7 10l5 5 5-5M12 15V3"/></svg>
            Xuất Hồ sơ (ZIP)
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error py-2.5 px-4 mb-5 text-sm">{{ session('error') }}</div>
@endif

<div class="space-y-6" x-data="{ openFacility: {{ Js::from((string) old('_target_facility_id', '')) }} }"
     x-init="if (!openFacility) openFacility = (window.location.hash || '').replace('#facility-', '')">

    {{-- ── Khối 1 · Hồ sơ Cấp Công ty (Global) ──────────────────────────────── --}}
    <div>
        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Khối 1 · Hồ sơ cấp Công ty</p>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="card-title text-base mb-0.5">{{ $headquarter->name }}</h2>
                        @if($headquarter->address)
                        <p class="text-xs text-base-content/40">{{ $headquarter->address }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @can('update', $headquarter)
                        @if($canManageDocuments)
                        <button type="button" class="btn btn-primary btn-sm" onclick="openAddDocumentModal('{{ $headquarter->id }}')">Tải lên hồ sơ mới</button>
                        @endif
                        <button type="button" class="btn btn-ghost btn-sm"
                                onclick="openFacilityModal({{ json_encode(['id' => $headquarter->id, 'name' => $headquarter->name, 'type' => $headquarter->type, 'address' => $headquarter->address, 'status' => $headquarter->status], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }})">Sửa</button>
                        @endcan
                    </div>
                </div>

                <div class="tabulator-daisy">
                    <div id="hq-doc-table"
                         data-can-manage="{{ $canManageDocuments ? '1' : '0' }}"
                         data-delete-url-template="{{ route('backend.internal-facilities.documents.destroy', [$headquarter, '__ID__']) }}"
                         data-rows="{{ json_encode($buildDocRows($headquarter), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Khối 2 · Hồ sơ Cấp Cơ sở (Local) ──────────────────────────────────── --}}
    <div>
        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Khối 2 · Hồ sơ cấp Cơ sở</p>

        @if($localFacilities->isEmpty())
        <div class="card bg-base-100 border border-dashed border-base-300">
            <div class="card-body py-10 text-center text-base-content/40">
                <p class="text-sm">Chưa có cơ sở nào (Kho, Vùng trồng, Khu sơ chế...) được khai báo.</p>
                @can('create', \Modules\Compliance\Models\InternalFacility::class)
                <button type="button" class="btn btn-primary btn-sm mt-3 mx-auto" onclick="openFacilityModal()">+ Thêm cơ sở</button>
                @endcan
            </div>
        </div>
        @else
        <div class="space-y-2">
            @foreach($localFacilities as $facility)
            @php $fid = $facility->id; @endphp
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <button type="button"
                        class="w-full flex items-center justify-between gap-3 p-4 text-left"
                        @click="openFacility = (openFacility === '{{ $fid }}') ? '' : '{{ $fid }}'; if (openFacility === '{{ $fid }}') window.onFacilityAccordionOpen?.('facility-doc-table-{{ $fid }}')">
                    <div class="min-w-0">
                        <p class="font-semibold text-sm truncate">{{ $facility->name }}</p>
                        <p class="text-xs text-base-content/40 truncate">
                            {{ $facility->typeLabel() }}@if($facility->address) — {{ $facility->address }} @endif
                            @if($facility->status === 'inactive') <span class="badge badge-ghost badge-xs ml-1">Ngừng hoạt động</span> @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="badge badge-neutral badge-xs">{{ $facility->documents->count() }} hồ sơ</span>
                        <svg class="w-4 h-4 text-base-content/40 transition-transform" :class="openFacility === '{{ $fid }}' ? 'rotate-180' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                <div x-show="openFacility === '{{ $fid }}'" x-transition x-cloak>
                    <div class="card-body pt-0 border-t border-base-200">
                        <div class="flex items-center justify-end gap-2 mb-3 mt-4">
                            @can('update', $facility)
                            @if($canManageDocuments)
                            <button type="button" class="btn btn-primary btn-sm" onclick="openAddDocumentModal('{{ $fid }}')">Tải lên hồ sơ mới</button>
                            @endif
                            <button type="button" class="btn btn-ghost btn-sm"
                                    onclick="openFacilityModal({{ json_encode(['id' => $facility->id, 'name' => $facility->name, 'type' => $facility->type, 'address' => $facility->address, 'status' => $facility->status], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }})">Sửa cơ sở</button>
                            @endcan
                        </div>

                        <div class="tabulator-daisy">
                            <div id="facility-doc-table-{{ $fid }}"
                                 data-can-manage="{{ $canManageDocuments ? '1' : '0' }}"
                                 data-delete-url-template="{{ route('backend.internal-facilities.documents.destroy', [$facility, '__ID__']) }}"
                                 data-rows="{{ json_encode($buildDocRows($facility), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Khối 3 · Năng lực Nhân sự (Auto-Sync từ module Nhân sự) ──────────── --}}
    <div>
        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Khối 3 · Năng lực Nhân sự (tự động đồng bộ)</p>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="card-title text-base mb-0">Tổng quan nhân sự</h2>
                    @can('employee.view')
                    <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-xs">Xem module Nhân sự →</a>
                    @endcan
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                    <div class="stat bg-base-200/50 rounded-xl py-3 px-4">
                        <div class="stat-title text-xs">Tổng số nhân sự</div>
                        <div class="stat-value text-2xl">{{ number_format($employeeStats['total']) }}</div>
                        <div class="stat-desc">đang được theo dõi hồ sơ</div>
                    </div>
                    <div class="stat bg-base-200/50 rounded-xl py-3 px-4">
                        <div class="stat-title text-xs">Giấy khám SK còn hạn</div>
                        <div class="stat-value text-2xl {{ $employeeStats['health_valid_pct'] < 80 ? 'text-error' : 'text-success' }}">{{ $employeeStats['health_valid_pct'] }}%</div>
                        <div class="stat-desc">{{ $employeeStats['health_valid'] }}/{{ $employeeStats['total'] }} nhân sự</div>
                    </div>
                    <div class="stat bg-base-200/50 rounded-xl py-3 px-4">
                        <div class="stat-title text-xs">Chứng nhận ATTP còn hạn</div>
                        <div class="stat-value text-2xl {{ $employeeStats['attp_valid_pct'] < 80 ? 'text-error' : 'text-success' }}">{{ $employeeStats['attp_valid_pct'] }}%</div>
                        <div class="stat-desc">{{ $employeeStats['attp_valid'] }}/{{ $employeeStats['total'] }} nhân sự</div>
                    </div>
                </div>

                @if(count($employeeStats['at_risk']))
                <p class="text-xs font-medium text-base-content/50 mb-2">Nhân sự thiếu / sắp hết hạn giấy tờ — cần bổ sung</p>
                <div class="tabulator-daisy">
                    <div id="employee-risk-table" data-rows="{{ json_encode($employeeStats['at_risk'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"></div>
                </div>
                @else
                <div class="alert alert-success py-2.5 px-4 text-sm">Toàn bộ nhân sự đều có đủ giấy tờ còn hiệu lực.</div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ── Modal: Tải lên / Sửa hồ sơ năng lực (dùng chung cho Khối 1 & 2) ──────── --}}
@if($canManageDocuments)
<dialog id="addDocumentModal" class="modal" @if($errors->any()) data-autoopen="1" @endif>
    <div class="modal-box max-w-lg">
        <h3 class="font-bold text-lg mb-4" id="documentModalTitle">{{ $modalIsEdit ? 'Sửa hồ sơ' : 'Tải lên hồ sơ năng lực mới' }}</h3>

        @if($errors->any())
        <div class="alert alert-error py-2 px-3 mb-3 text-xs">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ $modalIsEdit
                  ? route('backend.internal-facilities.documents.update', [$modalFacilityId, old('_document_id')])
                  : route('backend.internal-facilities.documents.store', $modalFacilityId) }}"
              enctype="multipart/form-data" class="space-y-3"
              data-store-url-template="{{ route('backend.internal-facilities.documents.store', ['internal_facility' => '__FID__']) }}"
              data-update-url-template-raw="{{ route('backend.internal-facilities.documents.update', ['internal_facility' => '__FID__', 'document' => '__ID__']) }}"
              x-data="documentUploadForm({{ Js::from(
                  $documentTypesByGroup['legal_facility']->concat($documentTypesByGroup['attp_quality'])
                      ->map(fn ($t) => ['id' => $t->id, 'has_expiration_date' => (bool) $t->has_expiration_date, 'has_issue_place' => (bool) $t->has_issue_place, 'default_validity_months' => $t->default_validity_months])
              ) }}, {{ Js::from((string) old('document_master_type_id', '')) }})">
            @csrf
            <input type="hidden" name="_method" value="{{ $modalIsEdit ? 'PUT' : '' }}">
            <input type="hidden" name="_document_id" value="{{ old('_document_id') }}">
            <input type="hidden" name="_target_facility_id" value="{{ $modalFacilityId }}">

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại giấy tờ</span></label>
                <select id="ts-document_master_type_id" name="document_master_type_id" class="select select-bordered select-sm w-full" data-ts-placeholder="— Chọn loại giấy tờ —" @change="selectedId = $event.target.value">
                    <optgroup label="{{ $groupLabels['legal_facility'] }}">
                        @foreach($documentTypesByGroup['legal_facility'] as $type)
                        <option value="{{ $type->id }}" @selected(old('document_master_type_id') === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="{{ $groupLabels['attp_quality'] }}">
                        @foreach($documentTypesByGroup['attp_quality'] as $type)
                        <option value="{{ $type->id }}" @selected(old('document_master_type_id') === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control" :class="selected.has_issue_place ? '' : 'col-span-2'">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số hiệu</span></label>
                    <input type="text" name="document_number" value="{{ old('document_number') }}" class="input input-bordered input-sm w-full">
                </div>
                <div class="form-control" x-show="selected.has_issue_place">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Nơi cấp</span></label>
                    <input type="text" name="issued_by" value="{{ old('issued_by') }}" class="input input-bordered input-sm w-full">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control" :class="selected.has_expiration_date ? '' : 'col-span-2'">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày cấp</span></label>
                    <input type="text" id="fp-issue_date" name="issue_date" value="{{ old('issue_date') }}"
                           class="input input-bordered input-sm w-full" placeholder="dd/mm/yyyy" autocomplete="off">
                </div>
                <div class="form-control" x-show="selected.has_expiration_date">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày hết hạn</span></label>
                    <input type="text" id="fp-expiration_date" name="expiration_date" value="{{ old('expiration_date') }}"
                           class="input input-bordered input-sm w-full" placeholder="dd/mm/yyyy" autocomplete="off">
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">File PDF/Scan</span></label>
                <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="file-input file-input-bordered file-input-sm w-full">
                <p class="mt-1 text-xs text-base-content/50" id="documentCurrentFileInfo" hidden>
                    File hiện tại: <a href="#" target="_blank" class="link link-primary" id="documentCurrentFileLink">Xem file</a> — chọn file mới để thay thế
                </p>
            </div>

            <div class="modal-action mt-2">
                <button type="button" class="btn btn-ghost btn-sm" onclick="addDocumentModal.close()">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm" id="documentModalSubmit">{{ $modalIsEdit ? 'Lưu thay đổi' : 'Lưu hồ sơ' }}</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endif

{{-- ── Modal: Thêm/Sửa cơ sở nội bộ ─────────────────────────────────────── --}}
@can('create', \Modules\Compliance\Models\InternalFacility::class)
<dialog id="facilityModal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg mb-1" id="facilityModalTitle">Thêm cơ sở nội bộ</h3>

        <form method="POST" id="facilityForm" action="{{ route('backend.internal-facilities.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="_method" id="facilityMethod" value="">

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tên cơ sở <span class="text-error">*</span></span></label>
                <input type="text" id="facilityName" name="name" class="input input-bordered input-sm w-full" placeholder="VD: Kho trung tâm, Vùng trồng A..." required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại cơ sở <span class="text-error">*</span></span></label>
                    <select id="facilityType" name="type" class="select select-bordered select-sm w-full">
                        <option value="headquarter" hidden>Trụ sở chính</option>
                        <option value="farm">Vùng trồng</option>
                        <option value="warehouse">Kho</option>
                        <option value="processing_zone">Khu sơ chế/chế biến</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span></label>
                    <select id="facilityStatus" name="status" class="select select-bordered select-sm w-full">
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Ngừng hoạt động</option>
                    </select>
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Địa chỉ</span></label>
                <textarea id="facilityAddress" name="address" rows="2" class="textarea textarea-bordered textarea-sm w-full"></textarea>
            </div>

            <div class="modal-action mt-2">
                <button type="button" class="btn btn-ghost btn-sm" onclick="facilityModal.close()">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
function openFacilityModal(facility) {
    const form = document.getElementById('facilityForm');
    const typeSelect = document.getElementById('facilityType');
    const hqOption = typeSelect.querySelector('option[value="headquarter"]');
    const updateUrlTemplate = @json(route('backend.internal-facilities.update', ['internal_facility' => '__ID__']));
    const storeUrl = @json(route('backend.internal-facilities.store'));

    if (facility && facility.id) {
        form.action = updateUrlTemplate.replace('__ID__', facility.id);
        document.getElementById('facilityMethod').value = 'PUT';
        document.getElementById('facilityModalTitle').textContent = 'Sửa cơ sở nội bộ';
        document.getElementById('facilityName').value = facility.name ?? '';
        hqOption.hidden = facility.type !== 'headquarter';
        document.getElementById('facilityType').value = facility.type ?? 'farm';
        document.getElementById('facilityStatus').value = facility.status ?? 'active';
        document.getElementById('facilityAddress').value = facility.address ?? '';
    } else {
        form.reset();
        form.action = storeUrl;
        hqOption.hidden = true;
        document.getElementById('facilityMethod').value = '';
        document.getElementById('facilityModalTitle').textContent = 'Thêm cơ sở nội bộ';
    }

    facilityModal.showModal();
}
</script>
@endcan

@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Compliance/resources/assets/sass/compliance.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/tabulator.js',
        'Modules/Compliance/resources/assets/js/compliance.js',
    ], 'build/backend')
@endpush
