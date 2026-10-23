<?php

namespace Modules\SalesOrder\Support;

use Illuminate\Support\Collection;
use Modules\GoodsReceipt\Models\BatchQualityCheck;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\LabelPrintEvent;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrderItem;

class SalesOrderItemRemover
{
    /**
     * @param  array<int, string>  $itemIds
     * @return array<int, string>
     */
    public static function qcItemIds(array $itemIds): array
    {
        return BatchQualityCheck::withTrashed()->whereIn('sales_order_item_id', $itemIds)->distinct()->pluck('sales_order_item_id')->all();
    }

    /**
     * @param  Collection<int, SalesOrderItem>  $items
     * @return int số tem đã hủy
     */
    public static function remove(Collection $items, string $reason, ?string $userId): int
    {
        $ids = $items->pluck('id')->all();
        $printed = PrintLog::withTrashed()->whereIn('order_item_id', $ids)->distinct()->pluck('order_item_id')
            ->merge(LabelPrintEvent::query()->whereIn('order_item_id', $ids)->distinct()->pluck('order_item_id'))
            ->unique()->all();

        $logs = PrintLog::query()->whereIn('order_item_id', $printed)->where('status', PrintLogStatus::Active->value)->get();

        foreach ($logs as $log) {
            $log->update([
                'status'            => PrintLogStatus::Revoked,
                'status_reason'     => $reason,
                'status_changed_by' => $userId,
                'status_changed_at' => now(),
            ]);
        }

        foreach ($items as $item) {
            in_array($item->id, $printed, true) ? $item->delete() : $item->forceDelete();
        }

        return $logs->count();
    }
}
