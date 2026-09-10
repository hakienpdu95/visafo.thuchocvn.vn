<?php

namespace Modules\Customer\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Models\CustomerDeliveryPoint;

class DestroyCustomerDeliveryPointAction
{
    use AsAction;

    public function handle(CustomerDeliveryPoint $deliveryPoint): void
    {
        $deliveryPoint->delete();
    }
}
