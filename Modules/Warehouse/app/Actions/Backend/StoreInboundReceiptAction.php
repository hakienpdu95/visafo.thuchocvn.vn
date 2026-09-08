<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Data\Requests\StoreInboundReceiptData;
use Modules\Warehouse\Models\InboundReceipt;

class StoreInboundReceiptAction
{
    use AsAction;

    public function handle(StoreInboundReceiptData $data): InboundReceipt
    {
        return InboundReceipt::create([
            'vendor_id'      => $data->vendor_id,
            'receipt_number' => $data->receipt_number,
            'received_date'  => $data->received_date,
            'status'         => $data->status->value,
            'notes'          => $data->notes,
        ]);
    }
}
