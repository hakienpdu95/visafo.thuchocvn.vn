<?php

namespace Modules\SalesPackage\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesPackage\Enums\SalesPackageStatus;
use Modules\SalesPackage\Models\SalesPackage;

class UpdateSalesPackageStatusAction
{
    use AsAction;

    public function handle(SalesPackage $package, SalesPackageStatus $status): SalesPackage
    {
        $package->update(['status' => $status->value]);

        return $package->fresh();
    }
}
