@extends('layouts.backend')
@section('title', $vendor->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $vendor->name }}
            <span class="badge {{ $vendor->status->badgeClass() }} badge-sm">{{ $vendor->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">
            {{ $vendor->vendor_code ?? '—' }} · MST {{ $vendor->tax_code }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.vendors.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        @can('update', $vendor)
        <a href="{{ route('backend.vendors.edit', $vendor) }}" class="btn btn-primary btn-sm">Chỉnh sửa</a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

@php $initialTab = $errors->any() || str_contains((string) session('success'), 'hồ sơ') ? 'documents' : 'general'; @endphp
<div x-data="{ tab: '{{ $initialTab }}' }" data-initial-tab="{{ $initialTab }}">

    <div role="tablist" class="tabs tabs-lift mb-6">
        <a role="tab" class="tab" :class="tab === 'general' ? 'tab-active' : ''" @click.prevent="tab = 'general'" href="#">
            Thông tin chung &amp; Đầu mối liên hệ
        </a>
        <a role="tab" class="tab" :class="tab === 'products' ? 'tab-active' : ''" @click.prevent="tab = 'products'; window.onVendorTabShown('products')" href="#">
            Hàng hóa cung cấp
            <span class="badge badge-neutral badge-xs ml-1.5">{{ $vendor->partnerProducts->count() }}</span>
        </a>
        <a role="tab" class="tab" :class="tab === 'documents' ? 'tab-active' : ''" @click.prevent="tab = 'documents'; window.onVendorTabShown('documents')" href="#">
            Hồ sơ pháp lý &amp; ATTP
            <span class="badge badge-neutral badge-xs ml-1.5">{{ $vendor->documents->count() }}</span>
        </a>
        <a role="tab" class="tab" :class="tab === 'farming-steps' ? 'tab-active' : ''" @click.prevent="tab = 'farming-steps'" href="#">
            Cấu hình Nhật ký Canh tác
            <span class="badge badge-neutral badge-xs ml-1.5">{{ $vendor->farmingSteps->count() }}</span>
        </a>
    </div>

    {{-- ── Tab 1: Thông tin chung & Đầu mối liên hệ ────────────────────── --}}
    <div x-show="tab === 'general'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="text-base font-semibold mb-3">Thông tin pháp nhân</h2>
                    <dl class="text-sm space-y-2">
                        <div><dt class="text-base-content/50 text-xs">Nhóm nguồn</dt><dd>{{ $vendor->source_group?->label() ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Địa chỉ</dt>
                            <dd>
                                {{ $vendor->address ?? '—' }}
                                @if($vendor->ward || $vendor->province)
                                    <br>{{ trim(($vendor->ward?->name ?? '') . ', ' . ($vendor->province?->name ?? ''), ', ') }}
                                @endif
                            </dd>
                        </div>
                        <div><dt class="text-base-content/50 text-xs">Điện thoại công ty</dt><dd>{{ $vendor->phone_number ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Email công ty</dt><dd>{{ $vendor->email ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="text-base font-semibold mb-3">Người đại diện theo pháp luật</h2>
                    <dl class="text-sm space-y-2">
                        <div><dt class="text-base-content/50 text-xs">Họ và tên</dt><dd>{{ $vendor->representative_name ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Chức danh</dt><dd>{{ $vendor->representative_title ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Điện thoại</dt><dd>{{ $vendor->representative_phone ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Email</dt><dd>{{ $vendor->representative_email ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="text-base font-semibold mb-3">Đầu mối liên hệ về công việc</h2>
                    <dl class="text-sm space-y-2">
                        <div><dt class="text-base-content/50 text-xs">Họ và tên</dt><dd>{{ $vendor->contact_person_name ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Chức vụ</dt><dd>{{ $vendor->contact_person_title ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Điện thoại</dt><dd>{{ $vendor->contact_person_phone ?? '—' }}</dd></div>
                        <div><dt class="text-base-content/50 text-xs">Email</dt><dd>{{ $vendor->contact_person_email ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Tab 2: Hàng hóa cung cấp ──────────────────────────────────────── --}}
    <div x-show="tab === 'products'" x-cloak>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold">Hàng hóa nhà cung cấp đang giao</h2>
                    @can('create', \Modules\Product\Models\PartnerProduct::class)
                    <a href="{{ route('backend.partner-products.create', ['vendor_id' => $vendor->id]) }}" class="btn btn-primary btn-sm">+ Thêm hàng hóa</a>
                    @endcan
                </div>

                <div class="tabulator-daisy">
                    <div id="vendor-partner-products-table" data-rows="{{ json_encode($vendor->partnerProducts->map(fn ($p) => [
                        'name'              => $p->name,
                        'vendor_sku'        => $p->vendor_sku,
                        'product_name'      => $p->product?->name,
                        'product_sku'       => $p->product?->sku,
                        'manufacturer_name' => $p->manufacturer_name,
                        'status_label'      => $p->status->label(),
                        'status_badge'      => $p->status->badgeClass(),
                        'show_url'          => auth()->user()->can('view', $p) ? route('backend.partner-products.show', $p) : null,
                    ])->values(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tab 3: Hồ sơ pháp lý & ATTP ───────────────────────────────────── --}}
    <div x-show="tab === 'documents'" x-cloak>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold">Hồ sơ pháp lý &amp; ATTP</h2>
                    @can('update', $vendor)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openAddDocumentModal()">Tải lên hồ sơ mới</button>
                    @endcan
                </div>

                @php
                    $canManageDocuments = auth()->user()->can('update', $vendor);
                    $mediaUrlService = app(\App\Services\Media\MediaUrlService::class);
                @endphp
                <div class="tabulator-daisy">
                    <div id="vendor-documents-table"
                         data-can-manage="{{ $canManageDocuments ? '1' : '0' }}"
                         data-delete-url-template="{{ route('backend.vendors.documents.destroy', [$vendor, '__ID__']) }}"
                         data-rows="{{ json_encode($vendor->documents->map(fn ($d) => [
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
                             'file_url'                => ($m = $d->getMedia('attachments_private')->last()) ? $mediaUrlService->url($m) : '',
                             'media'                   => $d->getMedia('attachments_private')->map(fn ($m) => [
                                 'id'         => $m->id,
                                 'name'       => $m->file_name,
                                 'size'       => $m->size,
                                 'is_image'   => str_starts_with($m->mime_type, 'image/'),
                                 'url'        => $mediaUrlService->url($m),
                                 'delete_url' => route('backend.vendors.documents.media.destroy', [$vendor, $d->id, $m->id]),
                             ])->values(),
                         ])->values(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tab 4: Cấu hình Nhật ký Canh tác (Vỏ mềm) ─────────────────────── --}}
    <div x-show="tab === 'farming-steps'" x-cloak>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-base font-semibold">Cấu hình Nhật ký Canh tác (Vỏ mềm)</h2>
                    @can('create', \Modules\Product\Models\VendorFarmingStep::class)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openFarmingStepModal()">+ Thêm bước canh tác</button>
                    @endcan
                </div>
                <p class="text-xs text-base-content/50 mb-4">
                    Các công đoạn đặc thù (Tỉa cành, Bọc trái, Phơi sấy...) do Nông hộ/QC tự định nghĩa — chỉ ghi nhận với
                    <span class="font-mono">activity_type</span> = <span class="font-mono">cultivation</span> hoặc <span class="font-mono">other</span>,
                    không ảnh hưởng thuật toán Readiness ATTP. 3 công đoạn "Vỏ cứng" (Bón phân, Phun thuốc BVTV, Thu hoạch) luôn cố định trên App Nông hộ.
                </p>

                @if($vendor->farmingSteps->isEmpty())
                <div class="text-center py-10 text-base-content/40 text-sm">Chưa có bước canh tác tùy biến nào.</div>
                @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-xs uppercase text-base-content/40">
                                <th class="w-16">Thứ tự</th>
                                <th>Tên công đoạn</th>
                                <th>Áp dụng cho</th>
                                <th>Loại gốc</th>
                                <th class="text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vendor->farmingSteps as $step)
                            @php
                                $stepPayload = json_encode([
                                    'id'                  => $step->id,
                                    'step_name'           => $step->step_name,
                                    'partner_product_id'  => $step->partner_product_id,
                                    'base_activity_type'  => $step->base_activity_type,
                                    'order_index'         => $step->order_index,
                                ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
                            @endphp
                            <tr>
                                <td class="text-base-content/50">{{ $step->order_index }}</td>
                                <td class="font-medium">{{ $step->step_name }}</td>
                                <td class="text-sm text-base-content/70">{{ $step->partnerProduct?->name ?? 'Áp dụng chung' }}</td>
                                <td><span class="badge badge-ghost badge-xs font-mono">{{ $step->base_activity_type }}</span></td>
                                <td class="text-right whitespace-nowrap">
                                    @can('update', $step)
                                    <button type="button" class="btn btn-ghost btn-xs" onclick="openFarmingStepModal({{ $stepPayload }})">Sửa</button>
                                    @endcan
                                    @can('delete', $step)
                                    <form method="POST" action="{{ route('backend.vendors.farming-steps.destroy', [$vendor, $step]) }}"
                                          onsubmit="return confirm('Xóa bước canh tác &quot;{{ $step->step_name }}&quot;?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-xs text-error">Xóa</button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ── Modal: Tải lên hồ sơ mới ─────────────────────────────────────────── --}}
@can('update', $vendor)
<dialog id="addDocumentModal" class="modal" @if($errors->any()) data-autoopen="1" @endif>
    <div class="modal-box max-w-lg">
        <h3 class="font-bold text-lg mb-1" id="documentModalTitle">{{ old('_document_id') ? 'Sửa hồ sơ' : 'Tải lên hồ sơ mới' }}</h3>
        <p class="text-xs text-base-content/40 mb-4">Hồ sơ pháp lý / ATTP do nhà cung cấp này nộp</p>

        @if($errors->any())
        <div class="alert alert-error py-2 px-3 mb-3 text-xs">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ old('_document_id') ? route('backend.vendors.documents.update', [$vendor, old('_document_id')]) : route('backend.vendors.documents.store', $vendor) }}"
              enctype="multipart/form-data" class="space-y-3"
              data-create-url="{{ route('backend.vendors.documents.store', $vendor) }}"
              data-update-url-template="{{ route('backend.vendors.documents.update', [$vendor, '__ID__']) }}"
              x-data="documentUploadForm({{ Js::from($documentTypes->map(fn ($t) => ['id' => $t->id, 'has_expiration_date' => (bool) $t->has_expiration_date, 'has_issue_place' => (bool) $t->has_issue_place, 'default_validity_months' => $t->default_validity_months])) }}, {{ Js::from((string) old('document_master_type_id', '')) }})">
            @csrf
            <input type="hidden" name="_method" value="{{ old('_document_id') ? 'PUT' : '' }}">
            <input type="hidden" name="_document_id" value="{{ old('_document_id') }}">

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại giấy tờ</span></label>
                <select id="ts-document_master_type_id" name="document_master_type_id" class="select select-bordered select-sm w-full" data-ts-placeholder="— Chọn loại giấy tờ —" @change="selectedId = $event.target.value">
                    @foreach($documentTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('document_master_type_id') === $type->id)>{{ $type->name }}</option>
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

            <div class="form-control" x-show="existingMedia.length > 0" x-cloak>
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Các tệp đính kèm hiện tại</span></label>
                <template x-for="media in existingMedia" :key="media.id">
                    <div class="flex items-center justify-between p-2.5 mb-2 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg x-show="media.is_image" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <svg x-show="!media.is_image" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="truncate text-sm text-gray-700" x-text="media.name"></span>
                            <span class="shrink-0 text-xs text-gray-400" x-text="formatFileSize(media.size)"></span>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <a :href="media.url" target="_blank" class="btn btn-ghost btn-xs">Xem</a>
                            <button type="button" class="btn btn-ghost btn-xs text-error" :disabled="removingMediaId === media.id"
                                    @click="removeExistingMedia(media)" title="Xóa file này">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1">
                    <span class="label-text text-xs font-medium" x-text="existingMedia.length > 0 ? 'Tải thêm tệp mới' : 'File PDF/Scan'"></span>
                    <span class="label-text-alt text-xs text-base-content/40">Chọn 1 hoặc nhiều file — PDF, JPG, PNG, tối đa 100MB/file</span>
                </label>
                <input type="file" name="files[]" x-ref="filesInput" multiple accept=".pdf,.jpg,.jpeg,.png"
                       class="file-input file-input-bordered file-input-sm w-full @error('files') input-error @enderror"
                       @change="onFilesChange($event)">

                <ul class="mt-2 space-y-1" x-show="files.length > 0" x-cloak>
                    <template x-for="(file, index) in files" :key="index">
                        <li class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg x-show="isImageFile(file)" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <svg x-show="!isImageFile(file)" class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="truncate text-xs text-gray-700" x-text="file.name"></span>
                                <span class="shrink-0 text-xs text-base-content/40" x-text="formatFileSize(file.size)"></span>
                            </div>
                            <button type="button" class="btn btn-ghost btn-xs btn-circle shrink-0" @click="removeFile(index)" title="Bỏ chọn file này">✕</button>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="modal-action mt-2">
                <button type="button" class="btn btn-ghost btn-sm" onclick="addDocumentModal.close()">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm" id="documentModalSubmit">{{ old('_document_id') ? 'Lưu thay đổi' : 'Lưu hồ sơ' }}</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan

{{-- ── Modal: Thêm/Sửa bước canh tác (Vỏ mềm) ───────────────────────────── --}}
@can('create', \Modules\Product\Models\VendorFarmingStep::class)
<dialog id="farmingStepModal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg mb-1" id="farmingStepModalTitle">Thêm bước canh tác</h3>
        <p class="text-xs text-base-content/40 mb-4">Chỉ áp dụng với activity_type = cultivation | other, không ảnh hưởng thuật toán ATTP</p>

        <form method="POST" id="farmingStepForm" action="{{ route('backend.vendors.farming-steps.store', $vendor) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="_method" id="farmingStepMethod" value="">

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tên công đoạn <span class="text-error">*</span></span></label>
                <input type="text" id="farmingStepName" name="step_name" class="input input-bordered input-sm w-full" placeholder="VD: Bọc trái ổi" required>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Áp dụng cho mặt hàng</span></label>
                <select id="farmingStepProduct" name="partner_product_id" class="select select-bordered select-sm w-full">
                    <option value="">— Áp dụng chung mọi mặt hàng —</option>
                    @foreach($vendor->partnerProducts as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Phân loại gốc <span class="text-error">*</span></span></label>
                    <select id="farmingStepType" name="base_activity_type" class="select select-bordered select-sm w-full">
                        <option value="cultivation">Canh tác (cultivation)</option>
                        <option value="other">Khác (other)</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Thứ tự</span></label>
                    <input type="number" id="farmingStepOrder" name="order_index" value="0" class="input input-bordered input-sm w-full">
                </div>
            </div>

            <div class="modal-action mt-2">
                <button type="button" class="btn btn-ghost btn-sm" onclick="farmingStepModal.close()">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
function openFarmingStepModal(step) {
    const form = document.getElementById('farmingStepForm');
    const storeUrl = @json(route('backend.vendors.farming-steps.store', $vendor));
    const updateUrlTemplate = @json(route('backend.vendors.farming-steps.update', [$vendor, '__ID__']));

    if (step && step.id) {
        form.action = updateUrlTemplate.replace('__ID__', step.id);
        document.getElementById('farmingStepMethod').value = 'PUT';
        document.getElementById('farmingStepModalTitle').textContent = 'Sửa bước canh tác';
        document.getElementById('farmingStepName').value = step.step_name ?? '';
        document.getElementById('farmingStepProduct').value = step.partner_product_id ?? '';
        document.getElementById('farmingStepType').value = step.base_activity_type ?? 'cultivation';
        document.getElementById('farmingStepOrder').value = step.order_index ?? 0;
    } else {
        form.reset();
        form.action = storeUrl;
        document.getElementById('farmingStepMethod').value = '';
        document.getElementById('farmingStepModalTitle').textContent = 'Thêm bước canh tác';
        document.getElementById('farmingStepOrder').value = 0;
    }

    farmingStepModal.showModal();
}
</script>
@endcan
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Vendor/resources/assets/sass/vendor.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/tabulator.js',
        'Modules/Vendor/resources/assets/js/vendor.js',
    ], 'build/backend')
@endpush
