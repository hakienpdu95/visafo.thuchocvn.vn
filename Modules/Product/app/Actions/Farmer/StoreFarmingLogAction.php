<?php

namespace Modules\Product\Actions\Farmer;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreFarmingLogData;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingLog;

class StoreFarmingLogAction
{
    use AsAction;

    public function __construct(private ResolveFarmingLogAttributesAction $resolveAttributes) {}

    public function handle(FarmingBatch $farmingBatch, StoreFarmingLogData $data, string $userId, ?string $operatorName = null): FarmingLog
    {
        if ($data->activity_type === 'harvest') {
            $daysLeft = $farmingBatch->quarantineDaysLeft();
            if ($daysLeft > 0) {
                throw ValidationException::withMessages([
                    'activity_type' => 'Lô đang trong thời gian cách ly thuốc (còn ' . $daysLeft . ' ngày). Không được phép thu hoạch!',
                ]);
            }
            if ($farmingBatch->pre_harvest_status !== 'passed') {
                throw ValidationException::withMessages([
                    'activity_type' => 'Lô chưa được QC phê duyệt cho phép thu hoạch.',
                ]);
            }
        }

        $attributes = $this->resolveAttributes->handle($data, $operatorName);

        $log = FarmingLog::query()->create([
            ...$attributes,
            'farming_batch_id' => $farmingBatch->id,
            'image_path'       => $data->image?->store('farming-logs', 'public'),
            'created_by'       => $userId,
        ]);

        if ($data->activity_type === 'harvest') {
            $farmingBatch->update([
                'actual_harvest_date' => $data->activity_date,
                'status'              => 'harvested',
            ]);
        }

        $farmingBatch->revokeHarvestApprovalIfQuarantined();

        return $log;
    }
}
