<div class="form-control" x-show="row.hasBatches">
    <label class="label py-0 pb-1">
        <span class="label-text text-xs font-medium">Lô xuất kho</span>
        <span class="label-text-alt text-base-content/40 text-xs" x-show="row.loadingBatches">Đang tải…</span>
    </label>
    <select class="select select-bordered select-sm w-full" x-init="initRowBatch(row, $el)">
        <option value="">— Không chọn lô —</option>
    </select>
</div>
<div class="form-control" x-show="row.hasBatches && row.batchId">
    <label class="label py-0 pb-1">
        <span class="label-text text-xs font-medium">Nhà cung cấp</span>
        <span class="label-text-alt text-base-content/40 text-xs">Theo lô — không sửa</span>
    </label>
    <input type="text" readonly disabled :value="supplierOf(row) || 'Chưa rõ NCC'"
           class="input input-bordered input-sm w-full bg-base-200 text-base-content/70 cursor-not-allowed">
</div>
<div class="form-control" x-show="!row.hasBatches || !row.batchId">
    <label class="label py-0 pb-1">
        <span class="label-text text-xs font-medium">Nguồn cung</span>
        <span class="label-text-alt text-base-content/40 text-xs" x-text="row.hasBatches ? 'Không chọn lô — chọn nguồn cung thủ công' : 'Sản phẩm không quản lý theo lô'"></span>
    </label>
    <div x-show="!row.supplierManual">
        <select class="select select-bordered select-sm w-full" x-init="initRowSupplier(row, $el)">
            <option value="">— Chọn nhà cung cấp —</option>
        </select>
    </div>
    <div x-show="row.supplierManual">
        <input type="text" maxlength="255" x-model="row.supplierText" @input="onRowSupplierTextInput(row)" placeholder="VD: HTX Rau sạch Đà Lạt"
               class="input input-bordered input-sm w-full">
    </div>
    <label class="label cursor-pointer justify-start gap-2 py-1">
        <input type="checkbox" x-model="row.supplierManual" @change="onRowSupplierManualToggle(row)" class="checkbox checkbox-xs">
        <span class="label-text text-xs">Khác / Nhập tay</span>
    </label>
</div>
<div class="form-control" x-show="!row.supplierManual">
    <label class="label py-0 pb-1">
        <span class="label-text text-xs font-medium">Hàng hóa nhà cung cấp</span>
        <span class="label-text-alt text-base-content/40 text-xs" x-show="row.loadingPartners">Đang tải…</span>
        <span class="label-text-alt text-base-content/40 text-xs" x-show="!row.loadingPartners && row.vendorId && row.partnerProductsVendorId === row.vendorId && row.partnerProducts.length === 0">NCC chưa kê khai hàng hóa này</span>
    </label>
    <select class="select select-bordered select-sm w-full" x-init="initRowPartner(row, $el)">
        <option value="">— Chọn nguồn cung trước —</option>
    </select>
</div>
