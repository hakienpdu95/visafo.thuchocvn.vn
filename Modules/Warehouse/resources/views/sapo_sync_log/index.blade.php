@extends('layouts.backend')
@section('title', 'Đồng bộ Sapo')

@section('content')
<div x-data="{
        activeTab: 'tags',
        productsShown: false,
        switchTab(tab) {
            this.activeTab = tab;
            if (tab === 'products') {
                if (!this.productsShown) {
                    this.productsShown = true;
                    this.$nextTick(() => window.dispatchEvent(new CustomEvent('sapo-products-tab-shown')));
                } else {
                    window.sapoProductSyncLogTable?.redraw(true);
                }
            } else {
                window.sapoSyncLogTable?.redraw(true);
            }
        },
     }">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Đồng bộ Sapo</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Trung tâm giám sát đồng bộ dữ liệu 2 chiều với Sapo POS: tem đã bán trừ kho, và danh mục sản phẩm đồng bộ về.</p>
    </div>

    <div role="tablist" class="tabs tabs-boxed w-fit mb-4">
        <a role="tab" class="tab" :class="activeTab === 'tags' && 'tab-active'" @click="switchTab('tags')">
            Lịch sử đồng bộ Tem xuất kho
        </a>
        <a role="tab" class="tab" :class="activeTab === 'products' && 'tab-active'" @click="switchTab('products')">
            Lịch sử đồng bộ Danh mục Sản phẩm
        </a>
    </div>

    <div x-show="activeTab === 'tags'" x-data="sapoSyncLogListPage({{ Js::from([
        'apiUrl' => route('backend.api.sapo-sync-log'),
    ]) }})">

        <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
            <div class="card-body py-3 px-4">
                <div class="form-control max-w-sm">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Mã QR, GS1 serial, mã đơn Sapo</span>
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
                <div id="sapo-sync-log-table"></div>
            </div>
        </div>

    </div>

    <div x-show="activeTab === 'products'" x-data="sapoProductSyncLogListPage({{ Js::from([
        'apiUrl' => route('backend.api.sapo-product-sync-log'),
    ]) }})">

        <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
            <div class="card-body py-3 px-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="form-control flex-1 min-w-52">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Tìm kiếm</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tên sản phẩm, SKU, mã sản phẩm Sapo</span>
                        </label>
                        <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
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
                    <div class="form-control w-48">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                        <select x-model="filters.status" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            <option value="success">Thành công</option>
                            <option value="failed">Lỗi</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-0 overflow-hidden tabulator-daisy">
                <div id="sapo-product-sync-log-table"></div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Warehouse/resources/assets/js/warehouse.js',
    ], 'build/backend')
@endpush
