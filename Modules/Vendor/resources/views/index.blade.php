@extends('layouts.backend')
@section('title', 'Nhà cung cấp')

@section('content')
<div x-data="vendorListPage({{ Js::from([
    'apiUrl'      => route('backend.api.vendors'),
    'wardsApiUrl' => url('/api/provinces'),
    'statuses'    => $statuses,
    'provinces'   => $provinces,
    'canDelete'   => auth()->user()->can('delete', new \Modules\Vendor\Models\Vendor),
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý nhà cung cấp và hồ sơ pháp lý đi kèm</p>
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

            @can('create', \Modules\Vendor\Models\Vendor::class)
            <a href="{{ route('backend.vendors.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm nhà cung cấp
            </a>
            @endcan

        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
    @endif

    <div class="section-page">
        <div class="card bg-base-100 mb-4">
            <div class="card-body py-3 px-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">

                    <div class="form-control lg:col-span-2">
                        <label class="label mb-2">
                            <span class="label-text text-xs font-medium">Tìm kiếm</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tên, mã NCC, MST</span>
                        </label>
                        <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input id="filter-search" type="text"
                                   x-model="filters.search"
                                   @input.debounce.350ms="onFilterChange()"
                                   placeholder="Nhập từ khóa..."
                                   class="grow bg-transparent outline-none text-sm"/>
                            <button x-show="filters.search" @click="clearSearch()"
                                    class="text-base-content/30 hover:text-base-content transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Tỉnh / Thành phố</span>
                        </label>
                        <select id="ts-province" x-model="filters.province" @change="onProvinceChange()"
                                data-ts-placeholder="Tất cả tỉnh / thành phố"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($provinces as $province)
                            <option value="{{ $province['value'] }}">{{ $province['text'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Phường / Xã</span>
                        </label>
                        <select id="ts-ward" x-model="filters.ward" @change="onFilterChange()"
                                data-ts-placeholder="Chọn tỉnh / thành phố trước"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Số điện thoại</span>
                        </label>
                        <input type="text"
                               x-model="filters.phone"
                               @input.debounce.350ms="onFilterChange()"
                               placeholder="Nhập số điện thoại..."
                               class="input input-sm input-bordered w-full"/>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Trạng thái</span>
                        </label>
                        <select id="ts-status" x-model="filters.status" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả trạng thái"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($statuses as $status)
                            <option value="{{ $status['value'] }}">{{ $status['text'] }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="flex justify-end">
                    <button @click="reset()" x-show="hasFilters" x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error mt-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
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

        <div class="card">
            <div class="card-body p-0 overflow-hidden tabulator-daisy">
                <div id="vendor-table"></div>
            </div>
        </div>
    </div>
</div>

<dialog id="deleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa nhà cung cấp
            <strong id="deleteItemName" class="text-base-content"></strong>?
        </p>
        <p class="text-xs text-error/70">Toàn bộ chứng chỉ và dữ liệu liên quan sẽ bị xóa theo.</p>
        <div class="modal-action mt-4">
            <button id="confirmDeleteBtn" class="btn btn-error btn-sm">Xóa</button>
            <button class="btn btn-ghost btn-sm" onclick="deleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Vendor/resources/assets/sass/vendor.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'Modules/Vendor/resources/assets/js/vendor.js',
    ], 'build/backend')
@endpush
