@extends('layouts.backend')
@section('title', 'Hồ sơ năng lực VISAFO')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Hồ sơ năng lực VISAFO</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Hồ sơ pháp lý & chứng nhận nội bộ — QT-TXNG-01 nhóm NL</p>
    </div>
    @can('create', \Modules\Compliance\Models\InternalFacility::class)
    <button type="button" class="btn btn-primary btn-sm" onclick="openFacilityModal()">+ Thêm cơ sở</button>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

@if($facilities->isEmpty())
<div class="card bg-base-100 border border-dashed border-base-300">
    <div class="card-body py-16 text-center text-base-content/40">
        <p class="text-sm">Chưa có cơ sở nội bộ nào được khai báo.</p>
    </div>
</div>
@else

<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body py-3 px-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="form-control flex-1 min-w-52">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Cơ sở</span></label>
                <select id="facility-select" class="select select-bordered select-sm w-full">
                    @foreach($facilities as $facility)
                    <option value="{{ $facility->id }}" {{ $selectedFacility?->id === $facility->id ? 'selected' : '' }}
                            data-url="{{ route('backend.internal-compliance.index', ['facility' => $facility->id]) }}">
                        {{ $facility->name }} ({{ $facility->typeLabel() }})@if($facility->status === 'inactive') — Ngừng hoạt động @endif
                    </option>
                    @endforeach
                </select>
            </div>
            @can('update', $selectedFacility)
            @php
                $facilityPayload = json_encode([
                    'id'      => $selectedFacility->id,
                    'name'    => $selectedFacility->name,
                    'type'    => $selectedFacility->type,
                    'address' => $selectedFacility->address,
                    'status'  => $selectedFacility->status,
                ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
            @endphp
            <button type="button" class="btn btn-ghost btn-sm" onclick="openFacilityModal({{ $facilityPayload }})">Sửa cơ sở</button>
            @endcan
        </div>
        @if($selectedFacility?->address)
        <p class="text-xs text-base-content/40 mt-2">{{ $selectedFacility->address }}</p>
        @endif
    </div>
</div>

@php
    $groupTabs = [
        'legal_facility' => ['label' => 'Pháp lý (NL-PL)', 'table' => 'nl-pl-table'],
        'attp_quality'   => ['label' => 'ATTP & Chất lượng (NL-ATTP)', 'table' => 'nl-attp-table'],
        'personnel'      => ['label' => 'Nhân sự (NL-NS)', 'table' => 'nl-ns-table'],
    ];
    $canManageDocuments = $selectedFacility && auth()->user()->can('update', $selectedFacility);
@endphp

<div x-data="{ tab: 'legal_facility' }" data-initial-tab="legal_facility">
    <div role="tablist" class="tabs tabs-lift mb-6">
        @foreach($groupTabs as $key => $meta)
        <a role="tab" class="tab" :class="tab === '{{ $key }}' ? 'tab-active' : ''" @click.prevent="tab = '{{ $key }}'; window.onInternalComplianceTabShown('{{ $meta['table'] }}')" href="#">
            {{ $meta['label'] }}
            <span class="badge badge-neutral badge-xs ml-1.5">{{ $documentsByGroup[$key]->count() }}</span>
        </a>
        @endforeach
    </div>

    @foreach($groupTabs as $key => $meta)
    <div x-show="tab === '{{ $key }}'" x-cloak>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="card-title text-base mb-0">{{ $meta['label'] }}</h2>
                    @if($canManageDocuments)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openAddDocumentModal()">Tải lên hồ sơ mới</button>
                    @endif
                </div>

                <div class="tabulator-daisy">
                    <div id="{{ $meta['table'] }}"
                         data-can-manage="{{ $canManageDocuments ? '1' : '0' }}"
                         data-delete-url-template="{{ $selectedFacility ? route('backend.internal-facilities.documents.destroy', [$selectedFacility, '__ID__']) : '' }}"
                         data-rows="{{ json_encode($documentsByGroup[$key]->map(fn ($d) => [
                             'id'                      => $d->id,
                             'document_master_type_id' => $d->document_master_type_id,
                             'type_name'               => $d->documentType->name,
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
                         ])->values(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Modal: Tải lên hồ sơ năng lực mới ────────────────────────────────── --}}
@if($canManageDocuments)
<dialog id="addDocumentModal" class="modal" @if($errors->any()) data-autoopen="1" @endif>
    <div class="modal-box max-w-lg">
        <h3 class="font-bold text-lg mb-1" id="documentModalTitle">{{ old('_document_id') ? 'Sửa hồ sơ' : 'Tải lên hồ sơ năng lực mới' }}</h3>
        <p class="text-xs text-base-content/40 mb-4">Hồ sơ nội bộ của {{ $selectedFacility->name }}</p>

        @if($errors->any())
        <div class="alert alert-error py-2 px-3 mb-3 text-xs">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ old('_document_id') ? route('backend.internal-facilities.documents.update', [$selectedFacility, old('_document_id')]) : route('backend.internal-facilities.documents.store', $selectedFacility) }}"
              enctype="multipart/form-data" class="space-y-3"
              data-create-url="{{ route('backend.internal-facilities.documents.store', $selectedFacility) }}"
              data-update-url-template="{{ route('backend.internal-facilities.documents.update', [$selectedFacility, '__ID__']) }}"
              x-data="documentUploadForm({{ Js::from($documentTypes->map(fn ($t) => ['id' => $t->id, 'has_expiration_date' => (bool) $t->has_expiration_date, 'has_issue_place' => (bool) $t->has_issue_place, 'default_validity_months' => $t->default_validity_months])) }}, {{ Js::from((string) old('document_master_type_id', '')) }})">
            @csrf
            <input type="hidden" name="_method" value="{{ old('_document_id') ? 'PUT' : '' }}">
            <input type="hidden" name="_document_id" value="{{ old('_document_id') }}">

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại giấy tờ</span></label>
                <select id="ts-document_master_type_id" name="document_master_type_id" class="select select-bordered select-sm w-full" data-ts-placeholder="— Chọn loại giấy tờ —" @change="selectedId = $event.target.value">
                    @foreach($groupTabs as $key => $meta)
                    <optgroup label="{{ $meta['label'] }}">
                        @foreach($documentTypesByGroup[$key] as $type)
                        <option value="{{ $type->id }}" @selected(old('document_master_type_id') === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
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
                <button type="submit" class="btn btn-primary btn-sm" id="documentModalSubmit">{{ old('_document_id') ? 'Lưu thay đổi' : 'Lưu hồ sơ' }}</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endif

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
                <input type="text" id="facilityName" name="name" class="input input-bordered input-sm w-full" placeholder="VD: Trụ sở chính" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại cơ sở <span class="text-error">*</span></span></label>
                    <select id="facilityType" name="type" class="select select-bordered select-sm w-full">
                        <option value="headquarter">Trụ sở chính</option>
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
    const updateUrlTemplate = @json(route('backend.internal-facilities.update', ['internal_facility' => '__ID__']));
    const storeUrl = @json(route('backend.internal-facilities.store'));

    if (facility && facility.id) {
        form.action = updateUrlTemplate.replace('__ID__', facility.id);
        document.getElementById('facilityMethod').value = 'PUT';
        document.getElementById('facilityModalTitle').textContent = 'Sửa cơ sở nội bộ';
        document.getElementById('facilityName').value = facility.name ?? '';
        document.getElementById('facilityType').value = facility.type ?? 'headquarter';
        document.getElementById('facilityStatus').value = facility.status ?? 'active';
        document.getElementById('facilityAddress').value = facility.address ?? '';
    } else {
        form.reset();
        form.action = storeUrl;
        document.getElementById('facilityMethod').value = '';
        document.getElementById('facilityModalTitle').textContent = 'Thêm cơ sở nội bộ';
    }

    facilityModal.showModal();
}

document.getElementById('facility-select')?.addEventListener('change', (e) => {
    const url = e.target.selectedOptions[0]?.dataset.url;
    if (url) window.location.href = url;
});
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
