@extends('layouts.backend')
@section('title', $salesOrder->misa_ref_id)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content font-mono">{{ $salesOrder->misa_ref_id }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $salesOrder->customer_name ?? '—' }}</p>
    </div>
    <div class="flex items-center gap-2">
        @can('print', $salesOrder)
        <button type="button" class="btn btn-sm gap-1.5 border-0 bg-blue-800 text-white hover:bg-blue-900"
                onclick="window.dispatchEvent(new CustomEvent('open-bulk-print'))">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
            In tem toàn bộ đơn
        </button>
        @endcan
        <a href="{{ route('backend.sales-orders.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
    </div>
</div>

@php
    $canPrint = auth()->user()->can('print', $salesOrder);
    $itemRows = $salesOrder->items->map(fn ($item) => [
        'id'            => $item->id,
        'line_no'       => $item->line_no,
        'product_name'  => $item->product?->name ?? $item->product_name_raw,
        'name'          => $item->product_name_raw,
        'sku'           => $item->product?->sku,
        'unit'          => $item->unit_raw,
        'requested_qty' => number_format((float) $item->requested_qty, 3),
        'requested_qty_raw' => (float) $item->requested_qty,
        'actual_qty'    => $item->actual_qty !== null ? number_format((float) $item->actual_qty, 3) : null,
        'printed_qty'   => number_format((float) $item->printed_qty, 3),
        'printed_qty_raw' => (float) $item->printed_qty,
        'label_template_id' => $item->product?->label_template_id,
        'attributes_url' => route('backend.sales-orders.items.batch-attributes', $item),
        'history_url'   => route('backend.sales-orders.items.print-logs', $item),
        'shelf_life_days' => $item->product?->shelf_life_days,
        'print_url'     => $canPrint ? route('backend.sales-orders.items.print', $item) : null,
    ])->values();
@endphp

<div class="flex flex-col gap-6">

    @can('print', $salesOrder)
    <div x-data="bulkPrintOrder({{ Js::from(['url' => route('backend.sales-orders.print-all', $salesOrder)]) }})"
         @open-bulk-print.window="openConfirm()" class="contents" x-cloak>

        <div class="alert py-2.5 px-4 text-sm" :class="alertClass" x-show="message" x-transition>
            <span x-text="message"></span>
            <button type="button" class="btn btn-ghost btn-xs ml-auto" @click="message = ''">Đóng</button>
        </div>

        <div class="modal" :class="{ 'modal-open': confirming }" @keydown.escape.window="confirming = false">
            <div class="modal-box max-w-md">
                <h3 class="font-bold text-lg">In tem toàn bộ đơn</h3>
                <div class="mt-3 space-y-2 text-sm">
                    <template x-if="preview.total === 0">
                        <p class="text-base-content/70">Tất cả mặt hàng đã được in đủ tem.</p>
                    </template>
                    <template x-if="preview.total > 0">
                        <div class="space-y-2">
                            <p>Sẽ in tem cho <strong x-text="preview.printable"></strong>/<strong x-text="preview.total"></strong> mặt hàng còn thiếu, với khối lượng = số lượng còn lại và HSD tự tính từ ngày hôm nay.</p>
                            <p class="text-warning" x-show="preview.manual > 0">
                                <strong x-text="preview.manual"></strong> mặt hàng chưa cấu hình số ngày bảo quản sẽ bị bỏ qua — cần in thủ công từng dòng để khai báo HSD.
                            </p>
                        </div>
                    </template>
                </div>
                <div class="modal-action mt-5">
                    <button type="button" class="btn btn-sm border-0 bg-blue-800 text-white hover:bg-blue-900"
                            :disabled="submitting || preview.printable === 0" @click="run()">
                        <span class="loading loading-spinner loading-xs" x-show="submitting"></span>
                        In tem
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" @click="confirming = false">Hủy</button>
                </div>
            </div>
            <div class="modal-backdrop" @click="confirming = false"></div>
        </div>
    </div>
    @endcan

    <div class="card w-full bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-4">Chi tiết đơn</p>

            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5 text-sm">
                <div class="space-y-4">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Khách hàng</dt>
                        <dd class="font-medium">{{ $salesOrder->customer_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Địa chỉ giao hàng</dt>
                        <dd>{{ $salesOrder->delivery_address ?? '—' }}</dd>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-1">Trạng thái</dt>
                        <dd><span class="badge badge-warning badge-soft badge-sm">{{ \Modules\SalesOrder\Models\SalesOrder::statusLabels()[$salesOrder->status] ?? $salesOrder->status }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">File nguồn</dt>
                        <dd class="font-mono text-xs break-all">{{ $salesOrder->source_file_name ?? '—' }}</dd>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Người import</dt>
                        <dd>{{ $salesOrder->importedBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Nhập lúc</dt>
                        <dd>{{ $salesOrder->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </div>
            </dl>
        </div>
    </div>

    <div class="card w-full bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-x-auto tabulator-daisy">
            <div id="sales-order-items-table" data-rows="{{ json_encode($itemRows, JSON_UNESCAPED_UNICODE) }}"></div>
        </div>
    </div>

</div>

@can('view', $salesOrder)
<div x-data="printHistoryModal()" @open-print-history.window="openFor($event.detail)" x-cloak>
    <div class="modal" :class="{ 'modal-open': open }" @keydown.escape.window="close()">
        <div class="modal-box max-w-5xl">
            <h3 class="font-bold text-lg">Lịch sử in tem của mặt hàng này</h3>
            <p class="text-sm text-base-content/60 mt-1" x-text="item?.product_name"></p>

            <div class="mt-4 overflow-x-auto rounded-md border border-gray-200">
                <table class="min-w-full border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Thời gian</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">KL/tem (kg)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Số tem</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Tổng (kg)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">HSD</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Người in</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">Đang tải...</td></tr>
                        </template>
                        <template x-if="!loading && error">
                            <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-red-600" x-text="error"></td></tr>
                        </template>
                        <template x-if="!loading && !error && logs.length === 0">
                            <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">Chưa có lần in nào.</td></tr>
                        </template>
                        <template x-for="log in logs" :key="log.id">
                            <tr class="border-b border-gray-100 last:border-b-0">
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="log.printed_at"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap font-mono" x-text="log.weight_per_label"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="log.label_count"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap font-mono" x-text="log.total_weight"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="log.exp_date"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="log.printed_by || '—'"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap text-right">
                                    <button type="button" x-show="log.reprint_url" @click="reprint(log)"
                                            class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded shadow-sm text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                                        In lại
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex items-center justify-between gap-4">
                <p class="text-xs italic text-gray-400 text-left">In lại chỉ mở lại tem cũ, không cộng dồn thêm khối lượng đã in.</p>
                <button type="button" @click="close()"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-xs font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 whitespace-nowrap">
                    Đóng
                </button>
            </div>
        </div>
        <div class="modal-backdrop" @click="close()"></div>
    </div>
</div>
@endcan

@can('print', $salesOrder)
<div x-data="printLabelModal()" @open-print-label.window="openFor($event.detail)" x-cloak>
    <div class="modal" :class="{ 'modal-open': open }" @keydown.escape.window="close()">
        <div class="modal-box max-w-6xl overflow-visible">

            <h3 class="card-title text-base mb-5">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
                </svg>
                Cấu hình In Tem
            </h3>

            <div class="divider my-4 text-xs text-base-content/30" x-text="item?.product_name"></div>

            <form @submit.prevent="submit()" novalidate>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="ts-label-template">
                            <span class="label-text font-medium">Mẫu tem in</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Mặc định theo mẫu gán cho sản phẩm</span>
                        </label>
                        <select id="ts-label-template" name="label_template_id"
                                class="select select-bordered select-sm w-full"
                                data-ts-placeholder="— Mẫu mặc định (theo sản phẩm / hệ thống) —">
                            <option value="">— Mẫu mặc định (theo sản phẩm / hệ thống) —</option>
                            @foreach($labelTemplates as $labelTemplate)
                            <option value="{{ $labelTemplate['value'] }}">{{ $labelTemplate['text'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-error" x-show="errors.label_template_id" x-text="errors.label_template_id"></p>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="pl-weight">
                            <span class="label-text font-medium">Khối lượng/Tem (kg) <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tự động theo SL yêu cầu</span>
                        </label>
                        <input id="pl-weight" type="number" name="weight_per_label" step="0.001" min="0.001" inputmode="decimal"
                               x-model="form.weight" x-ref="weight" placeholder="VD: 0.5" readonly disabled
                               class="input input-bordered input-sm w-full bg-base-200 text-base-content/70 cursor-not-allowed"
                               :class="{ 'input-error': errors.weight_per_label }">
                        <p class="mt-1 text-xs text-error" x-show="errors.weight_per_label" x-text="errors.weight_per_label"></p>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="pl-count">
                            <span class="label-text font-medium">Số lượng Tem cần in <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tối đa 200</span>
                        </label>
                        <input id="pl-count" type="number" name="label_count" step="1" min="1" max="200" inputmode="numeric"
                               x-model="form.count" placeholder="VD: 1"
                               class="input input-bordered input-sm w-full"
                               :class="{ 'input-error': errors.label_count }">
                        <p class="mt-1 text-xs text-error" x-show="errors.label_count" x-text="errors.label_count"></p>
                    </div>

                    {{-- NSX/HSD xếp chồng label-trên-input trong từng ô, 2 ô cạnh nhau trên màn hình vừa/lớn. --}}
                    <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-base-content mb-1" for="fp-label-mfg">
                                NSX <span class="text-xs font-normal text-base-content/40 ml-1">Mặc định hôm nay</span>
                            </label>
                            <input id="fp-label-mfg" type="text" name="mfg_date" autocomplete="off" placeholder="DD/MM/YYYY"
                                   class="input input-bordered input-sm w-full"
                                   :class="{ 'input-error': errors.mfg_date }">
                            <p class="mt-1 text-xs text-error" x-show="errors.mfg_date" x-text="errors.mfg_date"></p>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-base-content mb-1" for="fp-label-exp">
                                HSD <span class="text-error">*</span>
                                <span class="text-xs font-normal text-base-content/40 ml-1" x-show="item?.shelf_life_days"
                                      x-text="'NSX + ' + item?.shelf_life_days + ' ngày'"></span>
                            </label>
                            <input id="fp-label-exp" type="text" name="exp_date" autocomplete="off" placeholder="DD/MM/YYYY"
                                   class="input input-bordered input-sm w-full"
                                   :class="{ 'input-error': errors.exp_date }">
                            <p class="mt-1 text-xs text-base-content/40" x-show="item?.shelf_life_days && !errors.exp_date">
                                Tự tính theo số ngày bảo quản của sản phẩm, vẫn có thể chỉnh tay.
                            </p>
                            <p class="mt-1 text-xs text-error" x-show="errors.exp_date" x-text="errors.exp_date"></p>
                        </div>

                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="pl-batch-code">
                            <span class="label-text font-medium">Mã lô</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tự sinh theo NSX/HSD</span>
                        </label>
                        <input id="pl-batch-code" type="text" name="batch_code" readonly disabled
                               x-model="form.batchCode" placeholder="LOT-..."
                               class="input input-bordered input-sm w-full bg-base-200 text-base-content/70 cursor-not-allowed font-mono">
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="ts-supplier">
                            <span class="label-text font-medium">Nguồn cung</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn</span>
                        </label>

                        <div x-show="!form.supplierManual">
                            <select id="ts-supplier" name="vendor_id"
                                    class="select select-bordered select-sm w-full"
                                    data-ts-placeholder="— Chọn nhà cung cấp —">
                                <option value="">— Chọn nhà cung cấp —</option>
                                @foreach($vendors as $vendor)
                                <option value="{{ $vendor['value'] }}" data-text="{{ $vendor['text'] }}">{{ $vendor['text'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="form.supplierManual">
                            <input id="pl-supplier-manual" type="text" name="supplier_name" maxlength="255"
                                   x-model="form.supplierText" placeholder="VD: HTX Rau sạch Đà Lạt"
                                   class="input input-bordered input-sm w-full"
                                   :class="{ 'input-error': errors.supplier_name }">
                        </div>

                        <label class="label cursor-pointer justify-start gap-2 py-1.5" for="pl-supplier-manual-toggle">
                            <input id="pl-supplier-manual-toggle" type="checkbox" x-model="form.supplierManual"
                                   @change="onSupplierManualToggle()" class="checkbox checkbox-xs">
                            <span class="label-text text-xs">Khác / Nhập tay</span>
                        </label>

                        <p class="mt-1 text-xs text-error" x-show="errors.supplier_name" x-text="errors.supplier_name"></p>
                    </div>

                </div>

                <div class="divider my-4 text-xs text-base-content/30">Thông tin in bổ sung</div>

                <div class="space-y-2">
                    <p class="text-xs text-base-content/40" x-show="loadingAttributes">Đang tải thông tin của lô hàng...</p>
                    <p class="text-xs text-base-content/40" x-show="!loadingAttributes && attributes.length === 0">
                        Chưa có thông tin bổ sung. Bấm "Thêm thông tin" để in thêm HDSD, Liều dùng, Bảo quản...
                    </p>

                    <template x-for="(attr, index) in attributes" :key="attr.uid">
                        <div class="grid grid-cols-[1fr_1.6fr_auto] gap-2 items-center">
                            <input type="text" maxlength="100" x-model="attr.key" placeholder="Tên thông tin (VD: HDSD)"
                                   class="input input-bordered input-sm w-full">
                            <input type="text" maxlength="1000" x-model="attr.value" placeholder="Nội dung"
                                   class="input input-bordered input-sm w-full">
                            <button type="button" class="btn btn-ghost btn-sm text-error" @click="removeAttribute(index)" title="Xóa dòng này">Xóa</button>
                        </div>
                    </template>

                    <p class="text-xs text-error" x-show="errors.extra_attributes" x-text="errors.extra_attributes"></p>

                    <button type="button" class="btn btn-ghost btn-sm gap-1.5 text-primary" @click="addAttribute()">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Thêm thông tin
                    </button>
                </div>

                <div class="alert alert-warning py-2 px-3 mt-4 text-xs" x-show="message" x-text="message"></div>
                <div class="alert alert-warning py-2 px-3 mt-4 text-xs" x-show="blockedUrl">
                    Trình duyệt đã chặn cửa sổ in. <a :href="blockedUrl" target="_blank" class="link font-semibold">Bấm vào đây để mở tem</a>.
                </div>

                <div class="flex items-center gap-3 pt-4 mt-4 border-t border-base-200">
                    <p class="text-xs text-base-content/40" x-show="!canSubmit && !submitting">
                        Nhập khối lượng &gt; 0 và HSD để in tem
                    </p>
                    <div class="ml-auto flex gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" @click="close()">Hủy</button>
                        <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="!canSubmit">
                            <span class="loading loading-spinner loading-xs" x-show="submitting"></span>
                            <span x-text="submitting ? 'Đang xử lý...' : 'In tem'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" @click="close()"></div>
    </div>
</div>
@endcan
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/SalesOrder/resources/assets/sass/salesorder.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/SalesOrder/resources/assets/js/salesorder.js',
    ], 'build/backend')
@endpush
