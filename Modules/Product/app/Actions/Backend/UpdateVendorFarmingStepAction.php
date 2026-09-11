<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\VendorFarmingStepData;
use Modules\Product\Models\VendorFarmingStep;

class UpdateVendorFarmingStepAction
{
    use AsAction;

    public function handle(VendorFarmingStep $vendorFarmingStep, VendorFarmingStepData $data): VendorFarmingStep
    {
        $vendorFarmingStep->update([
            'partner_product_id' => $data->partner_product_id,
            'step_name'          => $data->step_name,
            'base_activity_type' => $data->base_activity_type,
            'order_index'        => $data->order_index ?? 0,
        ]);

        return $vendorFarmingStep;
    }
}
