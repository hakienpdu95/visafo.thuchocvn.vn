<?php

namespace Modules\SalesOrder\Support;

use Illuminate\Validation\ValidationException;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Enums\PartnerProductStatus;
use Modules\Product\Models\PartnerProduct;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\Vendor\Models\Vendor;

class PrintSourceResolver
{
    public function resolve(array $data, ?SalesOrderItem $item = null): array
    {
        $vendorId = ($data['vendor_id'] ?? null) ?: null;
        $batchId = ($data['product_batch_id'] ?? null) ?: null;

        if ($batchId !== null) {
            $batch = ProductBatch::query()->with('goodsReceipt.vendor')->find($batchId);

            if ($batch === null || ($item !== null && $batch->product_id !== $item->product_id)) {
                throw ValidationException::withMessages(['product_batch_id' => 'Lô nhập kho không khớp với mặt hàng.']);
            }

            $vendorId = $batch->goodsReceipt?->vendor_id ?? $vendorId;
        }

        $vendorSelected = $vendorId !== null;
        $supplierName = trim((string) ($data['supplier_name'] ?? '')) ?: null;
        $sourceSelected = $vendorSelected || $batchId !== null || $supplierName !== null;
        if ($vendorId === null && $supplierName === null && $item !== null) {
            $vendorId = $this->defaultVendorId($item);
        }

        if ($vendorId !== null) {
            $supplierName = Vendor::query()->whereKey($vendorId)->value('name') ?? $supplierName;
        }

        return [
            'vendor_id'        => $vendorId,
            'product_batch_id' => $batchId,
            'supplier_name'    => $supplierName,
            'vendor_selected'  => $vendorSelected,
            'source_selected'  => $sourceSelected,
        ];
    }

    public function forBulkItem(SalesOrderItem $item, array $itemData, array $globalData): array
    {
        $own = array_filter([
            'vendor_id'        => $itemData['vendor_id'] ?? null,
            'supplier_name'    => trim((string) ($itemData['supplier_name'] ?? '')) ?: null,
            'product_batch_id' => $itemData['product_batch_id'] ?? null,
        ]);

        $global = array_filter([
            'vendor_id'     => $globalData['vendor_id'] ?? null,
            'supplier_name' => trim((string) ($globalData['supplier_name'] ?? '')) ?: null,
        ]);

        return $this->resolve($own !== [] ? $own : $global, $item);
    }

    public function defaultVendorId(SalesOrderItem $item): ?string
    {
        if ($item->product_id === null) {
            return null;
        }

        return ProductBatch::query()
            ->where('product_batches.product_id', $item->product_id)
            ->join('goods_receipts', 'goods_receipts.id', '=', 'product_batches.goods_receipt_id')
            ->whereNotNull('goods_receipts.vendor_id')
            ->whereNull('goods_receipts.deleted_at')
            ->latest('product_batches.created_at')->latest('product_batches.id')
            ->value('goods_receipts.vendor_id')
            ?? PartnerProduct::query()
                ->where('product_id', $item->product_id)
                ->where('status', PartnerProductStatus::Active->value)
                ->whereNotNull('vendor_id')
                ->latest('created_at')->latest('id')
                ->value('vendor_id');
    }
}
