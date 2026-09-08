@extends('layouts.backend')
@section('title', 'Cảnh báo pháp lý & hạn dùng')

@section('content')
<div x-data="complianceWarningListPage({{ Js::from([
    'apiUrl'     => route('backend.api.compliance-warnings'),
    'statuses'   => $statuses,
    'categories' => $categories,
    'severities' => $severities,
    'canManage'  => auth()->user()->can('compliance.manage'),
    'csrfToken'  => csrf_token(),
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Cảnh báo pháp lý & hạn dùng</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Hộp thư tập trung — hồ sơ sản phẩm, chứng chỉ nhà cung cấp, lô hàng cận date</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control w-56">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Trạng thái</span>
                    </label>
                    <select x-model="filters.status" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">Đang mở (mặc định)</option>
                        @foreach($statuses as $status)
                        <option value="{{ $status['value'] }}">{{ $status['text'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control w-64">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Loại cảnh báo</span>
                    </label>
                    <select x-model="filters.category" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">Tất cả</option>
                        @foreach($categories as $category)
                        <option value="{{ $category['value'] }}">{{ $category['text'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control w-48">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Mức độ</span>
                    </label>
                    <select x-model="filters.severity" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">Tất cả</option>
                        @foreach($severities as $severity)
                        <option value="{{ $severity['value'] }}">{{ $severity['text'] }}</option>
                        @endforeach
                    </select>
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
            <div id="compliance-warning-table"></div>
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
        'Modules/Compliance/resources/assets/js/compliance.js',
    ], 'build/backend')
@endpush
