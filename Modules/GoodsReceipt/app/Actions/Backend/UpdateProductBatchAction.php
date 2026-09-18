<?php

namespace Modules\GoodsReceipt\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Data\Requests\UpdateProductBatchData;
use Modules\GoodsReceipt\Models\ProductBatch;

class UpdateProductBatchAction
{
    use AsAction;

    public function handle(ProductBatch $productBatch, UpdateProductBatchData $data): ProductBatch
    {
        $productBatch->update([
            'mfg_date' => $data->mfg_date,
            'exp_date' => $data->exp_date,
        ]);

        return $productBatch;
    }
}
