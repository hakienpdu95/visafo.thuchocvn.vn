<?php

namespace Modules\Product\Actions\Farmer;

use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreFarmingLogData;
use Modules\Product\Models\FarmingLog;

class UpdateFarmingLogAction
{
    use AsAction;

    public function __construct(private ResolveFarmingLogAttributesAction $resolveAttributes) {}

    public function handle(FarmingLog $farmingLog, StoreFarmingLogData $data): FarmingLog
    {
        $imagePath = $farmingLog->image_path;
        if ($data->image) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $data->image->store('farming-logs', 'public');
        }

        $farmingLog->update([
            ...$this->resolveAttributes->handle($data, $farmingLog->operatorText()),
            'image_path' => $imagePath,
        ]);

        $farmingLog->batch->revokeHarvestApprovalIfQuarantined();

        return $farmingLog;
    }
}
