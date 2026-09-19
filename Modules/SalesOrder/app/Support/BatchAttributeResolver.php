<?php

namespace Modules\SalesOrder\Support;

use Modules\GoodsReceipt\Models\BatchAttribute;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\SalesOrder\Models\SalesOrderItem;

/**
 * Xác định "Lô hàng tương ứng" của một dòng đơn xuất và lấy thông tin bổ sung (EAV) của lô đó.
 * Đơn xuất chỉ biết sản phẩm, không gắn lô → lấy lô nhập gần nhất của sản phẩm có khai báo thông tin bổ sung.
 */
class BatchAttributeResolver
{
    /** @return array{batch_id: ?string, attributes: array<int, array{key: string, value: string}>} */
    public function forItem(SalesOrderItem $item): array
    {
        $batch = ProductBatch::query()
            ->where('product_id', $item->product_id)
            ->whereHas('extraAttributes')
            ->latest('created_at')->latest('id')
            ->with('extraAttributes')
            ->first();

        return [
            'batch_id'   => $batch?->id,
            'attributes' => $batch === null ? [] : $batch->extraAttributes
                ->map(fn (BatchAttribute $a) => ['key' => $a->attribute_key, 'value' => (string) $a->attribute_value])
                ->values()
                ->all(),
        ];
    }
}
