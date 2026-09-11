<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\VendorFarmingStepData;
use Modules\Product\Models\VendorFarmingStep;
use Modules\Vendor\Models\Vendor;

class StoreVendorFarmingStepAction
{
    use AsAction;

    public function handle(Vendor $vendor, VendorFarmingStepData $data): VendorFarmingStep
    {
        return VendorFarmingStep::query()->create([
            'vendor_id'           => $vendor->id,
            'partner_product_id'  => $data->partner_product_id,
            'step_name'           => $data->step_name,
            'base_activity_type'  => $data->base_activity_type,
            'order_index'         => $data->order_index ?? 0,
        ]);
    }
}
