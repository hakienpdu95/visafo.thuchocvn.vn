<?php

namespace Modules\HanoiCheckOrder\Observers;

use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheckOrder\Actions\Backend\QueueHanoiCheckOrderSyncAction;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrderItem;
use Throwable;

class PrintLogHanoiCheckObserver
{
    private const WATCHED = ['status', 'product_batch_id', 'weight_per_label', 'order_item_id'];

    public function saved(PrintLog $log): void
    {
        if (! HanoiCheckClient::enabled() || (! $log->wasRecentlyCreated && ! $log->wasChanged(self::WATCHED))) {
            return;
        }

        try {
            $orderId = SalesOrderItem::query()->withTrashed()->whereKey($log->order_item_id)->value('order_id');
            if ($orderId !== null) {
                QueueHanoiCheckOrderSyncAction::run((string) $orderId);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
