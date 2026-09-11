<?php

namespace Modules\Product\Actions\Farmer;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreFarmingLogData;
use Modules\Product\Models\AgriPesticide;
use Modules\Product\Models\FarmingLog;

class UpdateFarmingLogAction
{
    use AsAction;

    public function handle(FarmingLog $farmingLog, StoreFarmingLogData $data): FarmingLog
    {
        $safeHarvestDate = null;
        if ($data->activity_type === 'pesticide' && $data->agri_pesticide_id) {
            $pesticide = AgriPesticide::query()->find($data->agri_pesticide_id);
            if ($pesticide?->quarantine_days !== null) {
                $safeHarvestDate = Carbon::parse($data->activity_date)->addDays($pesticide->quarantine_days);
            }
        }

        $imagePath = $farmingLog->image_path;
        if ($data->image) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $data->image->store('farming-logs', 'public');
        }

        $farmingLog->update([
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
            'notes'                  => $data->notes,
        ]);

        return $farmingLog;
    }
}
