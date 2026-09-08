@extends('layouts.backend')
@section('title', 'Thương hiệu')

@section('content')
<div x-data="brandListPage({{ Js::from([
    'apiUrl'    => route('backend.api.brands'),
    'canDelete' => auth()->user()->can('delete', new \Modules\Product\Models\Brand),
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Thương hiệu</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Từ điển thương hiệu — tránh nhập tay gây trùng lặp dữ liệu</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm">Quay lại danh mục</a>
            @can('create', \Modules\Product\Models\Brand::class)
            <a href="{{ route('backend.brands.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm thương hiệu
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="form-control max-w-sm">
                <label class="label py-0.5">
                    <span class="label-text text-xs font-medium">Tìm kiếm</span>
                    <span class="label-text-alt text-xs text-base-content/40">Tên, mô tả</span>
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
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="brand-table"></div>
        </div>
    </div>

</div>

<dialog id="deleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa thương hiệu
            <strong id="deleteItemName" class="text-base-content"></strong>?
        </p>
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
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
