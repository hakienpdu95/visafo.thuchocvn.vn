@extends('layouts.backend')
@section('title', 'Vụ / Lô sản xuất')

@section('content')
<div x-data="farmingBatchListPage({{ Js::from([
    'apiUrl'    => route('backend.api.farming-batches'),
    'canManage' => auth()->user()->can('compliance.manage'),
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Quản lý Vụ / Lô sản xuất</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Số hóa BM-NH-03 (Mở vụ/lô sản xuất) — QT-NH-01</p>
        </div>
        @can('create', \Modules\Product\Models\FarmingBatch::class)
        <button type="button" class="btn btn-primary btn-sm gap-1.5" onclick="openFarmingBatchModal()">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Mở vụ mới
        </button>
        @endcan
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
                        <span class="label-text-alt text-xs text-base-content/40">Mã lô, tên nông hộ</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="filters.search" @input.debounce.350ms="onFilterChange()"
                               placeholder="Nhập từ khóa..." class="grow bg-transparent outline-none text-sm"/>
                        <button x-show="filters.search" @click="clearSearch()" class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-control w-56">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                    <select x-model="filters.status" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">Tất cả</option>
                        <option value="active">Đang canh tác</option>
                        <option value="harvested">Đã thu hoạch</option>
                        <option value="cancelled">Đã hủy</option>
                    </select>
                </div>
                <button @click="reset()" x-show="hasFilters" x-transition class="btn btn-ghost btn-sm gap-1.5 text-error">Đặt lại</button>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="farming-batch-table"></div>
        </div>
    </div>

</div>

@can('create', \Modules\Product\Models\FarmingBatch::class)
<dialog id="farmingBatchModal" class="modal" @if($errors->any()) data-autoopen="1" @endif>
    <div class="modal-box max-w-lg">
        <h3 class="font-bold text-lg mb-1" id="farmingBatchModalTitle">Mở vụ / lô sản xuất mới</h3>
        <p class="text-xs text-base-content/40 mb-4">BM-NH-03 — chỉ hiển thị vùng trồng đã được duyệt (Đạt) và giống hợp lệ</p>

        @if($errors->any())
        <div class="alert alert-error py-2 px-3 mb-3 text-xs">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" id="farmingBatchForm" action="{{ route('backend.farming-batches.store') }}" class="space-y-3"
              data-create-url="{{ route('backend.farming-batches.store') }}">
            @csrf
            <input type="hidden" name="_method" id="farmingBatchMethod" value="">

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Vùng trồng (đã duyệt) <span class="text-error">*</span></span></label>
                <select id="ts-batch-source" name="farming_source_id" class="select select-bordered select-sm w-full" data-ts-placeholder="— Chọn vùng trồng —">
                    <option value="">— Chọn vùng trồng —</option>
                    @forelse($farmingSources as $source)
                    <option value="{{ $source->id }}" data-vendor-id="{{ $source->vendor_id }}">{{ $source->vendor?->name }} — {{ $source->name }}</option>
                    @empty
                    @endforelse
                </select>
                @if($farmingSources->isEmpty())
                <p class="mt-1 text-xs text-warning">Chưa có vùng trồng nào được duyệt "Đạt" — hãy xác nhận kiểm tra vùng trồng trước.</p>
                @endif
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Giống cây trồng <span class="text-error">*</span></span></label>
                <select id="ts-batch-seed" name="agri_seed_id" class="select select-bordered select-sm w-full" data-ts-placeholder="— Chọn giống —">
                    <option value="">— Chọn giống —</option>
                    @foreach($agriSeeds as $seed)
                    <option value="{{ $seed->id }}">{{ $seed->name }} ({{ $seed->crop_type }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Mặt hàng thương mại (Visafo thu mua) <span class="text-error">*</span></span></label>
                <select id="ts-batch-product" name="partner_product_id"
                        class="select select-bordered select-sm w-full"
                        data-ts-placeholder="Chọn vùng trồng trước"
                        data-products="{{ $partnerProducts->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'vendor_id' => $p->vendor_id])->toJson() }}"
                        disabled>
                    <option value="">Chọn vùng trồng trước</option>
                </select>
                <p class="mt-1 text-xs text-base-content/40">Chỉ hiện mặt hàng do đúng Nông hộ của vùng trồng đã chọn kê khai.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày gieo</span></label>
                    <input type="text" name="sowing_date" id="fp-sowing_date" value="{{ old('sowing_date') }}"
                           class="input input-bordered input-sm w-full fp-init @error('sowing_date') input-error @enderror"
                           placeholder="DD/MM/YYYY" autocomplete="off">
                    @error('sowing_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Dự kiến thu hoạch</span></label>
                    <input type="text" name="expected_harvest_date" id="fp-expected_harvest_date" value="{{ old('expected_harvest_date') }}"
                           class="input input-bordered input-sm w-full fp-init @error('expected_harvest_date') input-error @enderror"
                           placeholder="DD/MM/YYYY" autocomplete="off">
                    @error('expected_harvest_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ghi chú</span></label>
                <textarea name="notes" id="farmingBatchNotes" rows="2" class="textarea textarea-bordered textarea-sm w-full"></textarea>
            </div>

            <div class="modal-action mt-2">
                <button type="button" class="btn btn-ghost btn-sm" onclick="farmingBatchModal.close()">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm" id="farmingBatchModalSubmit">Mở vụ / lô</button>
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
