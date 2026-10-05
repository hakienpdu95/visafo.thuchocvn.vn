<?php

namespace Modules\GoodsReceipt\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Models\BatchQualityCheck;

class StoreBatchQualityCheckAction
{
    use AsAction;

    /** @param array{stage: string, result: string, checked_at?: ?string, note?: ?string, product_batch_id?: ?string, sales_order_item_id?: ?string} $data */
    public function handle(array $data): BatchQualityCheck
    {
        return BatchQualityCheck::create([
            'product_batch_id'    => $data['product_batch_id'] ?? null,
            'sales_order_item_id' => $data['sales_order_item_id'] ?? null,
            'stage'               => $data['stage'],
            'result'              => $data['result'],
            'checked_at'          => $data['checked_at'] ?? now(),
            'checked_by'          => auth()->id(),
            'note'                => $data['note'] ?? null,
        ]);
    }
}
