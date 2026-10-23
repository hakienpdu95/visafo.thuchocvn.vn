@extends('layouts.backend')
@section('title', $salesOrder ? 'Sửa đơn ' . $salesOrder->misa_ref_id : 'Thêm mới đơn xuất hàng')

@section('content')
@php
    $printedOrder = $salesOrder && $salesOrder->hasPrintHistory();
    $refLocked = $salesOrder && (! $salesOrder->isManual() || $printedOrder);
@endphp
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $salesOrder ? 'Sửa đơn xuất hàng' : 'Thêm mới đơn xuất hàng' }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            @if($salesOrder)
                <span class="font-mono">{{ $salesOrder->misa_ref_id }}</span> — sửa được đến khi xuất kho{{ $printedOrder ? '. Đơn đã in tem: dòng bị gỡ hoặc đổi sản phẩm sẽ hủy tem đã in của dòng đó' : '' }}
            @else
                Nhập tay phiếu xuất kho — sản phẩm chọn từ danh mục Master Data
            @endif
        </p>
    </div>
    <a href="{{ $salesOrder ? route('backend.sales-orders.show', $salesOrder) : route('backend.sales-orders.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
</div>

@error('order')
<div role="alert" class="alert alert-error alert-soft mb-4 text-sm">{{ $message }}</div>
@enderror

<form method="POST"
      action="{{ $salesOrder ? route('backend.sales-orders.update', $salesOrder) : route('backend.sales-orders.store') }}"
      x-data="salesOrderForm({{ Js::from($formData) }})"
      @submit="onSubmit($event)"
      class="space-y-6">
    @csrf
    @if($salesOrder)
        @method('PUT')
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-6 gap-5">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Thông tin đơn</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                <label class="form-control w-full">
                    <span class="label-text text-sm mb-1">Số phiếu <span class="text-error">*</span></span>
                    <input type="text" name="misa_ref_id" x-model="fields.misa_ref_id" maxlength="50"
                           @readonly($refLocked)
                           class="input input-bordered input-sm w-full font-mono {{ $refLocked ? 'bg-base-200' : '' }}"
                           :class="{ 'input-error': err('misa_ref_id') }">
                    <span class="text-xs text-base-content/40 mt-1">
                        @if($salesOrder && ! $salesOrder->isManual())
                            Số phiếu MISA (đơn import) — không sửa được.
                        @elseif($printedOrder)
                            Đơn đã in tem — giữ nguyên số phiếu.
                        @else
                            Tự sinh dạng PX-yymmdd-XXXX, có thể sửa lại.
                        @endif
                    </span>
                    <span class="text-xs text-error mt-1" x-show="err('misa_ref_id')" x-text="err('misa_ref_id')"></span>
                </label>

                <label class="form-control w-full">
                    <span class="label-text text-sm mb-1">Ngày giao hàng <span class="text-error">*</span></span>
                    <input type="text" name="delivery_date" value="{{ $formData['fields']['delivery_date'] }}" required
                           class="input input-bordered input-sm w-full fp-init {{ $errors->has('delivery_date') ? 'input-error' : '' }}">
                    <span class="text-xs text-error mt-1" x-show="err('delivery_date')" x-text="err('delivery_date')"></span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="form-control w-full">
                    <span class="label-text text-sm mb-1">Khách hàng (danh mục)</span>
                    <select name="customer_id" x-ref="customerSelect"></select>
                    <span class="text-xs text-base-content/40 mt-1">Chọn để tự điền tên và địa chỉ — có thể bỏ trống nếu khách lẻ.</span>
                    <span class="text-xs text-error mt-1" x-show="err('customer_id')" x-text="err('customer_id')"></span>
                </div>

                <div class="form-control w-full">
                    <span class="label-text text-sm mb-1">Điểm giao</span>
                    <select x-ref="pointSelect"></select>
                    <span class="text-xs text-base-content/40 mt-1" x-show="!fields.customer_id">Chọn khách hàng trước.</span>
                    <span class="text-xs text-base-content/40 mt-1" x-show="fields.customer_id && !pointsLoading && points.length === 0" x-cloak>Khách hàng chưa khai báo điểm giao.</span>
                </div>

                <label class="form-control w-full">
                    <span class="label-text text-sm mb-1">Khách hàng / người nhận <span class="text-error">*</span></span>
                    <input type="text" name="customer_name" x-model="fields.customer_name" maxlength="100" required
                           class="input input-bordered input-sm w-full" :class="{ 'input-error': err('customer_name') || fields.customer_name.length > 100 }">
                    <span class="flex justify-between gap-2 text-xs mt-1">
                        <span class="text-error" x-text="err('customer_name')"></span>
                        <span class="shrink-0" :class="fields.customer_name.length > 100 ? 'text-error' : 'text-base-content/40'" x-text="fields.customer_name.length + '/100'"></span>
                    </span>
                </label>

                <label class="form-control w-full">
                    <span class="label-text text-sm mb-1">Địa chỉ giao hàng</span>
                    <textarea name="delivery_address" x-model="fields.delivery_address" rows="2" maxlength="200"
                              class="textarea textarea-bordered textarea-sm w-full resize-none" :class="{ 'textarea-error': err('delivery_address') || fields.delivery_address.length > 200 }"></textarea>
                    <span class="flex justify-between gap-2 text-xs mt-1">
                        <span class="text-error" x-text="err('delivery_address')"></span>
                        <span class="shrink-0" :class="fields.delivery_address.length > 200 ? 'text-error' : 'text-base-content/40'" x-text="fields.delivery_address.length + '/200'"></span>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-6 gap-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Hàng hóa</p>
                <span class="text-sm text-base-content/60">
                    <span x-text="filledCount"></span> mặt hàng · Tổng SL yêu cầu: <span class="font-semibold" x-text="formatQty(totalQty)"></span>
                </span>
            </div>

            <p class="text-xs text-error" x-show="err('items')" x-text="err('items')"></p>
            <p class="text-xs text-info" x-show="notice" x-text="notice" x-cloak></p>

            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr class="bg-base-200/60">
                            <th class="w-12 text-center">STT</th>
                            <th class="min-w-72">Sản phẩm <span class="text-error">*</span></th>
                            <th class="w-32">Mã hàng</th>
                            <th class="w-24">ĐVT</th>
                            <th class="w-40 text-right">SL yêu cầu <span class="text-error">*</span></th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="row.uid">
                            <tr class="align-top">
                                <td class="text-center pt-3" x-text="index + 1"></td>
                                <td>
                                    <select :name="row.qc ? null : 'items[' + row.uid + '][product_id]'" x-init="initProductSelect($el, row)"></select>
                                    <template x-if="row.qc">
                                        <input type="hidden" :name="'items[' + row.uid + '][product_id]'" :value="row.orig_product_id">
                                    </template>
                                    <span class="text-xs text-error" x-show="err('items.' + row.uid + '.product_id')" x-text="err('items.' + row.uid + '.product_id')"></span>
                                    <span class="text-xs text-base-content/50" x-show="row.qc" x-cloak>Đã có biên bản QC trước xuất — khóa sản phẩm và số lượng.</span>
                                    <span class="text-xs text-base-content/50" x-show="!row.qc && row.printed && !productChanged(row)" x-cloak x-text="'Đã in ' + row.printed + ' tem'"></span>
                                    <span class="text-xs text-warning" x-show="row.printed && productChanged(row)" x-cloak x-text="'Đổi sản phẩm sẽ hủy ' + row.printed + ' tem đã in của sản phẩm cũ.'"></span>
                                </td>
                                <td class="pt-3 font-mono text-xs" x-text="row.product?.sku || '—'"></td>
                                <td class="pt-3 text-sm" x-text="row.product?.unit || '—'"></td>
                                <td>
                                    <input type="number" :step="qtyStep(row)" :min="qtyStep(row)" :inputmode="row.product?.decimal ? 'decimal' : 'numeric'"
                                           :placeholder="row.product ? (row.product.decimal ? 'VD: 1.25' : 'Số nguyên') : ''"
                                           @keydown="blockQtyKeys($event, row)" :readonly="!!row.qc"
                                           :name="'items[' + row.uid + '][requested_qty]'" x-model="row.requested_qty"
                                           class="input input-bordered input-sm w-full text-right"
                                           :class="{ 'input-error': err('items.' + row.uid + '.requested_qty'), 'bg-base-200': row.qc }">
                                    <span class="text-xs text-error" x-show="err('items.' + row.uid + '.requested_qty')" x-text="err('items.' + row.uid + '.requested_qty')"></span>
                                </td>
                                <td class="pt-2">
                                    <button type="button" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-error"
                                            x-show="!row.qc" :title="row.printed ? 'Xóa dòng (hủy ' + row.printed + ' tem đã in)' : 'Xóa dòng'" @click="confirmRemoveRow(row)">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div>
                <button type="button" class="btn btn-outline btn-sm gap-1.5" @click="addRow()">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Thêm dòng hàng
                </button>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2">
        <a href="{{ $salesOrder ? route('backend.sales-orders.show', $salesOrder) : route('backend.sales-orders.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
        <button type="submit" class="btn btn-primary btn-sm" :disabled="submitting">
            <span class="loading loading-spinner loading-xs" x-show="submitting" x-cloak></span>
            {{ $salesOrder ? 'Lưu thay đổi' : 'Tạo đơn xuất hàng' }}
        </button>
    </div>
</form>
@endsection

@push('styles')
    @vite(['Modules/SalesOrder/resources/assets/sass/salesorder.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/SalesOrder/resources/assets/js/salesorder.js',
    ], 'build/backend')
@endpush
