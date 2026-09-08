<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Data\Requests\StoreOutboundOrderData;
use Modules\Warehouse\Enums\OutboundOrderStatus;
use Modules\Warehouse\Models\OutboundOrder;

class StoreOutboundOrderAction
{
    use AsAction;

    public function handle(StoreOutboundOrderData $data): OutboundOrder
    {
        return OutboundOrder::create([
            'order_number'   => $data->order_number,
            'order_type'     => 'wholesale',
            'dealer_name'    => $data->dealer_name,
            'dealer_phone'   => $data->dealer_phone,
            'dealer_address' => $data->dealer_address,
            'status'         => OutboundOrderStatus::Draft->value,
            'ordered_at'     => $data->ordered_at,
            'notes'          => $data->notes,
        ]);
    }
}
