@extends('layouts.backend')
@section('title', 'Kho tài liệu & Minh chứng')

@section('content')
<div x-data="documentListPage({{ Js::from([
    'apiUrl'            => route('backend.api.documents'),
    'documentableTypes' => $documentableTypes,
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Kho tài liệu & Minh chứng</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Toàn bộ hồ sơ pháp lý hệ thống — Nhà cung cấp, Sản phẩm Visafo, Hàng hóa NCC</p>
        </div>
        <div class="flex items-center gap-2">

            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    Cột
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-48 z-50 p-2">
                    <template x-for="col in toggleableCols" :key="col.field">
                        <li>
                            <label class="flex items-center gap-2 cursor-pointer py-1.5 px-2 rounded-lg hover:bg-base-200">
                                <input type="checkbox" class="checkbox checkbox-xs"
                                       :checked="!hiddenCols.includes(col.field)"
                                       @change="toggleCol(col.field)"/>
                                <span x-text="col.title" class="text-sm"></span>
                            </label>
                        </li>
                    </template>
                </ul>
            </div>

            @if($canUploadShared)
            <button type="button" class="btn btn-primary btn-sm gap-1.5" onclick="sharedUploadModal.showModal()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tải tài liệu nội bộ
            </button>
            @endif

        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Số hiệu</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input id="filter-search" type="text"
                               x-model="filters.search"
                               @input.debounce.350ms="onFilterChange()"
                               placeholder="Nhập số hiệu..."
                               class="grow bg-transparent outline-none text-sm"/>
                        <button x-show="filters.search" @click="clearSearch()"
                                class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-control w-56">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Thuộc về</span>
                    </label>
                    <select x-model="filters.documentableType" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">Tất cả</option>
                        @foreach($documentableTypes as $type)
                        <option value="{{ $type['value'] }}">{{ $type['text'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label cursor-pointer gap-2 py-0.5">
                        <input type="checkbox" class="checkbox checkbox-sm checkbox-warning"
                               x-model="filters.expiring" @change="onExpiringChange()">
                        <span class="label-text text-xs">Sắp hết hạn (30 ngày)</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label cursor-pointer gap-2 py-0.5">
                        <input type="checkbox" class="checkbox checkbox-sm checkbox-error"
                               x-model="filters.expired" @change="onExpiredChange()">
                        <span class="label-text text-xs">Đã hết hạn</span>
                    </label>
                </div>

                <div class="form-control ml-auto">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <button @click="reset()" x-show="hasFilters" x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>

            </div>

            <div x-show="activeChips.length > 0" x-transition
                 class="flex flex-wrap gap-2 pt-3 mt-3 border-t border-base-200">
                <span class="text-xs text-base-content/40 self-center">Đang lọc:</span>
                <template x-for="chip in activeChips" :key="chip.key">
                    <span class="badge badge-sm gap-1 cursor-pointer hover:badge-error transition-colors"
                          @click="removeChip(chip.key)">
                        <span x-text="chip.label"></span>
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </span>
                </template>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="document-table"></div>
        </div>
    </div>

</div>

<p class="text-xs text-base-content/40 mt-3">
    Để thêm hồ sơ Nhà cung cấp / Sản phẩm / Hàng hóa NCC / Cơ sở nội bộ, vào trang chi tiết tương ứng. Tài liệu nội bộ dùng chung có thể tải lên trực tiếp tại đây.
</p>

@if($canUploadShared)
<dialog id="sharedUploadModal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg">Tải tài liệu nội bộ dùng chung</h3>
        <p class="text-sm text-base-content/50 mb-4">Áp dụng cho tài liệu không gắn với Nhà cung cấp / Cơ sở / Sản phẩm cụ thể — VD: Quy chế, biểu mẫu, quyết định...</p>

        <form method="POST" action="{{ route('backend.document-repository.upload') }}" enctype="multipart/form-data" novalidate data-shared-upload-form>
            @csrf

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Tên tài liệu <span class="text-error">*</span></span>
                </label>
                <input type="text" name="custom_name" value="{{ old('custom_name') }}"
                       data-req="Vui lòng nhập tên tài liệu"
                       class="input input-bordered input-sm w-full @error('custom_name') input-error @enderror"
                       placeholder="VD: Quy chế chi tiêu nội bộ 2026">
                @error('custom_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control mt-3">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Nhóm/Phân loại <span class="text-error">*</span></span>
                </label>
                <select name="custom_category"
                        data-req="Vui lòng chọn nhóm/phân loại"
                        class="select select-bordered select-sm w-full @error('custom_category') select-error @enderror">
                    <option value="">— Chọn nhóm —</option>
                    @foreach($sharedCategories as $cat)
                    <option value="{{ $cat['value'] }}" {{ old('custom_category') === $cat['value'] ? 'selected' : '' }}>{{ $cat['text'] }}</option>
                    @endforeach
                </select>
                @error('custom_category')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control mt-3">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">File đính kèm <span class="text-error">*</span></span>
                    <span class="label-text-alt text-xs text-base-content/40">PDF, DOCX, XLSX, JPG</span>
                </label>
                <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                       class="file-input file-input-bordered file-input-sm w-full @error('file') input-error @enderror">
                @error('file')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control mt-3">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Ghi chú</span>
                    <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                </label>
                <textarea name="notes" rows="3"
                          class="textarea textarea-bordered textarea-sm w-full"
                          placeholder="Ghi chú...">{{ old('notes') }}</textarea>
            </div>

            <div class="modal-action mt-4">
                <button type="button" class="btn btn-ghost btn-sm" onclick="sharedUploadModal.close()">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm">Tải lên</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

@if($errors->has('custom_name') || $errors->has('custom_category') || $errors->has('file'))
<script>document.addEventListener('DOMContentLoaded', () => sharedUploadModal?.showModal());</script>
@endif
@endif

@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Compliance/resources/assets/js/compliance.js',
    ], 'build/backend')
@endpush
