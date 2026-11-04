@extends('layouts.backend')
@section('title', 'Liên kết đơn HanoiCheck')

@section('content')
<div x-data="hanoiCheckOrderListPage({{ Js::from([
    'apiUrl'   => route('backend.api.hanoicheck-orders'),
    'statuses' => $statuses,
    'links'    => $links,
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Liên kết đơn HanoiCheck</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Dán mã đơn từ cổng HanoiCheck vào đơn xuất hàng VISAFO tương ứng — đã liên kết <strong class="text-base-content">{{ number_format($stats['linked']) }}</strong> / {{ number_format($stats['total']) }} đơn</p>
        </div>
        <div class="dropdown dropdown-end">
            <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                Cột
            </label>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-52 z-50 p-2">
                <template x-for="col in toggleableCols" :key="col.field">
                    <li>
                        <label class="flex items-center gap-2 cursor-pointer py-1.5 px-2 rounded-lg hover:bg-base-200">
                            <input type="checkbox" class="checkbox checkbox-xs" :checked="!hiddenCols.includes(col.field)" @change="toggleCol(col.field)"/>
                            <span x-text="col.title" class="text-sm"></span>
                        </label>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <div class="section-page">
        <div class="card bg-base-100 mb-4">
            <div class="card-body py-3 px-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                    <div class="form-control lg:col-span-2">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Tìm kiếm</span>
                            <span class="label-text-alt text-xs text-base-content/40">Số đơn, khách hàng, vận đơn, mã HanoiCheck</span>
                        </label>
                        <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/></svg>
                            <input type="text" autocomplete="off" x-model="filters.search" @input.debounce.350ms="onFilterChange()"
                                   placeholder="Nhập từ khóa..." class="grow bg-transparent outline-none text-sm"/>
                            <button x-show="filters.search" @click="clearSearch()" class="text-base-content/30 hover:text-base-content transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Liên kết</span></label>
                        <select id="ts-link" x-model="filters.link" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($links as $link)
                            <option value="{{ $link['value'] }}">{{ $link['text'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái đơn</span></label>
                        <select id="ts-status" x-model="filters.status" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($statuses as $status)
                            <option value="{{ $status['value'] }}">{{ $status['text'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Giao từ ngày</span></label>
                        <input id="fp-date-from" type="text" placeholder="dd/mm/yyyy" autocomplete="off" class="input input-sm input-bordered w-full"/>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Đến ngày</span></label>
                        <input id="fp-date-to" type="text" placeholder="dd/mm/yyyy" autocomplete="off" class="input input-sm input-bordered w-full"/>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button @click="reset()" x-show="hasFilters" x-cloak x-transition class="btn btn-ghost btn-sm gap-1.5 text-error mt-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Đặt lại
                    </button>
                </div>

                <div x-show="activeChips.length > 0" x-cloak x-transition class="flex flex-wrap gap-2 pt-3 mt-3 border-t border-base-200">
                    <span class="text-xs text-base-content/40 self-center">Đang lọc:</span>
                    <template x-for="chip in activeChips" :key="chip.key">
                        <span class="badge badge-sm gap-1 cursor-pointer hover:badge-error transition-colors" @click="removeChip(chip.key)">
                            <span x-text="chip.label"></span>
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0 overflow-hidden tabulator-daisy">
                <div id="hanoicheck-order-table"></div>
            </div>
        </div>
    </div>
</div>

<div x-data="hanoiCheckLinkModal()" @open-hanoicheck-link.window="show($event.detail)" x-cloak>
    <div class="modal" :class="{ 'modal-open': open }" @keydown.escape.window="open = false">
        <div class="modal-box max-w-lg">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-bold text-lg">Mã đơn HanoiCheck</h3>
                    <p class="text-xs text-base-content/60 mt-0.5">
                        Đơn <span class="font-mono font-semibold" x-text="row?.misa_ref_id"></span> · <span x-text="row?.customer_name || '—'"></span>
                    </p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm btn-square" @click="open = false" title="Đóng">✕</button>
            </div>

            <form @submit.prevent="save()" novalidate class="space-y-4">
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Mã đơn trên cổng HanoiCheck</span></label>
                    <input type="text" x-ref="code" x-model="form.code" maxlength="255" autocomplete="off"
                           placeholder="Dán mã đơn, VD: DH2026090001" class="input input-bordered w-full font-mono"/>
                    <p class="text-xs text-base-content/50 mt-1">Chỉ gồm chữ không dấu, số và _ . - · Để trống rồi Lưu để gỡ liên kết.</p>
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Ghi chú</span></label>
                    <textarea x-model="form.note" rows="2" maxlength="500" class="textarea textarea-bordered w-full" placeholder="Tùy chọn — VD: tên trường đặt hàng"></textarea>
                </div>
                <p class="text-sm text-error" x-show="error" x-text="error"></p>
                <div class="modal-action mt-2">
                    <button type="button" class="btn btn-ghost btn-sm" @click="open = false">Hủy</button>
                    <button type="submit" class="btn btn-sm" :class="unlinking ? 'btn-error' : 'btn-primary'" :disabled="saving">
                        <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                        <span x-text="unlinking ? 'Gỡ liên kết' : 'Lưu'"></span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" @click="open = false"></div>
    </div>
</div>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/HanoiCheckOrder/resources/assets/sass/hanoicheckorder.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/HanoiCheckOrder/resources/assets/js/hanoicheckorder.js',
    ], 'build/backend')
@endpush
