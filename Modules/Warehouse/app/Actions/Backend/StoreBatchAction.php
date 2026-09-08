<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Data\Requests\StoreBatchData;
use Modules\Warehouse\Enums\BatchStatus;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\InboundReceipt;

class StoreBatchAction
{
    use AsAction;

    public function handle(InboundReceipt $inboundReceipt, StoreBatchData $data): Batch
    {
        return $inboundReceipt->batches()->create([
            'product_id'       => $data->product_id,
            'vendor_id'        => $inboundReceipt->vendor_id,
            'mfg_batch_number' => $data->mfg_batch_number,
            'mfg_date'         => $data->mfg_date,
            'exp_date'         => $data->exp_date,
            'initial_qty'      => $data->initial_qty,
            'current_qty'      => $data->initial_qty,
            'status'           => BatchStatus::Available->value,
        ]);
    }
}
