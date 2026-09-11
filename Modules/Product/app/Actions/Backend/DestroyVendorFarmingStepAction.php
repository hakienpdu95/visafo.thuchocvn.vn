<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\VendorFarmingStep;

class DestroyVendorFarmingStepAction
{
    use AsAction;

    public function handle(VendorFarmingStep $vendorFarmingStep): string
    {
        $name = $vendorFarmingStep->step_name;
        $vendorFarmingStep->delete();

        return $name;
    }
}
