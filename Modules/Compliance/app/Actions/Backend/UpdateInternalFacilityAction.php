<?php

namespace Modules\Compliance\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Data\Requests\InternalFacilityData;
use Modules\Compliance\Models\InternalFacility;

class UpdateInternalFacilityAction
{
    use AsAction;

    public function handle(InternalFacility $internalFacility, InternalFacilityData $data): InternalFacility
    {
        $internalFacility->update([
            'name'    => $data->name,
            'type'    => $data->type,
            'address' => $data->address,
            'status'  => $data->status,
        ]);

        return $internalFacility;
    }
}
