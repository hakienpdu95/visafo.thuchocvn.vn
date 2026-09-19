@extends('layouts.backend')
@section('title', 'Quản lý Tem / Nhật ký TXNG')

@section('content')
<div x-data="traceLogListPage({{ Js::from([
    'apiUrl'    => route('backend.api.trace-logs'),
    'customers' => $customers,
    'statuses'  => $statuses,
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Quản lý Tem / Nhật ký TXNG</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tra cứu ngược mọi tem đã in — quét mã QR hoặc nhập Mã TXNG, sản phẩm, số đơn hàng</p>
        </div>
        <div class="dropdown dropdown-end">
            <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                </svg>
                Cột
            </label>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-52 z-50 p-2">
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
    </div>

    <div class="section-page">
        <div class="card bg-base-100 mb-4">
            <div class="card-body py-3 px-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">

                    <div class="form-control lg:col-span-2">
                        <label class="label mb-2">
                            <span class="label-text text-xs font-medium">Tìm kiếm / Quét mã</span>
                            <span class="label-text-alt text-xs text-base-content/40">Mã TXNG, sản phẩm, số đơn — Enter để tìm</span>
                        </label>
                        <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            <input id="filter-search" type="text" autocomplete="off" autofocus
                                   x-model="filters.search"
                                   @input.debounce.350ms="onFilterChange()"
                                   @keydown.enter.prevent="onSearchEnter($event)"
                                   placeholder="Quét QR hoặc nhập từ khóa..."
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
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Khách hàng</span></label>
                        <select id="ts-customer" x-model="filters.customer" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả khách hàng"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer['value'] }}">{{ $customer['text'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                        <select id="ts-status" x-model="filters.status" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả trạng thái"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($statuses as $status)
                            <option value="{{ $status['value'] }}">{{ $status['text'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">In từ ngày</span></label>
                        <input id="fp-date-from" type="text" placeholder="dd/mm/yyyy" autocomplete="off"
                               class="input input-sm input-bordered w-full"/>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Đến ngày</span></label>
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
                <div id="trace-log-table"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal hồ sơ tem ─────────────────────────────────────────────── --}}
<div x-data="traceLogDetailModal()" @open-trace-detail.window="openFor($event.detail)" x-cloak>
    <div class="modal" :class="{ 'modal-open': open }" @keydown.escape.window="close()">
        <div class="modal-box max-w-4xl">

            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-bold text-lg">Hồ sơ tem truy xuất</h3>
                    <p class="mt-0.5 font-mono text-sm text-primary" x-show="detail" x-text="detail ? detail.trace_code.toUpperCase() : ''"></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge badge-soft" :class="detail?.status_badge" x-show="detail" x-text="detail?.status_label"></span>
                    <button type="button" class="btn btn-ghost btn-sm btn-square" @click="close()" title="Đóng">✕</button>
                </div>
            </div>

            <p class="py-10 text-center text-sm text-base-content/40" x-show="loading">Đang tải hồ sơ tem...</p>
            <p class="py-10 text-center text-sm text-error" x-show="error" x-text="error"></p>

            <div x-show="detail && !loading" class="space-y-5">

                <div class="alert alert-error py-2 px-3 text-sm" x-show="detail && detail.status !== 'active'">
                    <span>
                        <strong x-text="detail?.status_label"></strong>
                        <template x-if="detail?.status_reason"><span> — <span x-text="detail.status_reason"></span></span></template>
                        <template x-if="detail?.status_changed_at">
                            <span class="opacity-80"> (<span x-text="detail.status_changed_at"></span><template x-if="detail.status_changed_by"><span> · <span x-text="detail.status_changed_by"></span></span></template>)</span>
                        </template>
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Bản preview tờ tem --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-base-content/40 mb-2">Bản xem trước tem</p>
                        <template x-if="detail">
                            <iframe :src="detail.preview_url" title="Xem trước tem"
                                    class="w-full h-64 rounded-lg border border-base-200 bg-gray-100"></iframe>
                        </template>
                        <a :href="detail?.public_url" target="_blank" rel="noopener" class="mt-2 inline-block text-xs text-primary hover:underline" x-show="detail">
                            Mở trang truy xuất công khai ↗
                        </a>
                    </div>

                    {{-- Thông tin chính --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-base-content/40 mb-2">Thông tin tem</p>
                        <dl class="divide-y divide-base-200 text-sm">
                            <div class="flex justify-between gap-4 py-2"><dt class="text-base-content/50 shrink-0">Sản phẩm</dt><dd class="text-right font-medium" x-text="detail?.product?.name || '—'"></dd></div>
                            <div class="flex justify-between gap-4 py-2">
                                <dt class="text-base-content/50 shrink-0">Đơn hàng</dt>
                                <dd class="text-right">
                                    <template x-if="detail?.order"><a :href="detail.order.url" class="font-mono text-primary hover:underline" x-text="detail.order.misa_ref_id"></a></template>
                                    <template x-if="!detail?.order"><span>—</span></template>
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2"><dt class="text-base-content/50 shrink-0">Khách hàng</dt><dd class="text-right" x-text="detail?.order?.customer_name || '—'"></dd></div>
                            <div class="flex justify-between gap-4 py-2"><dt class="text-base-content/50 shrink-0">Khối lượng</dt><dd class="text-right font-mono" x-text="detail?.weight"></dd></div>
                            <div class="flex justify-between gap-4 py-2"><dt class="text-base-content/50 shrink-0">NSX / HSD</dt><dd class="text-right" x-text="(detail?.mfg_date || '—') + ' / ' + (detail?.exp_date || '—')"></dd></div>
                            <div class="flex justify-between gap-4 py-2"><dt class="text-base-content/50 shrink-0">In lúc</dt><dd class="text-right" x-text="detail ? (detail.printed_at + (detail.printed_by ? ' · ' + detail.printed_by : '')) : ''"></dd></div>
                            <div class="flex justify-between gap-4 py-2"><dt class="text-base-content/50 shrink-0">Mẫu tem</dt><dd class="text-right" x-text="detail?.template || 'Mặc định'"></dd></div>
                            <div class="flex justify-between gap-4 py-2"><dt class="text-base-content/50 shrink-0">Cùng lần in</dt><dd class="text-right" x-text="detail ? detail.session_count + ' tem' : ''"></dd></div>
                        </dl>
                    </div>
                </div>

                {{-- Thông tin động (EAV) đã in trên tem --}}
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-base-content/40 mb-2">Thông tin bổ sung đã in</p>
                    <p class="text-sm text-base-content/40" x-show="detail && detail.attributes.length === 0">Tem này không có thông tin bổ sung.</p>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 text-sm" x-show="detail && detail.attributes.length > 0">
                        <template x-for="attr in (detail?.attributes ?? [])" :key="attr.key">
                            <div class="flex justify-between gap-4 border-b border-base-200 py-2">
                                <dt class="text-base-content/50 shrink-0" x-text="attr.key"></dt>
                                <dd class="text-right font-medium" x-text="attr.value || '—'"></dd>
                            </div>
                        </template>
                    </dl>
                </div>

                {{-- Vị trí vật lý hiện tại --}}
                <div class="rounded-lg border border-base-200 bg-base-200/30 p-3 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-base-content/40 mb-1">Vị trí vật lý hiện tại</p>
                    <p class="font-medium" x-text="detail?.location?.label"></p>
                    <p class="text-base-content/60" x-show="detail?.location?.detail" x-text="detail?.location?.detail"></p>
                </div>

                {{-- Đổi trạng thái (chỉ QC có quyền quản lý) --}}
                <template x-if="detail && detail.can_manage">
                    <form class="rounded-lg border border-base-200 p-4 space-y-3" @submit.prevent="saveStatus()" novalidate>
                        <p class="text-xs font-semibold uppercase tracking-wide text-base-content/40">Cập nhật trạng thái tem</p>

                        <div class="flex flex-wrap gap-4">
                            <template x-for="opt in statusOptions" :key="opt.value">
                                <label class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="radio" name="trace_status" class="radio radio-sm"
                                           :class="opt.value === 'active' ? 'radio-success' : 'radio-error'"
                                           :value="opt.value" x-model="form.status">
                                    <span x-text="opt.label"></span>
                                </label>
                            </template>
                        </div>

                        <div class="form-control" x-show="form.status !== 'active'" x-transition>
                            <label class="label py-0 pb-1.5" for="tl-reason">
                                <span class="label-text font-medium">Lý do thu hồi / lỗi <span class="text-error">*</span></span>
                                <span class="label-text-alt text-base-content/40 text-xs">Hiển thị cho người tiêu dùng khi quét QR</span>
                            </label>
                            <input id="tl-reason" type="text" maxlength="255" x-model="form.reason"
                                   class="input input-bordered input-sm w-full" placeholder="VD: Phát hiện dư lượng thuốc BVTV vượt ngưỡng">
                        </div>

                        <label class="flex items-start gap-2 cursor-pointer text-sm" x-show="detail.session_count > 1">
                            <input type="checkbox" class="checkbox checkbox-sm mt-0.5" x-model="form.applySession">
                            <span>Áp dụng cho tất cả <strong x-text="detail.session_count"></strong> tem cùng lần in</span>
                        </label>

                        <p class="text-xs text-error" x-show="formError" x-text="formError"></p>
                        <p class="text-xs text-success" x-show="message" x-text="message"></p>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="saving">
                                <span class="loading loading-spinner loading-xs" x-show="saving"></span>
                                <span x-text="saving ? 'Đang xử lý...' : 'Lưu trạng thái'"></span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>

            <div class="modal-action mt-4">
                <button type="button" class="btn btn-ghost btn-sm" @click="close()">Đóng</button>
            </div>
        </div>
        <div class="modal-backdrop" @click="close()"></div>
    </div>
</div>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/TraceLog/resources/assets/sass/tracelog.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/TraceLog/resources/assets/js/tracelog.js',
    ], 'build/backend')
@endpush
