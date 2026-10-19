<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\FarmingSourceData;
use Modules\Product\Models\FarmingSource;

class UpdateFarmingSourceAction
{
    use AsAction;

    public function handle(FarmingSource $farmingSource, FarmingSourceData $data): FarmingSource
    {
        $farmingSource->update([
            'vendor_id'     => $data->vendor_id,
            'name'          => $data->name,
            ...$data->areaAttributes(),
            'water_source'  => $data->water_source,
            'address'       => $data->address,
            'notes'         => $data->notes,
        ]);

        return $farmingSource->fresh();
    }
}
