<?php

namespace Modules\Compliance\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Data\Requests\InternalFacilityData;
use Modules\Compliance\Models\InternalFacility;

class StoreInternalFacilityAction
{
    use AsAction;

    public function handle(InternalFacilityData $data): InternalFacility
    {
        return InternalFacility::query()->create([
            'name'    => $data->name,
            'type'    => $data->type,
            'address' => $data->address,
            'status'  => $data->status,
        ]);
    }
}
