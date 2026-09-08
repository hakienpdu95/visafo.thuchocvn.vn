<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Data\Requests\UpdateInboundReceiptData;
use Modules\Warehouse\Models\InboundReceipt;

class UpdateInboundReceiptAction
{
    use AsAction;

    public function handle(InboundReceipt $inboundReceipt, UpdateInboundReceiptData $data): InboundReceipt
    {
        $inboundReceipt->update([
            'vendor_id'      => $data->vendor_id,
            'receipt_number' => $data->receipt_number,
            'received_date'  => $data->received_date,
            'status'         => $data->status->value,
            'notes'          => $data->notes,
        ]);

        return $inboundReceipt;
    }
}
