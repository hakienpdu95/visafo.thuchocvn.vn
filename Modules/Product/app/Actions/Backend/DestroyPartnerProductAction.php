<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\PartnerProduct;

class DestroyPartnerProductAction
{
    use AsAction;

    public function handle(PartnerProduct $partnerProduct): string
    {
        $name = $partnerProduct->name;
        $partnerProduct->delete();

        return $name;
    }
}
