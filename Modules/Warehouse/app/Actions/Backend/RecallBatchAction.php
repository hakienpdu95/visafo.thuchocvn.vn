<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\BatchStatus;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\Batch;

class RecallBatchAction
{
    use AsAction;

    public function handle(Batch $batch): Batch
    {
        $batch->update(['status' => BatchStatus::Recalled->value]);

        $batch->tags()
            ->where('status', RetailItemTagStatus::InStock->value)
            ->update(['status' => RetailItemTagStatus::Recalled->value]);

        return $batch;
    }
}
