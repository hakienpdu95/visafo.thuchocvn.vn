@extends('layouts.backend')
@section('title', 'Từ điển phân bón')

@section('content')
<div x-data="agriFertilizerListPage({{ Js::from([
    'apiUrl'     => route('backend.api.agri-fertilizers'),
    'categories' => $categories,
]) }})">

    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            <svg class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c-4 3-6 6-6 10a6 6 0 0012 0c0-4-2-7-6-10z"/>
            </svg>
            Từ điển phân bón
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">Danh mục Phân bón Quốc gia — dữ liệu tham chiếu, chỉ hiển thị</p>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
    @endif

    <div class="card bg-success/5 border border-success/20 shadow-sm mb-4">
        <div class="card-body py-4 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control flex-1 min-w-64">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tên phân bón, thành phần, tổ chức đăng ký</span>
                    </label>
                    <div class="input input-bordered input-lg flex items-center gap-2 bg-base-100 border-success/30 focus-within:border-success">
                        <svg class="w-5 h-5 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input id="filter-search" type="text"
                               x-model="filters.search"
                               @input.debounce.350ms="onFilterChange()"
                               placeholder="VD: NPK, Đồng tiền vàng, hữu cơ..."
                               class="grow bg-transparent outline-none text-base"/>
                        <button x-show="filters.search" @click="clearSearch()"
                                class="text-base-content/30 hover:text-error transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-control w-64">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Nhóm phân bón</span>
                    </label>
                    <select x-model="filters.category" @change="onFilterChange()" class="select select-bordered w-full border-success/30">
                        <option value="">Tất cả</option>
                        @foreach($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <button @click="reset()" x-show="hasFilters" x-transition
                        class="btn btn-ghost btn-sm gap-1.5 text-error">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Đặt lại
                </button>

            </div>

            <div x-show="activeChips.length > 0" x-transition
                 class="flex flex-wrap gap-2 pt-3 mt-3 border-t border-success/10">
                <span class="text-xs text-base-content/40 self-center">Đang lọc:</span>
                <template x-for="chip in activeChips" :key="chip.key">
                    <span class="badge badge-sm badge-success badge-soft gap-1 cursor-pointer hover:badge-error transition-colors"
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
            <div id="agri-fertilizer-table"></div>
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
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
