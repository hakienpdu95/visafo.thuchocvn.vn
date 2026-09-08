<?php

namespace Modules\Vendor\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Vendor\Models\Vendor;

class DestroyVendorAction
{
    use AsAction;

    public function handle(Vendor $vendor): string
    {
        $name = $vendor->name;
        $vendor->delete();

        return $name;
    }
}
