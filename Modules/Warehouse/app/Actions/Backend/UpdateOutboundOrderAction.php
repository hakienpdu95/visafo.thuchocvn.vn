<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Data\Requests\UpdateOutboundOrderData;
use Modules\Warehouse\Models\OutboundOrder;

class UpdateOutboundOrderAction
{
    use AsAction;

    public function handle(OutboundOrder $order, UpdateOutboundOrderData $data): OutboundOrder
    {
        $order->update([
            'order_number'   => $data->order_number,
            'dealer_name'    => $data->dealer_name,
            'dealer_phone'   => $data->dealer_phone,
            'dealer_address' => $data->dealer_address,
            'ordered_at'     => $data->ordered_at,
            'notes'          => $data->notes,
        ]);

        return $order;
    }
}
