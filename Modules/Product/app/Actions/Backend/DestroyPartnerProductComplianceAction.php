<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\PartnerProductCompliance;

class DestroyPartnerProductComplianceAction
{
    use AsAction;

    public function handle(PartnerProductCompliance $compliance): void
    {
        $compliance->delete();
    }
}
