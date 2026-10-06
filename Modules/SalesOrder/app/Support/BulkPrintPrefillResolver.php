<?php

namespace Modules\SalesOrder\Support;

use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;

class BulkPrintPrefillResolver
{
    /** @return array<string, array{origin: ?string, batch_id: ?string, batch_text: ?string, vendor_id: ?string, vendor_name: ?string, supplier_name: ?string, printed: int, has_batches: bool}> */
    public function forOrder(SalesOrder $order): array
    {
        $items = $order->items;
        $logs = PrintLog::query()
            ->whereIn('order_item_id', $items->pluck('id'))
            ->with(['productBatch.goodsReceipt.vendor', 'vendor:id,name'])
            ->orderByRaw('status = ? desc', [PrintLogStatus::Active->value])
            ->latest('created_at')->latest('id')
            ->get()
            ->groupBy('order_item_id');

        $productIds = $items->pluck('product_id')->filter()->unique()->values();
        $batches = ProductBatch::query()
            ->whereIn('product_id', $productIds)
            ->with('goodsReceipt.vendor:id,name')
            ->get()
            ->groupBy('product_id');

        return $items->mapWithKeys(function (SalesOrderItem $item) use ($logs, $batches) {
            $itemLogs = $logs->get($item->id, collect());
            $productBatches = $batches->get($item->product_id, collect());
            $base = [
                'printed'     => $itemLogs->where('status', PrintLogStatus::Active)->count(),
                'has_batches' => $productBatches->isNotEmpty(),
            ];

            if ($last = $itemLogs->first()) {
                return [$item->id => $base + [
                    'origin'        => 'history',
                    'batch_id'      => $last->product_batch_id,
                    'batch_text'    => $last->productBatch ? self::batchText($last->productBatch) : null,
                    'vendor_id'     => $last->vendor_id,
                    'vendor_name'   => $last->vendor?->name,
                    'supplier_name' => $last->vendor_id ? null : $last->supplier_name,
                ]];
            }

            $fifo = $productBatches
                ->filter(fn (ProductBatch $b) => (float) $b->current_qty > 0)
                ->sortBy(fn (ProductBatch $b) => [$b->goodsReceipt?->receipt_date?->timestamp ?? $b->created_at?->timestamp, $b->created_at?->timestamp, $b->id])
                ->first();

            return [$item->id => $base + [
                'origin'        => $fifo ? 'fifo' : null,
                'batch_id'      => $fifo?->id,
                'batch_text'    => $fifo ? self::batchText($fifo) : null,
                'vendor_id'     => $fifo?->goodsReceipt?->vendor_id,
                'vendor_name'   => $fifo?->goodsReceipt?->vendor?->name,
                'supplier_name' => $fifo && ! $fifo->goodsReceipt?->vendor_id ? $fifo->goodsReceipt?->supplier_name : null,
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
