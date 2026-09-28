@extends('layouts.backend')
@section('title', 'Hợp đồng')

@section('content')
<div x-data="{
        pageTab: location.hash === '#compliance' ? 'compliance' : 'list',
        showTab(tab) {
            this.pageTab = tab;
            history.replaceState(null, '', location.pathname + location.search + (tab === 'compliance' ? '#compliance' : ''));
            if (tab === 'compliance') this.$dispatch('vendor-compliance-shown');
            else this.$nextTick(() => window.contractTable?.redraw(true));
        },
    }">

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

    <div role="tablist" class="tabs tabs-lift mb-4">
        <a role="tab" class="tab" :class="pageTab === 'list' ? 'tab-active' : ''" @click.prevent="showTab('list')" href="#">Danh sách hợp đồng</a>
        <a role="tab" class="tab" :class="pageTab === 'compliance' ? 'tab-active' : ''" @click.prevent="showTab('compliance')" href="#compliance">Tuân thủ theo NCC</a>
    </div>

<div x-show="pageTab === 'list'" x-data="contractListPage({{ Js::from([
    'apiUrl'        => route('backend.api.contracts'),
    'statuses'      => $statuses,
    'contractTypes' => $contractTypes,
    'partyTypes'    => $partyTypes,
    'vendors'       => $vendors,
    'customers'     => $customers,
    'canDelete'     => auth()->user()->can('delete', new \Modules\Contract\Models\Contract),
]) }})">
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

<div x-show="pageTab === 'compliance'" x-cloak
     x-data="vendorCompliancePage({{ Js::from([
        'apiUrl'         => route('backend.api.contracts.vendor-compliance'),
        'sourceGroups'   => $sourceGroups,
        'vendorStatuses' => $vendorStatuses,
     ]) }})"
     @vendor-compliance-shown.window="activate()">

    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <p class="text-sm text-base-content/50">Đối chiếu hồ sơ / hợp đồng của từng NCC với danh mục yêu cầu bắt buộc</p>
        @can('viewAny', \Modules\Contract\Models\VendorComplianceRequirement::class)
        <a href="{{ route('backend.vendor-compliance-requirements.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m3-6h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5"/></svg>
            Cấu hình danh mục bắt buộc
        </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <template x-for="tile in tiles" :key="tile.state">
            <button type="button" @click="toggleState(tile.state)"
                    class="card bg-base-100 border text-left transition-colors hover:border-base-content/30"
                    :class="filters.state === tile.state ? tile.activeClass : 'border-base-200'">
                <div class="card-body p-4 gap-1">
                    <span class="text-xs font-medium text-base-content/60" x-text="tile.label"></span>
                    <span class="text-2xl font-bold tabular-nums" :class="tile.textClass" x-text="summary[tile.state] ?? '—'"></span>
                    <span class="text-xs text-base-content/40">
                        trên <span x-text="summary.total ?? '—'"></span> NCC
                    </span>
                </div>
            </button>
        </template>
    </div>

    <div class="card bg-base-100 mb-4">
        <div class="card-body py-3 px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">

                <div class="form-control sm:col-span-2">
                    <label class="label mb-2">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tên, mã NCC</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="filters.search" @input.debounce.350ms="refresh()"
                               placeholder="Nhập từ khóa..." class="grow bg-transparent outline-none text-sm"/>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Nhóm nguồn</span></label>
                    <select x-model="filters.sourceGroup" @change="refresh()" class="select select-sm select-bordered w-full">
                        <option value="">Tất cả</option>
                        <template x-for="g in sourceGroups" :key="g.value">
                            <option :value="g.value" x-text="g.text"></option>
                        </template>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tình trạng hợp tác</span></label>
                    <select x-model="filters.vendorStatus" @change="refresh()" class="select select-sm select-bordered w-full">
                        <template x-for="s in vendorStatuses" :key="s.value">
                            <option :value="s.value" x-text="s.text"></option>
                        </template>
                        <option value="all">Tất cả</option>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Cảnh báo sắp hết hạn</span></label>
                    <select x-model.number="filters.days" @change="refresh()" class="select select-sm select-bordered w-full">
                        <option value="30">Trong 30 ngày</option>
                        <option value="60">Trong 60 ngày</option>
                        <option value="90">Trong 90 ngày</option>
                    </select>
                </div>

            </div>

            <div class="flex flex-wrap items-center gap-2 pt-3 mt-3 border-t border-base-200">
                <span class="text-xs text-base-content/40">Lọc nhanh:</span>
                <button type="button" class="badge badge-sm cursor-pointer" :class="filters.state === '' ? 'badge-neutral' : 'badge-ghost'" @click="setState('')">Tất cả</button>
                <template x-for="tile in tiles" :key="'chip-' + tile.state">
                    <button type="button" class="badge badge-sm cursor-pointer"
                            :class="filters.state === tile.state ? tile.chipClass : 'badge-ghost'"
                            @click="setState(tile.state)" x-text="tile.label"></button>
                </template>
                <button type="button" x-show="hasFilters" x-transition @click="reset()" class="btn btn-ghost btn-xs text-error ml-auto">Đặt lại</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="vendor-compliance-table"></div>
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
