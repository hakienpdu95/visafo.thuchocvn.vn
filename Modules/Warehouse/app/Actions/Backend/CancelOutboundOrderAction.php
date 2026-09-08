<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\OutboundOrderStatus;
use Modules\Warehouse\Models\OutboundOrder;

class CancelOutboundOrderAction
{
    use AsAction;

    public function handle(OutboundOrder $order): OutboundOrder
    {
        $order->update(['status' => OutboundOrderStatus::Cancelled->value]);

        return $order;
    }
}
