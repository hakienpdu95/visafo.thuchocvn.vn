<?php

namespace Modules\SalesPackage\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesPackage\Models\SalesPackage;

class DestroySalesPackageAction
{
    use AsAction;

    public function handle(SalesPackage $package): string
    {
        $name = $package->name;
        $package->delete();

        return $name;
    }
}
