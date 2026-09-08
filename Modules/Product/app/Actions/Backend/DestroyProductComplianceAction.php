<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\ProductCompliance;

class DestroyProductComplianceAction
{
    use AsAction;

    public function handle(ProductCompliance $compliance): void
    {
        $compliance->delete();
    }
}
