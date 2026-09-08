<?php

namespace Modules\Recall\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recall\Data\Requests\StoreProductRecallData;
use Modules\Recall\Enums\RecallStatus;
use Modules\Recall\Models\ProductRecall;
use Modules\Warehouse\Actions\Backend\RecallBatchAction;
use Modules\Warehouse\Models\Batch;

class StoreProductRecallAction
{
    use AsAction;

    public function __construct(
        private readonly RecallBatchAction $recallBatch,
    ) {}

    public function handle(StoreProductRecallData $data): ProductRecall
    {
        return DB::transaction(function () use ($data) {
            $recall = ProductRecall::create([
                'product_id'   => $data->product_id,
                'batch_id'     => $data->batch_id,
                'reason'       => $data->reason,
                'severity'     => $data->severity?->value,
                'status'       => RecallStatus::Active->value,
                'initiated_by' => auth()->id(),
                'initiated_at' => now(),
                'notes'        => $data->notes,
            ]);

            $batches = $data->batch_id
                ? Batch::where('id', $data->batch_id)->get()
                : Batch::where('product_id', $data->product_id)->get();

            foreach ($batches as $batch) {
                $this->recallBatch->handle($batch);
            }

            return $recall;
        });
    }
}
