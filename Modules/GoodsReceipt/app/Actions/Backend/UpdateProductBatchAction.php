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
        // HSD người dùng đã nhập luôn được giữ nguyên; chỉ tự tính khi để trống.
        $expDate = $data->exp_date ?: $productBatch->calculateExpDate($data->mfg_date)?->toDateString();

        $productBatch->update([
            'mfg_date' => $data->mfg_date,
            'exp_date' => $expDate,
        ]);

        return $productBatch;
    }
}
