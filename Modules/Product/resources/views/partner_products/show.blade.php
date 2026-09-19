@extends('layouts.backend')
@section('title', $partnerProduct->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $partnerProduct->name }}
            <span class="badge {{ $partnerProduct->status->badgeClass() }} badge-sm">{{ $partnerProduct->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">
            {{ $partnerProduct->vendor_sku ?? '—' }} · NCC: {{ $partnerProduct->vendor?->name }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.partner-products.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        @can('update', $partnerProduct)
        <a href="{{ route('backend.partner-products.edit', $partnerProduct) }}" class="btn btn-primary btn-sm">Chỉnh sửa</a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="space-y-6">

    {{-- ── Ánh xạ Tier 1 / Tier 2 ──────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-4">Truy xuất nguồn gốc</h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Nhà cung cấp trực tiếp (Tier 1)</dt>
                    <dd class="font-medium">{{ $partnerProduct->vendor?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Ánh xạ danh mục chuẩn Visafo</dt>
                    <dd class="font-medium">
                        {{ $partnerProduct->product?->name ?? '—' }}
                        @if($partnerProduct->product)
                        <span class="text-xs text-base-content/40 font-mono">({{ $partnerProduct->product->sku }})</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Nhà sản xuất / Nguồn gốc (Tier 2)</dt>
                    <dd class="font-medium">{{ $partnerProduct->manufacturer_name ?? '— (mua trực tiếp, không qua trung gian)' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 mb-0.5">Địa chỉ / Vùng trồng / Lò mổ gốc</dt>
                    <dd class="font-medium">{{ $partnerProduct->origin_address ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- ── Rule Engine: Hồ sơ chất lượng bắt buộc ──────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-1">Hồ sơ chất lượng</h2>
            <p class="text-xs text-base-content/40 mb-4">Tự động xác định theo nhóm hàng của sản phẩm đã ánh xạ (ProductComplianceRuleEngine)</p>

            @if(empty($complianceResults))
            <p class="text-sm text-base-content/50">
                @if(!$partnerProduct->product)
                Chưa ánh xạ sản phẩm chuẩn — không thể xác định giấy tờ bắt buộc.
                @else
                Nhóm hàng của sản phẩm này chưa có quy tắc giấy tờ bắt buộc trong hệ thống.
                @endif
            </p>
            @else
            <div class="space-y-2.5">
                @foreach($complianceResults as $result)
                <div class="flex items-start gap-3 p-3 rounded-lg border {{ $result->satisfied ? 'border-success/20 bg-success/5' : 'border-error/20 bg-error/5' }}">
                    @if($result->satisfied)
                    <svg class="w-5 h-5 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    @else
                    <svg class="w-5 h-5 text-error shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium">{{ $result->requirement->label }}</p>
                        @if($result->requirement->legalBasis)
                        <p class="text-xs text-base-content/40 mt-0.5">Căn cứ: {{ $result->requirement->legalBasis }}</p>
                        @endif
                        @if($result->satisfied && $result->matchedCompliance)
                        <p class="text-xs text-success mt-1">
                            Đã cấp — {{ $result->matchedCompliance->documentType->name }}
                            (số {{ $result->matchedCompliance->document_number }}@if($result->matchedCompliance->expiration_date), hạn {{ $result->matchedCompliance->expiration_date->format('d/m/Y') }}@endif)
                        </p>
                        @else
                        <p class="text-xs text-error mt-1">Đang thiếu — cần đòi NCC bổ sung</p>
                        @endif
                    </div>
                    @if(!$result->satisfied)
                    @can('update', $partnerProduct)
                    @php
                        $reqTypeIds = collect($result->requirement->documentTypeCodes)->map(fn ($code) => $codeToId[$code] ?? null)->filter();
                        $quickFixId = $documentTypes->firstWhere(fn ($t) => $reqTypeIds->contains($t->id))?->id ?? $reqTypeIds->first() ?? '';
                    @endphp
                    <button type="button" class="btn btn-error btn-outline btn-xs shrink-0" onclick="openAddDocumentModal('{{ $quickFixId }}')">Bổ sung ngay</button>
                    @endcan
                    @else
                    <span class="badge badge-sm badge-success shrink-0">Đã cấp</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ── Toàn bộ hồ sơ đã ghi nhận ────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold">Toàn bộ hồ sơ đã ghi nhận</h2>
                @can('update', $partnerProduct)
                <button type="button" class="btn btn-primary btn-sm" onclick="openAddDocumentModal()">Tải lên hồ sơ mới</button>
                @endcan
            </div>

            @php
                $canManageDocuments = auth()->user()->can('update', $partnerProduct);
                $mediaUrlService = app(\App\Services\Media\MediaUrlService::class);
            @endphp
            <div class="tabulator-daisy">
                <div id="partner-product-documents-table"
                     data-can-manage="{{ $canManageDocuments ? '1' : '0' }}"
                     data-delete-url-template="{{ route('backend.partner-products.documents.destroy', [$partnerProduct, '__ID__']) }}"
                     data-rows="{{ json_encode($partnerProduct->documents->map(fn ($d) => [
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
                         'media'                   => $d->getMedia('attachments_private')->map(fn ($m) => [
                             'id'         => $m->id,
                             'name'       => $m->file_name,
                             'size'       => $m->size,
                             'is_image'   => str_starts_with($m->mime_type, 'image/'),
                             'url'        => $mediaUrlService->url($m),
                             'delete_url' => route('backend.partner-products.documents.media.destroy', [$partnerProduct, $d->id, $m->id]),
                         ])->values(),
                     ])->values(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"></div>
            </div>
        </div>
    </div>

</div>

{{-- ── Modal: Tải lên hồ sơ chất lượng mới ──────────────────────────────── --}}
@can('update', $partnerProduct)
<dialog id="addDocumentModal" class="modal" @if($errors->any()) data-autoopen="1" @endif>
    <div class="modal-box max-w-lg">
        <h3 class="font-bold text-lg mb-1" id="documentModalTitle">{{ old('_document_id') ? 'Sửa hồ sơ' : 'Tải lên hồ sơ chất lượng mới' }}</h3>
        <p class="text-xs text-base-content/40 mb-4">Giấy tờ của nhà sản xuất gốc (VD: ISO, OCOP, kiểm dịch của {{ $partnerProduct->manufacturer_name ?? 'nhà sản xuất' }})</p>

        @if($errors->any())
        <div class="alert alert-error py-2 px-3 mb-3 text-xs">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ old('_document_id') ? route('backend.partner-products.documents.update', [$partnerProduct, old('_document_id')]) : route('backend.partner-products.documents.store', $partnerProduct) }}"
              enctype="multipart/form-data" class="space-y-3"
              data-create-url="{{ route('backend.partner-products.documents.store', $partnerProduct) }}"
              data-update-url-template="{{ route('backend.partner-products.documents.update', [$partnerProduct, '__ID__']) }}"
              x-data="documentUploadForm({{ Js::from($documentTypes->map(fn ($t) => ['id' => $t->id, 'has_expiration_date' => (bool) $t->has_expiration_date, 'has_issue_place' => (bool) $t->has_issue_place, 'default_validity_months' => $t->default_validity_months])) }}, {{ Js::from((string) old('document_master_type_id', '')) }})">
            @csrf
            <input type="hidden" name="_method" value="{{ old('_document_id') ? 'PUT' : '' }}">
            <input type="hidden" name="_document_id" value="{{ old('_document_id') }}">

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại giấy tờ</span></label>
                <select id="ts-document_master_type_id" name="document_master_type_id" class="select select-bordered select-sm w-full" data-ts-placeholder="— Chọn loại giấy tờ —" @change="selectedId = $event.target.value">
                    @foreach($documentTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('document_master_type_id') === $type->id)>
                        {{ $type->name }}@if($missingTypeIds->contains($type->id)) — đang thiếu @endif
                    </option>
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
                    <span class="label-text-alt text-xs text-base-content/40">Chọn 1 hoặc nhiều file — PDF, JPG, PNG, tối đa 10MB/file</span>
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
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Product/resources/assets/sass/product.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/tabulator.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
