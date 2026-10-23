@extends('layouts.backend')
@section('title', $goodsReceipt ? 'Sửa phiếu ' . $goodsReceipt->misa_ref_id : 'Thêm mới đơn nhập hàng')

@section('content')
@php
    $refLocked = $goodsReceipt && ! $goodsReceipt->isManual();
    $backUrl = $goodsReceipt ? route('backend.goods-receipts.show', $goodsReceipt) : route('backend.goods-receipts.index');
@endphp
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $goodsReceipt ? 'Sửa phiếu nhập kho' : 'Thêm mới đơn nhập hàng' }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            @if($goodsReceipt)
                <span class="font-mono">{{ $goodsReceipt->misa_ref_id }}</span> — chỉ sửa được khi lô chưa in tem, chưa kiểm duyệt truy xuất và chưa ghi QC
            @else
                Nhập tay phiếu nhập kho — mỗi sản phẩm sinh một lô hàng, NSX/HSD bổ sung ở trang chi tiết
            @endif
        </p>
    </div>
    <a href="{{ $backUrl }}" class="btn btn-ghost btn-sm">Quay lại</a>
</div>

@error('receipt')
<div role="alert" class="alert alert-error alert-soft mb-4 text-sm">{{ $message }}</div>
@enderror

<form method="POST"
      action="{{ $goodsReceipt ? route('backend.goods-receipts.update', $goodsReceipt) : route('backend.goods-receipts.store') }}"
      x-data="goodsReceiptForm({{ Js::from($formData) }})"
      @submit="onSubmit($event)"
      class="space-y-6">
    @csrf
    @if($goodsReceipt)
        @method('PUT')
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-6 gap-5">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Thông tin phiếu</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                <label class="form-control w-full">
                    <span class="label-text text-sm mb-1">Số phiếu <span class="text-error">*</span></span>
                    <input type="text" name="misa_ref_id" x-model="fields.misa_ref_id" maxlength="50"
                           @readonly($refLocked)
                           class="input input-bordered input-sm w-full font-mono {{ $refLocked ? 'bg-base-200' : '' }}"
                           :class="{ 'input-error': err('misa_ref_id') }">
                    <span class="text-xs text-base-content/40 mt-1">
                        {{ $refLocked ? 'Số phiếu MISA (phiếu import) — không sửa được.' : 'Tự sinh dạng PN-yymmdd-XXXX, có thể sửa lại. Mã lô = Số phiếu-Mã hàng.' }}
                    </span>
                    <span class="text-xs text-error mt-1" x-show="err('misa_ref_id')" x-text="err('misa_ref_id')"></span>
                </label>

                <label class="form-control w-full">
                    <span class="label-text text-sm mb-1">Ngày nhập <span class="text-error">*</span></span>
                    <input type="text" name="receipt_date" value="{{ $formData['fields']['receipt_date'] }}" required
                           class="input input-bordered input-sm w-full fp-init {{ $errors->has('receipt_date') ? 'input-error' : '' }}">
                    <span class="text-xs text-base-content/40 mt-1">Dùng làm gốc tự tính HSD khi chưa có NSX.</span>
                    <span class="text-xs text-error mt-1" x-show="err('receipt_date')" x-text="err('receipt_date')"></span>
                </label>

                <div class="form-control w-full">
                    <span class="label-text text-sm mb-1">Nhà cung cấp <span class="text-error">*</span></span>
                    <select name="vendor_id" x-ref="vendorSelect"></select>
                    @if($goodsReceipt && ! $goodsReceipt->isManual() && $goodsReceipt->supplier_name)
                        <span class="text-xs text-base-content/40 mt-1">Tên trên file MISA: {{ $goodsReceipt->supplier_name }}</span>
                    @else
                        <span class="text-xs text-base-content/40 mt-1">Bắt buộc chọn từ danh mục để liên kết lô canh tác.</span>
                    @endif
                    <span class="text-xs text-error mt-1" x-show="err('vendor_id')" x-text="err('vendor_id')"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-6 gap-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Hàng hóa</p>
                <span class="text-sm text-base-content/60">
                    <span x-text="filledCount"></span> mặt hàng · Tổng SL nhập: <span class="font-semibold" x-text="formatQty(totalQty)"></span>
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
                            <th class="w-40 text-right">SL nhập <span class="text-error">*</span></th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="row.uid">
                            <tr class="align-top">
                                <td class="text-center pt-3" x-text="index + 1"></td>
                                <td>
                                    <select :name="'items[' + row.uid + '][product_id]'" x-init="initProductSelect($el, row)"></select>
                                    <span class="text-xs text-error" x-show="err('items.' + row.uid + '.product_id')" x-text="err('items.' + row.uid + '.product_id')"></span>
                                    <span class="text-xs text-warning block" x-show="row.raw_name && row.product && String(row.product.id) === String(row.orig_product_id)" x-cloak x-text="'⚠ Tên trên phiếu: ' + row.raw_name + ' — khác tên sản phẩm trong danh mục, kiểm tra lại mã hàng.'"></span>
                                </td>
                                <td class="pt-3 font-mono text-xs" x-text="row.product?.sku || '—'"></td>
                                <td class="pt-3 text-sm" x-text="row.product?.unit || '—'"></td>
                                <td>
                                    <input type="number" :step="qtyStep(row)" :min="qtyStep(row)" :inputmode="row.product?.decimal ? 'decimal' : 'numeric'"
                                           :placeholder="row.product ? (row.product.decimal ? 'VD: 1.25' : 'Số nguyên') : ''"
                                           @keydown="blockQtyKeys($event, row)"
                                           :name="'items[' + row.uid + '][quantity]'" x-model="row.quantity"
                                           class="input input-bordered input-sm w-full text-right"
                                           :class="{ 'input-error': err('items.' + row.uid + '.quantity') }">
                                    <span class="text-xs text-error" x-show="err('items.' + row.uid + '.quantity')" x-text="err('items.' + row.uid + '.quantity')"></span>
                                </td>
                                <td class="pt-2">
                                    <button type="button" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-error"
                                            title="Xóa dòng" @click="removeRow(row.uid)">
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
        <a href="{{ $backUrl }}" class="btn btn-ghost btn-sm">Hủy</a>
        <button type="submit" class="btn btn-primary btn-sm" :disabled="submitting">
            <span class="loading loading-spinner loading-xs" x-show="submitting" x-cloak></span>
            {{ $goodsReceipt ? 'Lưu thay đổi' : 'Tạo phiếu nhập kho' }}
        </button>
    </div>
</form>
@endsection

@push('styles')
    @vite(['Modules/GoodsReceipt/resources/assets/sass/goodsreceipt.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/GoodsReceipt/resources/assets/js/goodsreceipt.js',
    ], 'build/backend')
@endpush
