@extends('layouts.backend')
@section('title', 'Vùng trồng')

@section('content')
<div x-data="farmingSourceListPage({{ Js::from([
    'apiUrl' => route('backend.api.farming-sources'),
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Quản lý Vùng trồng</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Số hóa BM-NH-01 (Đăng ký) &amp; BM-NH-02 (Đánh giá trước vụ) — QT-NH-01</p>
        </div>
        @can('create', \Modules\Product\Models\FarmingSource::class)
        <button type="button" class="btn btn-primary btn-sm gap-1.5" onclick="openFarmingSourceModal()">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm vùng trồng
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
                        <span class="label-text-alt text-xs text-base-content/40">Tên vùng, mã vùng, tên nông hộ</span>
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
                        <option value="pending">Chờ kiểm tra</option>
                        <option value="passed">Đã kiểm tra — Đạt</option>
                        <option value="failed">Đã kiểm tra — Không đạt</option>
                    </select>
                </div>
                <button @click="reset()" x-show="hasFilters" x-transition class="btn btn-ghost btn-sm gap-1.5 text-error">Đặt lại</button>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="farming-source-table"></div>
        </div>
    </div>

</div>

@can('create', \Modules\Product\Models\FarmingSource::class)
<dialog id="farmingSourceModal" class="modal">
    <div class="modal-box max-w-lg">
        <h3 class="font-bold text-lg mb-1" id="farmingSourceModalTitle">Thêm vùng trồng</h3>
        <p class="text-xs text-base-content/40 mb-4">Đăng ký vùng trồng của Nông hộ (BM-NH-01)</p>

        @if($errors->any())
        <div class="alert alert-error py-2 px-3 mb-3 text-xs">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('backend.farming-sources.store') }}" class="space-y-3"
              data-create-url="{{ route('backend.farming-sources.store') }}"
              data-update-url-template="{{ route('backend.farming-sources.update', '__ID__') }}">
            @csrf
            <input type="hidden" name="_method" value="">

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Nông hộ (Nhà cung cấp) <span class="text-error">*</span></span></label>
                <select id="ts-farming-source-vendor" name="vendor_id" class="select select-bordered select-sm w-full" data-ts-placeholder="— Chọn nông hộ —">
                    <option value="">— Chọn nông hộ —</option>
                    @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tên / Mô tả vùng trồng <span class="text-error">*</span></span></label>
                <input type="text" name="name" class="input input-bordered input-sm w-full" placeholder="VD: Thửa ruộng số 3">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Diện tích (ha)</span></label>
                    <input type="number" step="0.01" min="0" name="area_hectare" class="input input-bordered input-sm w-full">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Nguồn nước</span></label>
                    <input type="text" name="water_source" class="input input-bordered input-sm w-full" placeholder="VD: Giếng khoan, kênh mương">
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Địa chỉ / Vị trí</span></label>
                <input type="text" name="address" class="input input-bordered input-sm w-full">
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ghi chú</span></label>
                <textarea name="notes" rows="2" class="textarea textarea-bordered textarea-sm w-full"></textarea>
            </div>

            <div class="modal-action mt-2">
                <button type="button" class="btn btn-ghost btn-sm" onclick="farmingSourceModal.close()">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm" id="farmingSourceModalSubmit">Lưu vùng trồng</button>
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
        'resources/js/modules/tom-select.js',
        'resources/js/modules/tabulator.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
