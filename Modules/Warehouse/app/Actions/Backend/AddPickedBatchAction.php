<?php

namespace Modules\Warehouse\Actions\Backend;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Data\Requests\AddPickedBatchData;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\OutboundOrder;
use Modules\Warehouse\Models\OutboundPickedBatch;

class AddPickedBatchAction
{
    use AsAction;

    public function handle(OutboundOrder $order, AddPickedBatchData $data): OutboundPickedBatch
    {
        $batch = Batch::findOrFail($data->batch_id);

        if ($data->quantity > $batch->current_qty) {
            throw ValidationException::withMessages([
                'quantity' => "Lô \"{$batch->internal_batch_code}\" chỉ còn {$batch->current_qty} đơn vị.",
            ]);
        }

        return $order->pickedBatches()->create([
            'product_id' => $batch->product_id,
            'batch_id'   => $batch->id,
            'quantity'   => $data->quantity,
        ]);
    }
}
