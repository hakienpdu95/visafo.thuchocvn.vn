<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\Brand;

class DestroyBrandAction
{
    use AsAction;

    public function handle(Brand $brand): void
    {
        $brand->delete();
    }
}
