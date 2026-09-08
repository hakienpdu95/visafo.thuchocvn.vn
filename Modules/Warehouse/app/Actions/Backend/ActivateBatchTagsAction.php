<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\RetailItemTag;

class ActivateBatchTagsAction
{
    use AsAction;

    /** Kích hoạt lưu hành toàn bộ tem đang "Bound" (chờ lưu hành) của một lô. */
    public function handle(Batch $batch): int
    {
        return RetailItemTag::where('batch_id', $batch->id)
            ->where('status', RetailItemTagStatus::Bound->value)
            ->update(['status' => RetailItemTagStatus::InStock->value]);
    }
}
