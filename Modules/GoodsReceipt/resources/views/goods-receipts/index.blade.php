@extends('layouts.backend')
@section('title', 'Nhập kho & Quản lý Lô')

@section('content')
<div x-data="goodsReceiptListPage({{ Js::from([
    'apiUrl'  => route('backend.api.goods-receipts'),
    'vendors' => $vendors,
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Nhập kho & Quản lý Lô</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Phiếu nhập kho MISA đã import và các lô hàng đã sinh</p>
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

            @can('create', \Modules\GoodsReceipt\Models\GoodsReceipt::class)
            <a href="{{ route('backend.goods-receipts.import') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M7 10l5 5 5-5M12 15V3"/>
                </svg>
                Import phiếu nhập kho
            </a>
            @endcan

        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
    @endif

    <div class="section-page">
        <div class="card bg-base-100 mb-4">
            <div class="card-body py-3 px-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">

                    <div class="form-control lg:col-span-2">
                        <label class="label mb-2">
                            <span class="label-text text-xs font-medium">Tìm kiếm</span>
                            <span class="label-text-alt text-xs text-base-content/40">Số phiếu, NCC</span>
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
                            <span class="label-text text-xs font-medium">Nhà cung cấp</span>
                        </label>
                        <select id="ts-vendor" x-model="filters.vendor" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả nhà cung cấp"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor['value'] }}">{{ $vendor['text'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Từ ngày</span>
                        </label>
                        <input id="fp-date-from" type="text" placeholder="dd/mm/yyyy" autocomplete="off"
                               class="input input-sm input-bordered w-full"/>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Đến ngày</span>
                        </label>
                        <input id="fp-date-to" type="text" placeholder="dd/mm/yyyy" autocomplete="off"
                               class="input input-sm input-bordered w-full"/>
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
                <div id="goods-receipt-table"></div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/GoodsReceipt/resources/assets/sass/goodsreceipt.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/GoodsReceipt/resources/assets/js/goodsreceipt.js',
    ], 'build/backend')
@endpush
