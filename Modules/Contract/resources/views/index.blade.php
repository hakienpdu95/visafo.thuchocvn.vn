@extends('layouts.backend')
@section('title', 'Hợp đồng')

@section('content')
<div x-data="contractListPage({{ Js::from([
    'apiUrl'        => route('backend.api.contracts'),
    'statuses'      => $statuses,
    'contractTypes' => $contractTypes,
    'partyTypes'    => $partyTypes,
    'vendors'       => $vendors,
    'customers'     => $customers,
    'canDelete'     => auth()->user()->can('delete', new \Modules\Contract\Models\Contract),
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Hợp đồng</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý hợp đồng ký với nhà cung cấp</p>
        </div>
        <div class="flex items-center gap-2">

            @can('create', \Modules\Contract\Models\Contract::class)
            <a href="{{ route('backend.contracts.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tạo hợp đồng
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">

                    <div class="form-control sm:col-span-2">
                        <label class="label mb-2">
                            <span class="label-text text-xs font-medium">Tìm kiếm</span>
                            <span class="label-text-alt text-xs text-base-content/40">Số HĐ, tên, nhà cung cấp</span>
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
                            <span class="label-text text-xs font-medium">Loại giao dịch</span>
                        </label>
                        <select id="ts-type" x-model="filters.type" @change="onTypeChange()"
                                data-ts-placeholder="Tất cả loại giao dịch"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($partyTypes as $partyType)
                            <option value="{{ $partyType['value'] }}">{{ $partyType['text'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control" x-show="filters.type === 'input'" x-cloak>
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Nhà cung cấp</span>
                        </label>
                        <select id="ts-filter-vendor" x-model="filters.vendorId" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả nhà cung cấp"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control" x-show="filters.type === 'output'" x-cloak>
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Khách hàng</span>
                        </label>
                        <select id="ts-filter-customer" x-model="filters.customerId" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả khách hàng"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Loại hợp đồng</span>
                        </label>
                        <select id="ts-contract-type" x-model="filters.contractType" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả loại hợp đồng"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($contractTypes as $contractType)
                            <option value="{{ $contractType['value'] }}">{{ $contractType['text'] }}</option>
                            @endforeach
                        </select>
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
                <div id="contract-table"></div>
            </div>
        </div>
    </div>

</div>

<dialog id="deleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa hợp đồng
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
    @vite(['Modules/Contract/resources/assets/sass/contract.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'Modules/Contract/resources/assets/js/contract.js',
    ], 'build/backend')
@endpush
