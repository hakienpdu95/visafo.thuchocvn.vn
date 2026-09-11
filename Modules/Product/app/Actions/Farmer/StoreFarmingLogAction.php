<?php

namespace Modules\Product\Actions\Farmer;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreFarmingLogData;
use Modules\Product\Models\AgriPesticide;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingLog;

class StoreFarmingLogAction
{
    use AsAction;

    public function handle(FarmingBatch $farmingBatch, StoreFarmingLogData $data, string $userId): FarmingLog
    {
        if ($data->activity_type === 'harvest' && $farmingBatch->pre_harvest_status !== 'passed') {
            throw ValidationException::withMessages([
                'activity_type' => 'Chưa hết thời gian cách ly. Không thể thu hoạch!',
            ]);
        }

        $safeHarvestDate = null;
        if ($data->activity_type === 'pesticide' && $data->agri_pesticide_id) {
            $pesticide = AgriPesticide::query()->find($data->agri_pesticide_id);
            if ($pesticide?->quarantine_days !== null) {
                $safeHarvestDate = Carbon::parse($data->activity_date)->addDays($pesticide->quarantine_days);
            }
        }

        $imagePath = $data->image?->store('farming-logs', 'public');

        $log = FarmingLog::query()->create([
            'farming_batch_id'       => $farmingBatch->id,
            'activity_type'          => $data->activity_type,
            'activity_date'          => $data->activity_date,
            'agri_fertilizer_id'     => $data->agri_fertilizer_id,
            'agri_pesticide_id'      => $data->agri_pesticide_id,
            'vendor_farming_step_id' => $data->vendor_farming_step_id,
            'quantity'               => $data->quantity,
            'unit'                   => $data->unit,
            'method_or_target'       => $data->method_or_target,
            'safe_harvest_date'      => $safeHarvestDate,
            'image_path'             => $imagePath,
            'created_by'             => $userId,
            'notes'                  => $data->notes,
        ]);

        if ($data->activity_type === 'harvest') {
            $farmingBatch->update([
                'actual_harvest_date' => $data->activity_date,
                'status'              => 'harvested',
            ]);
        }

        return $log;
    }
}
