@extends('layouts.backend')
@section('title', 'Liên kết đơn HanoiCheck')

@section('content')
<div x-data="hanoiCheckOrderListPage({{ Js::from([
    'apiUrl'   => route('backend.api.hanoicheck-orders'),
    'statuses' => $statuses,
    'links'    => $links,
    'syncStatuses' => $syncStatuses,
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Liên kết đơn HanoiCheck</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Dán mã đơn từ cổng HanoiCheck vào đơn xuất hàng VISAFO tương ứng — đã liên kết <strong class="text-base-content">{{ number_format($stats['linked']) }}</strong> / {{ number_format($stats['total']) }} đơn</p>
        </div>
        <div class="flex items-center gap-2">
        @if($live)
        <span class="badge badge-success badge-sm gap-1" title="Đang kết nối thật tới HanoiCheck">● Đang kết nối Sở</span>
        @else
        <span class="badge badge-ghost badge-sm" title="HANOICHECK_ENABLED=false hoặc HANOICHECK_FAKE=true">Chưa kết nối Sở</span>
        @endif
        @if($canManageTransporters)
        <a href="{{ route('backend.hanoicheck-transporters.index') }}" class="btn btn-outline btn-sm">Người giao hàng</a>
        @endif
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
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Đồng bộ Sở</span></label>
                        <select id="ts-sync" x-model="filters.sync" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($syncStatuses as $sync)
                            <option value="{{ $sync['value'] }}">{{ $sync['text'] }}</option>
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

<div x-data="hanoiCheckLinkModal({{ Js::from(['live' => $live, 'remoteUrl' => $remoteUrl]) }})" @open-hanoicheck-link.window="show($event.detail)" x-cloak>
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

            <div x-show="live" class="mb-4 rounded-lg border border-base-200 p-3 space-y-2">
                <div class="flex flex-wrap items-end gap-2">
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Ngày giao trên HanoiCheck</span></label>
                        <input type="date" x-model="remote.date" class="input input-bordered input-sm"/>
                    </div>
                    <div class="form-control grow">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Từ khóa (mã đơn, tên trường, thực phẩm)</span></label>
                        <input type="text" x-model="remote.keyword" @keydown.enter.prevent="searchRemote()" class="input input-bordered input-sm w-full"/>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline" @click="searchRemote()" :disabled="remote.loading">
                        <span x-show="remote.loading" class="loading loading-spinner loading-xs"></span> Tìm trên cổng
                    </button>
                </div>
                <p class="text-xs text-base-content/50" x-show="remote.message" x-text="remote.message"></p>
                <div class="max-h-48 overflow-y-auto divide-y divide-base-200 text-sm" x-show="remote.rows.length > 0">
                    <template x-for="r in remote.rows" :key="r.code">
                        <button type="button" @click="pick(r)" class="w-full text-left px-2 py-1.5 hover:bg-base-200 rounded"
                                :class="{ 'opacity-50 cursor-not-allowed': r.linked_to && r.linked_to !== row?.misa_ref_id, 'bg-primary/10': form.code === r.code }">
                            <p><span class="font-mono font-semibold" x-text="r.code"></span> · <span x-text="r.school_name || '—'"></span>
                                <span class="text-xs text-base-content/50" x-text="r.status_label ? '· ' + r.status_label : ''"></span></p>
                            <p class="text-xs text-base-content/50 truncate" x-text="r.products.join(', ')"></p>
                            <p class="text-xs text-warning" x-show="r.linked_to" x-text="'Đã liên kết với đơn ' + r.linked_to"></p>
                        </button>
                    </template>
                </div>
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
<div x-data="hanoiCheckDetailModal()" @open-hanoicheck-detail.window="show($event.detail)" x-cloak>
    <div class="modal" :class="{ 'modal-open': open }" @keydown.escape.window="open = false">
        <div class="modal-box max-w-4xl">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                    <h3 class="font-bold text-lg">Xử lý đơn trên HanoiCheck</h3>
                    <p class="text-xs text-base-content/60 mt-0.5" x-show="data">
                        Đơn <span class="font-mono font-semibold" x-text="data?.order.misa_ref_id"></span> ↔ HanoiCheck <span class="font-mono font-semibold" x-text="data?.link.code"></span>
                        · <span x-text="data?.link.school_name || data?.order.customer || '—'"></span>
                    </p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm btn-square" @click="open = false" title="Đóng">✕</button>
            </div>

            <p class="py-8 text-center text-sm text-base-content/40" x-show="loading">Đang tải...</p>
            <p class="text-sm text-error mb-2" x-show="error" x-text="error"></p>

            <template x-if="data && !loading">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg border border-base-200 p-3">
                            <p class="text-xs text-base-content/50">Trạng thái VISAFO</p>
                            <p class="font-medium" x-text="data.order.status_label"></p>
                            <p class="text-xs text-base-content/50 mt-1" x-show="data.order.target_label" x-text="'→ Cần đưa Sở về: ' + data.order.target_label"></p>
                        </div>
                        <div class="rounded-lg border border-base-200 p-3">
                            <p class="text-xs text-base-content/50">Trạng thái trên HanoiCheck</p>
                            <span class="badge badge-sm" :class="data.link.remote_badge || 'badge-ghost'" x-text="data.link.remote_status || 'Chưa đọc'"></span>
                            <p class="text-xs text-base-content/50 mt-1" x-show="data.link.fetched_at" x-text="'Đọc lúc ' + data.link.fetched_at"></p>
                        </div>
                        <div class="rounded-lg border border-base-200 p-3">
                            <p class="text-xs text-base-content/50">Đồng bộ</p>
                            <span class="badge badge-sm" :class="data.link.sync_status_badge" x-text="data.link.sync_status_label"></span>
                            <p class="text-xs text-base-content/50 mt-1" x-show="data.link.synced_at" x-text="'Lần cuối ' + data.link.synced_at"></p>
                        </div>
                    </div>

                    <div class="alert text-sm py-2" :class="data.link.sync_status === 'synced' ? 'alert-success' : (data.link.sync_status === 'failed' ? 'alert-error' : 'alert-warning')" x-show="data.link.sync_message">
                        <span x-text="data.link.sync_message"></span>
                    </div>
                    <div class="alert alert-info text-sm py-2" x-show="!data.live">
                        <span>Chưa kết nối HanoiCheck thật — thông tin giao hàng vẫn được lưu và sẽ tự đồng bộ khi bật kết nối.</span>
                    </div>

                    <div>
                        <p class="text-sm font-semibold mb-1">Nguồn hàng khai cho từng dòng (lấy từ tem đã in + lô nhập)</p>
                        <div class="overflow-x-auto rounded-lg border border-base-200">
                            <table class="table table-sm">
                                <thead><tr><th>Mã dòng (Sở)</th><th>Thực phẩm</th><th class="text-right">Trường đặt</th><th class="text-right">Đã khai</th><th>Lô / Kho</th><th>Ghi chú</th></tr></thead>
                                <tbody>
                                    <template x-for="l in data.link.lines" :key="l.trace_code">
                                        <tr>
                                            <td class="font-mono text-xs" x-text="l.trace_code"></td>
                                            <td><span x-text="l.food_name || l.food_code"></span><p class="font-mono text-xs text-base-content/50" x-text="l.food_code"></p></td>
                                            <td class="text-right font-mono" x-text="l.requested_amount"></td>
                                            <td class="text-right font-mono" :class="l.error ? 'text-warning' : 'text-success'" x-text="l.allocated_amount"></td>
                                            <td class="text-xs"><template x-for="p in l.phan_bo"><p class="font-mono" x-text="p.ma_lo + ' @ ' + p.ma_kho + ' : ' + p.so_luong"></p></template></td>
                                            <td class="text-xs" :class="l.error ? 'text-warning' : 'text-success'" x-text="l.error || 'Đủ'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="data.link.lines.length === 0"><td colspan="6" class="text-center text-base-content/40 py-4">Chưa đọc dòng hàng từ HanoiCheck.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-warning mt-1" x-show="Object.keys(data.link.leftover || {}).length > 0"
                           x-text="'Tem in dư so với số lượng trường đặt: ' + Object.entries(data.link.leftover).map(([k, v]) => k + ' ' + v).join(', ')"></p>
                    </div>

                    <form @submit.prevent="save()" class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="data.can_edit && !data.link.locked">
                        <div class="form-control">
                            <label class="label py-0.5"><span class="label-text text-sm font-medium">Người giao hàng</span><span class="label-text-alt text-xs">Bắt buộc trước khi "Đang giao"</span></label>
                            <select x-model="form.transporter_id" class="select select-bordered select-sm w-full">
                                <option value="">— Chưa chọn —</option>
                                <template x-for="t in data.transporters" :key="t.value"><option :value="t.value" x-text="t.text" :selected="t.value === form.transporter_id"></option></template>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label py-0.5"><span class="label-text text-sm font-medium">Ảnh tổng quan đơn (1–3)</span><span class="label-text-alt text-xs">Bắt buộc trước khi "Đã giao"</span></label>
                            <div class="flex flex-wrap gap-2 mb-2">
                                <template x-for="p in data.link.photos" :key="p.id">
                                    <label class="relative cursor-pointer" :title="form.remove.includes(String(p.id)) ? 'Sẽ xóa — bấm để giữ' : 'Bấm để đánh dấu xóa'">
                                        <img :src="p.url" class="w-16 h-16 object-cover rounded border" :class="form.remove.includes(String(p.id)) ? 'opacity-30 border-error' : 'border-base-200'">
                                        <input type="checkbox" class="hidden" @change="toggleRemove(p.id)">
                                    </label>
                                </template>
                            </div>
                            <input type="file" x-ref="photos" accept="image/jpeg,image/png,image/webp" multiple @change="onFiles($event)" class="file-input file-input-bordered file-input-sm w-full" :disabled="photoSlots <= 0">
                        </div>
                        <div class="md:col-span-2 flex justify-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline" @click="syncNow()" :disabled="saving || !data.live">Đồng bộ ngay</button>
                            <button type="submit" class="btn btn-sm btn-primary" :disabled="saving"><span x-show="saving" class="loading loading-spinner loading-xs"></span> Lưu thông tin giao hàng</button>
                        </div>
                    </form>

                    <details class="text-xs" x-show="data.logs.length > 0">
                        <summary class="cursor-pointer text-base-content/60">Nhật ký gọi API đơn này (10 lần gần nhất)</summary>
                        <div class="mt-2 space-y-1 font-mono">
                            <template x-for="l in data.logs"><p><span x-text="l.at"></span> · <span x-text="l.status"></span> · <span x-text="l.path"></span> <span x-text="l.http ? '(' + l.http + ')' : ''"></span> <span class="text-error" x-text="l.message || ''"></span></p></template>
                        </div>
                    </details>
                </div>
            </template>
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
