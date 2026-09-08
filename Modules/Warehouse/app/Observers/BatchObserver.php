<?php

namespace Modules\Warehouse\Observers;

use Illuminate\Support\Facades\DB;
use Modules\Warehouse\Models\Batch;

class BatchObserver
{
    public function creating(Batch $batch): void
    {
        if (empty($batch->internal_batch_code)) {
            $batch->internal_batch_code = $this->generateInternalBatchCode();
        }

        if ($batch->current_qty === null) {
            $batch->current_qty = $batch->initial_qty;
        }
    }

    private function generateInternalBatchCode(): string
    {
        $datePrefix = now()->format('ymd');

        return DB::transaction(function () use ($datePrefix) {
            $lastCode = Batch::where('internal_batch_code', 'like', "BAT-{$datePrefix}-%")
                ->lockForUpdate()
                ->orderByDesc('internal_batch_code')
                ->value('internal_batch_code');

            $sequence = $lastCode ? ((int) substr($lastCode, -3)) + 1 : 1;

            return sprintf('BAT-%s-%03d', $datePrefix, $sequence);
        });
    }
}
