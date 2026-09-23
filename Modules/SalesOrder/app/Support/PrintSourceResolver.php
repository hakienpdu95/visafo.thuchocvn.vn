<?php

namespace Modules\SalesOrder\Support;

use Illuminate\Validation\ValidationException;
use Modules\GoodsReceipt\Models\ProductBatch;
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

        $supplierName = $data['supplier_name'] ?? null;
        if ($vendorId !== null) {
            $supplierName = Vendor::query()->whereKey($vendorId)->value('name') ?? $supplierName;
        }

        return [
            'vendor_id'        => $vendorId,
            'product_batch_id' => $batchId,
            'supplier_name'    => $supplierName,
        ];
    }
}
