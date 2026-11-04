<?php

namespace Modules\HanoiCheckOrder\Observers;

use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheckOrder\Actions\Backend\QueueHanoiCheckOrderSyncAction;
use Modules\SalesOrder\Models\SalesOrder;
use Throwable;

class SalesOrderHanoiCheckObserver
{
    public function updated(SalesOrder $order): void
    {
        if (! HanoiCheckClient::enabled() || ! $order->wasChanged(['status', 'shipped_at', 'delivered_at'])) {
            return;
        }

        try {
            QueueHanoiCheckOrderSyncAction::run((string) $order->id);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
