<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\FarmingBatch;

class ApproveFarmingBatchHarvestAction
{
    use AsAction;

    public function handle(FarmingBatch $farmingBatch, int|string $checkedByUserId): FarmingBatch
    {
        $farmingBatch->update([
            'pre_harvest_status'       => 'passed',
            'pre_harvest_checked_at'   => now(),
            'pre_harvest_checked_by'   => $checkedByUserId,
        ]);

        return $farmingBatch->fresh();
    }
}
