<?php

namespace Modules\SalesOrder\Support;

use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;

class BulkPrintPrefillResolver
{
    /** @return array<string, array{origin: ?string, batch_id: ?string, batch_text: ?string, vendor_id: ?string, vendor_name: ?string, partner_product_id: ?string, supplier_name: ?string, printed: int, has_batches: bool}> */
    public function forOrder(SalesOrder $order): array
    {
        $items = $order->items;
        $logs = PrintLog::query()
            ->whereIn('order_item_id', $items->pluck('id'))
            ->with(['productBatch.goodsReceipt.vendor', 'vendor:id,name', 'partnerProduct:id,vendor_id'])
            ->orderByRaw('status = ? desc', [PrintLogStatus::Active->value])
            ->latest('created_at')->latest('id')
            ->get()
            ->groupBy('order_item_id');

        $productIds = $items->pluck('product_id')->filter()->unique()->values();
        $productsWithBatches = ProductBatch::query()
            ->whereIn('product_id', $productIds)
            ->distinct()
            ->pluck('product_id')
            ->flip();

        return $items->mapWithKeys(function (SalesOrderItem $item) use ($logs, $productsWithBatches) {
            $itemLogs = $logs->get($item->id, collect());
            $base = [
                'printed'     => $itemLogs->where('status', PrintLogStatus::Active)->count(),
                'has_batches' => $item->product_id !== null && $productsWithBatches->has($item->product_id),
            ];

            if ($last = $itemLogs->first()) {
                return [$item->id => $base + [
                    'origin'        => 'history',
                    'batch_id'      => $last->product_batch_id,
                    'batch_text'    => $last->productBatch ? self::batchText($last->productBatch) : null,
                    'vendor_id'     => $last->vendor_id,
                    'vendor_name'   => $last->vendor?->name,
                    // Chỉ điền sẵn khi hàng hóa NCC vẫn thuộc đúng NCC của tem (tem cũ: null).
                    'partner_product_id' => $last->partnerProduct?->vendor_id === $last->vendor_id ? $last->partner_product_id : null,
                    'supplier_name' => $last->vendor_id ? null : $last->supplier_name,
                ]];
            }

            // Chưa in lần nào: không tự điền lô / nguồn cung — nhân viên kho phải tự chọn đúng lô thực tế.
            return [$item->id => $base + [
                'origin'        => null,
                'batch_id'      => null,
                'batch_text'    => null,
                'vendor_id'     => null,
                'vendor_name'   => null,
                'partner_product_id' => null,
                'supplier_name' => null,
            ]];
        })->all();
    }

    public static function batchText(ProductBatch $batch): string
    {
        $receipt = $batch->goodsReceipt;

        return $batch->batch_code . ' · ' . ($receipt?->vendor?->name ?? $receipt?->supplier_name ?? 'Chưa rõ NCC')
            . ($receipt?->receipt_date ? ' · nhập ' . $receipt->receipt_date->format('d/m/Y') : '');
    }
}
